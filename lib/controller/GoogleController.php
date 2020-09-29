<?php

namespace CsrDelft\controller;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\common\CsrException;
use CsrDelft\entity\GoogleToken;
use CsrDelft\repository\GoogleTokenRepository;
use CsrDelft\service\GoogleSync;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class GoogleController.
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 */
class GoogleController extends AbstractController {
	/**
	 * @var GoogleTokenRepository
	 */
	private $googleTokenModel;

	public function __construct(GoogleTokenRepository $googleTokenModel) {
		$this->googleTokenModel = $googleTokenModel;
	}

	/**
	 * @param Request $request
	 * @return RedirectResponse
	 * @Route("/google/callback", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function callback(Request $request) {
		$state = $request->query->get('state', null);
		$code = $request->query->get('code', null);
		$error = $request->query->get('error',null);
		if ($code) {
			$client = GoogleSync::createGoogleCLient();
			$client->fetchAccessTokenWithAuthCode($code);

			$existingToken = $this->googleTokenModel->findOneBy(['uid' => $this->getUid()]);
			$manager = $this->getDoctrine()->getManager();

			if (!$existingToken) {
				$googleToken = new GoogleToken();
				$googleToken->uid = $this->getUid();
				$googleToken->token = $client->getRefreshToken();
				$manager->persist($googleToken);
			} else {
				$existingToken->token = $client->getRefreshToken();
			}

			$manager->flush();

			return $this->csrRedirect(urldecode($state));
		}

		if ($error) {
			setMelding('Verbinding met Google niet geaccepteerd', 2);
			$state = substr(strstr($state, 'addToGoogleContacts', true), 0, -1);

			return $this->csrRedirect($state);
		}

		throw new CsrException('Geen error en geen code van Google gekregen.');
	}
}
