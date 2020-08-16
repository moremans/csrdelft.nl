<?php

namespace CsrDelft\controller\fiscaat;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\common\ContainerFacade;
use CsrDelft\controller\AbstractController;
use CsrDelft\entity\fiscaat\CiviBestelling;
use CsrDelft\entity\fiscaat\CiviBestellingInhoud;
use CsrDelft\repository\fiscaat\CiviBestellingRepository;
use CsrDelft\repository\fiscaat\CiviProductRepository;
use CsrDelft\repository\fiscaat\CiviSaldoRepository;
use CsrDelft\view\renderer\TemplateView;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ParseCsv\Csv;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class CiviSaldoAfschrijvenController extends AbstractController {
	/**
	 * @Route("/fiscaat/afschrijven")
	 * @return TemplateView
	 * @Auth(P_FISCAAT_MOD)
	 */
	public function afschrijven() {
		return view('fiscaat.afschrijven', []);
	}

	private function quickMelding($melding, $code, $url = '/fiscaat/afschrijven') {
		setMelding($melding, $code);
		return $this->redirect($url);
	}

	/**
	 * @Route("/fiscaat/afschrijven/upload", methods={"POST"})
	 * @Auth(P_FISCAAT_MOD)
	 * @param Request $request
	 * @param Session $session
	 * @return Response
	 */
	public function upload(Request $request, Session $session) {
		// Kijk of bestand meegegeven is
		if (!$request->files->has('csv')) {
			return $this->quickMelding("Upload een CSV", 2);
		}

		// Kijk of bestand CSV is
		/** @var UploadedFile $file */
		$file = $request->files->get('csv');
		if (!in_array($file->getMimeType(), ['text/plain', 'text/csv', 'application/vnd.ms-excel'])) {
			return $this->quickMelding("Alleen een CSV is toegestaan", 2);
		}

		// Parse CSV
		$csv = new Csv();
		if ($csv->auto($file->getPathname()) === false) {
			return $this->quickMelding("Fout bij inlezen van CSV", 2);
		}
		$data = $csv->data;

		// Controleer of er regels zijn en eerste regel geldige keys heeft
		if (empty($data) === 0) {
			return $this->quickMelding("Geen regels gevonden", 2);
		}
		if (array_keys($data[0]) !== ['uid', 'productID', 'aantal', 'beschrijving']) {
			return $this->quickMelding("Ongeldige kolommen in de CSV", 2);
		}

		// Sla data op in sessie
		$key = uniqid();
		$session->set("afschrijven-{$key}", $data);

		// Redirect naar check pagina
		return $this->redirect('/fiscaat/afschrijven/controle/' . $key);
	}

	/**
	 * @Route("/fiscaat/afschrijven/controle/{key}")
	 * @Auth(P_FISCAAT_MOD)
	 * @param string $key
	 * @param Session $session
	 * @param CiviSaldoRepository $civiSaldoRepository
	 * @param CiviProductRepository $civiProductRepository
	 * @return TemplateView|RedirectResponse
	 */
	public function controle(
		string $key,
		Session $session,
		CiviSaldoRepository $civiSaldoRepository,
		CiviProductRepository $civiProductRepository
	) {
		// Haal data op
		if (!$session->has("afschrijven-{$key}")) {
			return $this->quickMelding("Er ging iets fout bij het inladen van de CSV", 2);
		}
		$data = $session->get("afschrijven-{$key}");

		// Ga regels langs
		$aantalSucces = 0;
		$aantalGefaald = 0;
		$afschriften = [];
		$i = -1;
		foreach ($data as $regel) {
			$i++;
			$afschriften[$i] = new stdClass();
			$afschriften[$i]->succes = true;
			$afschriften[$i]->regel = $regel;
			$afschriften[$i]->productNaam = '';
			$afschriften[$i]->accountNaam = '';
			$afschriften[$i]->waarschuwing = [];
			$afschriften[$i]->totaal = 0;
			$aantalGefaald++;

			// Check keys
			if (array_keys($data[0]) !== ['uid', 'productID', 'aantal', 'beschrijving']) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = 'Ongeldige kolommen';
				continue;
			}

			// Haal account op
			$account = $civiSaldoRepository->findOneBy(['uid' => (strlen($regel['uid']) === 3 ? '0' : '') . $regel['uid']]);
			if (!$account) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = "Account {$regel['uid']} niet gevonden";
				$afschriften[$i]->accountNaam = $regel['uid'];
			} elseif ($account->deleted) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = 'Account is verwijderd';
				$afschriften[$i]->accountNaam = $account->getDataTableNaam();
			} else {
				$afschriften[$i]->accountNaam = $account->getDataTableNaam();
			}

			// Haal product op
			$product = $civiProductRepository->find(intval($regel['productID']));
			if (!$product) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = "Product {$regel['productID']} niet gevonden";
				$afschriften[$i]->productNaam = $regel['productID'];
			} else {
				$afschriften[$i]->productNaam = $product->getWeergave();
			}

			// Check aantal
			if (empty($regel['aantal'])) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = 'Geen aantal ingevuld';
			} else {
				$aantal = intval($regel['aantal']);
			}

			// Check beschrijving
			if (empty($regel['beschrijving'])) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = 'Geen beschrijving ingevuld';
			} elseif (strlen($regel['beschrijving']) > 255) {
				$afschriften[$i]->succes = false;
				$afschriften[$i]->waarschuwing[] = 'Beschrijving is te lang';
			}

			// Bereken nieuwe CiviSaldo
			if ($account && $product && isset($aantal)) {
				$afschriften[$i]->totaal = $product->getPrijsInt() * $aantal / 100;
				$afschriften[$i]->nieuwSaldo = $account->saldo / 100 - $afschriften[$i]->totaal;
			}

			// Sla op
			if ($afschriften[$i]->succes) {
				$aantalSucces++;
				$aantalGefaald--;
			}
		}

		// Overzicht tonen
		return view('fiscaat.afschrijven-overzicht', [
			'key' => $key,
			'aantalSucces' => $aantalSucces,
			'aantalGefaald' => $aantalGefaald,
			'afschriften' => $afschriften,
		]);
	}

	/**
	 * @Route("/fiscaat/afschrijven/verwerk/{key}", methods={"POST"})
	 * @Auth(P_FISCAAT_MOD)
	 * @param string $key
	 * @param Session $session
	 * @param CiviSaldoRepository $civiSaldoRepository
	 * @param CiviProductRepository $civiProductRepository
	 * @param CiviBestellingRepository $civiBestellingRepository
	 * @param Request $request
	 * @param EntityManagerInterface $em
	 * @return TemplateView|RedirectResponse
	 */
	public function verwerk(
		string $key,
		Session $session,
		CiviSaldoRepository $civiSaldoRepository,
		CiviProductRepository $civiProductRepository,
		CiviBestellingRepository $civiBestellingRepository,
		Request $request,
		EntityManagerInterface $em
	) {
		// Haal data op
		if (!$session->has("afschrijven-{$key}")) {
			return $this->quickMelding("Er ging iets fout bij het verwerken van de CSV", 2);
		} elseif ($session->has("afschrijven-{$key}-locked")) {
			return $this->quickMelding("Deze CSV wordt al verwerkt", 2);
		} else {
			$session->set("afschrijven-{$key}-locked", true);
		}
		$data = $session->get("afschrijven-{$key}");

		if (!$request->request->has('gecheckt') || !$request->request->has('foutenAkkoord')) {
			$session->remove("afschrijven-{$key}-locked");
			return $this->quickMelding("Geef akkoord voor verwerking", 2, "/fiscaat/afschrijven/controle/{$key}");
		}

		// Ga regels langs
		$aantalSucces = 0;
		$totaal = 0;
		$em->transactional(function () use ($civiBestellingRepository, $civiSaldoRepository, $civiProductRepository, $data, &$aantalSucces, &$totaal, $session, $key) {
			/** @var CiviBestelling[] $bestellingen */
			$bestellingen = [];
			foreach ($data as $regel) {
				// Check keys
				if (array_keys($data[0]) !== ['uid', 'productID', 'aantal', 'beschrijving']) {
					continue;
				}

				// Haal account & product op
				$account = $civiSaldoRepository->findOneBy(['uid' => (strlen($regel['uid']) === 3 ? '0' : '') . $regel['uid']]);
				$product = $civiProductRepository->find(intval($regel['productID']));
				if (!$account || $account->deleted || !$product) {
					continue;
				}

				// Check aantal
				if (empty($regel['aantal'])) {
					continue;
				} else {
					$aantal = intval($regel['aantal']);
				}

				// Check beschrijving
				if (empty($regel['beschrijving']) || strlen($regel['beschrijving']) > 255) {
					continue;
				}

				// Verwerk
				$totaal += $product->getPrijsInt() * $aantal / 100;
				$aantalSucces++;

				$bestelling = new CiviBestelling();
				$bestelling->cie = 'anders';
				$bestelling->uid = $account->uid;
				$bestelling->civiSaldo = $account;
				$bestelling->deleted = false;
				$bestelling->moment = new DateTime();
				$bestelling->comment = $regel['beschrijving'];

				$inhoud = new CiviBestellingInhoud();
				$inhoud->aantal = $aantal;
				$inhoud->product_id = $product->id;
				$inhoud->product = $product;

				$bestelling->inhoud[] = $inhoud;
				$bestelling->totaal = $product->getPrijsInt() * $aantal;
				$bestellingen[] = $bestelling;
			}

			foreach ($bestellingen as $bestelling) {
				$civiBestellingRepository->create($bestelling);
				$civiSaldoRepository->verlagen($bestelling->uid, $bestelling->totaal);
			}

			$session->remove("afschrijven-{$key}");
		});

		$session->remove("afschrijven-{$key}-lock");

		// Overzicht tonen
		return view('fiscaat.afschrijven-succes', [
			'aantalSucces' => $aantalSucces,
			'totaal' => $totaal
		]);
	}

	/**
	 * @Route("/fiscaat/afschrijven/template")
	 * @Auth(P_FISCAAT_MOD)
	 * @return Response
	 */
	public function downloadTemplate() {
		$template = "uid;productID;aantal;beschrijving\r\nx101;32;100;Lunch";
		$response = new Response($template);
		$disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'afschrijven.csv');
		$response->headers->set('Content-Type', 'text/csv');
		$response->headers->set('Content-Disposition', $disposition);
		return $response;
	}
}