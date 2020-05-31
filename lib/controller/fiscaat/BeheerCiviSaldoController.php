<?php

namespace CsrDelft\controller\fiscaat;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\common\CsrToegangException;
use CsrDelft\Component\DataTable\RemoveDataTableEntry;
use CsrDelft\controller\AbstractController;
use CsrDelft\entity\fiscaat\CiviSaldo;
use CsrDelft\repository\fiscaat\CiviBestellingRepository;
use CsrDelft\repository\fiscaat\CiviSaldoRepository;
use CsrDelft\repository\ProfielRepository;
use CsrDelft\service\ProfielService;
use CsrDelft\view\datatable\GenericDataTableResponse;
use CsrDelft\view\fiscaat\saldo\CiviSaldoTable;
use CsrDelft\view\fiscaat\saldo\InleggenForm;
use CsrDelft\view\fiscaat\saldo\LidRegistratieForm;
use CsrDelft\view\fiscaat\saldo\SaldiSomForm;
use CsrDelft\view\JsonResponse;
use CsrDelft\view\renderer\TemplateView;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * BeheerCiviSaldoController.class.php
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 07/04/2017
 */
class BeheerCiviSaldoController extends AbstractController {
	/**
	 * @var CiviSaldoRepository
	 */
	private $civiSaldoRepository;
	/**
	 * @var CiviBestellingRepository
	 */
	private $civiBestellingRepository;
	/**
	 * @var ProfielService
	 */
	private $profielService;

	public function __construct(CiviSaldoRepository $civiSaldoRepository, CiviBestellingRepository $civiBestellingRepository, ProfielService $profielService) {
		$this->profielService = $profielService;
		$this->civiSaldoRepository = $civiSaldoRepository;
		$this->civiBestellingRepository = $civiBestellingRepository;
	}

	/**
	 * @return TemplateView
	 * @Route("/fiscaat/saldo", methods={"GET"})
	 * @Auth(P_FISCAAT_READ)
	 */
	public function overzicht() {
		return view('fiscaat.pagina', [
			'titel' => 'Saldo beheer',
			'view' => new CiviSaldoTable(),
		]);
	}

	/**
	 * @return GenericDataTableResponse
	 * @Route("/fiscaat/saldo", methods={"POST"})
	 * @Auth(P_FISCAAT_READ)
	 */
	public function lijst() {
		return $this->tableData($this->civiSaldoRepository->findBy(['deleted' => false]));
	}

	/**
	 * @param EntityManagerInterface $em
	 * @return GenericDataTableResponse|InleggenForm
	 * @Route("/fiscaat/saldo/inlegen", methods={"POST"})
	 * @Auth(P_FISCAAT_MOD)
	 */
	public function inleggen(EntityManagerInterface $em) {
		$selection = filter_input(INPUT_POST, 'DataTableSelection', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);

		/** @var CiviSaldo $civisaldo */
		$civisaldo = $this->civiSaldoRepository->retrieveByUUID($selection[0]);

		if ($civisaldo) {
			$form = new InleggenForm($civisaldo);
			$values = $form->getValues();
			if ($form->validate() AND $values['inleg'] !== 0 AND $values['saldo'] == $civisaldo->saldo) {
				$inleg = $values['inleg'];
				$em->transactional(function () use ($inleg, $civisaldo) {
					$bestelling = $this->civiBestellingRepository->vanBedragInCenten($inleg, $civisaldo->uid);
					$this->civiBestellingRepository->create($bestelling);

					$this->civiSaldoRepository->ophogen($civisaldo->uid, $inleg);
					$civisaldo->saldo += $inleg;
					$civisaldo->laatst_veranderd = date_create_immutable();
				});

				return $this->tableData([$civisaldo]);
			} else {
				return $form;
			}
		}

		throw new CsrToegangException();
	}

	/**
	 * @return GenericDataTableResponse
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @Route("/fiscaat/saldo/verwijderen", methods={"POST"})
	 * @Auth(P_FISCAAT_MOD)
	 */
	public function verwijderen() {
		$selection = filter_input(INPUT_POST, 'DataTableSelection', FILTER_SANITIZE_STRING, FILTER_FORCE_ARRAY);

		$removed = array();
		foreach ($selection as $uuid) {
			/** @var CiviSaldo $civisaldo */
			$civisaldo = $this->civiSaldoRepository->retrieveByUUID($uuid);

			if ($civisaldo) {
				$civisaldo->deleted = true;
				$removed[] = new RemoveDataTableEntry($civisaldo->id, CiviSaldo::class);
				$this->civiSaldoRepository->update($civisaldo);
			}
		}

		if (!empty($removed)) {
			return $this->tableData($removed);
		}

		throw new CsrToegangException();
	}

	/**
	 * @return GenericDataTableResponse|LidRegistratieForm
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @Route("/fiscaat/saldo/registreren", methods={"POST"})
	 * @Auth(P_FISCAAT_MOD)
	 */
	public function registreren() {
		$form = new LidRegistratieForm(new CiviSaldo());

		if ($form->validate()) {
			/** @var CiviSaldo $saldo */
			$saldo = $form->getModel();
			$saldo->laatst_veranderd = date_create_immutable();

			if (is_null($saldo->uid)) {
				$laatsteSaldo = $this->civiSaldoRepository->findLaatsteCommissie();
				$saldo->uid = ++$laatsteSaldo->uid;
			}

			if (is_null($saldo->naam)) {
				$saldo->naam = '';
			}

			if (count($this->civiSaldoRepository->findBy(['uid' => $saldo->uid])) === 1) {
				throw new CsrToegangException();
			} else {
				$this->civiSaldoRepository->create($saldo);
			}

			return $this->tableData([$saldo]);
		}

		return $form;
	}

	/**
	 * @return TemplateView
	 * @Route("/fiscaat/saldo/som", methods={"POST"})
	 * @Auth(P_FISCAAT_MOD)
	 */
	public function som() {
		$momentString = filter_input(INPUT_POST, 'moment', FILTER_SANITIZE_STRING);
		$moment = DateTime::createFromFormat("Y-m-d H:i:s", $momentString);
		if (!$moment) {
			throw new CsrToegangException();
		}

		return view('fiscaat.saldisom', [
			'saldisomform' => new SaldiSomForm($this->civiSaldoRepository, $moment),
			'saldisom' => $this->civiSaldoRepository->getSomSaldiOp($moment),
			'saldisomleden' => $this->civiSaldoRepository->getSomSaldiOp($moment, true),
		]);
	}

	/**
	 * @param Request $request
	 * @return JsonResponse
	 * @Route("/fiscaat/saldo/zoek", methods={"GET"})
	 * @Auth(P_FISCAAT_READ)
	 */
	public function zoek(Request $request) {
		$zoekterm = $request->query->get('q');

		$leden = $this->profielService->zoekLeden($zoekterm, 'naam', 'alle', 'achternaam');
		$uids = array_map(function ($profiel) { return $profiel->uid; }, $leden);

		$civiSaldi = $this->civiSaldoRepository->zoeken($uids, $zoekterm);

		$resp = [];
		foreach ($civiSaldi as $civiSaldo) {
			$profiel = ProfielRepository::get($civiSaldo->uid);
			$resp[] = [
				'label' => $profiel === false ? $civiSaldo->naam : $profiel->getNaam('volledig'),
				'value' => $civiSaldo->uid
			];
		}

		return new JsonResponse($resp);
	}
}
