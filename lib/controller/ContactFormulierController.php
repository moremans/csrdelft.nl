<?php

namespace CsrDelft\controller;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\common\CsrGebruikerException;
use CsrDelft\common\Mail;
use CsrDelft\common\SimpleSpamFilter;
use CsrDelft\view\PlainView;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 19/12/2018
 */
class ContactFormulierController extends AbstractController {
	/**
	 * @return PlainView
	 * @Route("/contactformulier/interesse", methods={"POST"})
	 * @Auth(P_PUBLIC)
	 */
	public function interesse() {
		$resp = $this->checkCaptcha(filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING));

		if (!$resp['success']) {
			throw $this->createAccessDeniedException("Geen toegang");
		}

		$naam = filter_input(INPUT_POST, "naam", FILTER_SANITIZE_STRING);
		$achternaam = filter_input(INPUT_POST, "achternaam", FILTER_SANITIZE_STRING);
		$email = filter_input(INPUT_POST, "submit_by", FILTER_SANITIZE_STRING);
		$adres = filter_input(INPUT_POST, "straat", FILTER_SANITIZE_STRING);
		$postcode = filter_input(INPUT_POST, "postcode", FILTER_SANITIZE_STRING);
		$woonplaats = filter_input(INPUT_POST, "plaats", FILTER_SANITIZE_STRING);
		$telefoon = filter_input(INPUT_POST, "telefoon", FILTER_SANITIZE_STRING);
		$opmerking = filter_input(INPUT_POST, "opmerking", FILTER_SANITIZE_STRING);

		$interesses = [
			filter_input(INPUT_POST, "interesse1", FILTER_SANITIZE_STRING),
			filter_input(INPUT_POST, "interesse2", FILTER_SANITIZE_STRING),
			filter_input(INPUT_POST, "interesse3", FILTER_SANITIZE_STRING),
			filter_input(INPUT_POST, "interesse4", FILTER_SANITIZE_STRING),
		];

		$interessestring = '';
		foreach ($interesses as $interesse) {
			if ($interesse) {
				$interessestring .= " * " . $interesse . "\n";
			}
		}

		if ($achternaam || $this->bevatUrl($opmerking) || $this->isSpam($naam, $email, $adres, $postcode, $woonplaats, $telefoon, $opmerking, $interessestring)) {
			throw new CsrGebruikerException('Bericht bevat ongeldige tekst.');
		}

		$bericht = "
Beste OweeCie,

Het interesseformulier op de stek is ingevuld:

Naam: $naam
Email: $email
Adres: $adres
Postcode: $postcode
Woonplaats: $woonplaats
Telefoon: $telefoon

Interesses:
$interessestring
Opmerking:
$opmerking


Met vriendelijke groeten,
De PubCie.
";

		$mail = new Mail([$_ENV['EMAIL_OWEECIE'] => "OweeCie"], "Interesseformulier", $bericht);
		$mail->setFrom($email);
		$mail->send();

		return new PlainView('Bericht verzonden, je zult binnenkort meer horen.');
	}

	/**
	 * @return PlainView
	 * @Route("/contactformulier/owee", methods={"POST"})
	 * @Auth(P_PUBLIC)
	 */
	public function owee() {
		$resp = $this->checkCaptcha(filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING));

		if (!$resp['success']) {
			throw $this->createAccessDeniedException("Geen toegang");
		}

		$type = filter_input(INPUT_POST, "optie", FILTER_SANITIZE_STRING);
		$naam = filter_input(INPUT_POST, "naam", FILTER_SANITIZE_STRING);
		$email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_STRING);
		$telefoon = filter_input(INPUT_POST, "telefoon", FILTER_SANITIZE_STRING);

		if ($this->isSpam($naam, $email, $telefoon)) {
			throw new CsrGebruikerException('Bericht bevat ongeldige tekst.');
		}

		$commissie = 'PromoCie';
		$bestemming = [$_ENV['EMAIL_PROMOCIE'] => $commissie];

		if ($type === 'lid-worden') {
			$typeaanduiding = 'Ik wil lid worden';
//			$commissie = "NovCie";
//			$bestemming = [$_ENV['EMAIL_NOVCIE'] => $commissie];
		} else {
			$typeaanduiding = 'Eerst een lid spreken';
//			$commissie = "OweeCie";
//			$bestemming = [$_ENV['EMAIL_OWEECIE'] => $commissie];
		}

		$bericht = $this->renderView('mail/bericht/contactformulier.mail.twig', [
			'telefoon' => $telefoon,
			'typeaanduiding' => $typeaanduiding,
			'naam' => $naam,
			'email' => $email,
			'commissie' => $commissie,
		]);

		$mail = new Mail($bestemming, "Lid worden formulier", $bericht);
		$mail->setFrom($_ENV['EMAIL_PUBCIE']);
		$mail->send();

		return new PlainView('Bericht verzonden, je zult binnenkort meer horen.');
	}

	private function isSpam(...$input) {
		$filter = new SimpleSpamFilter();
		foreach ($input as $item) {
			if ($item && $filter->isSpam($item)) {
				return true;
			}
		}
		return false;
	}

	private function bevatUrl($opmerking) {
		return preg_match('/https?:|\.(com|ru|pw|pro|nl)\/?($|\W)/', $opmerking) == true;
	}

	/**
	 * @param $response
	 * @return mixed
	 */
	public function checkCaptcha($response) {
		$secret = $_ENV['GOOGLE_CAPTCHA_SECRET'];

		$ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "secret=$secret&response=$response");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		return json_decode(curl_exec($ch), true);
	}
}
