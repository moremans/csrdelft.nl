<?php

namespace CsrDelft\controller;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\security\enum\AuthenticationMethod;
use CsrDelft\repository\CmsPaginaRepository;
use CsrDelft\repository\security\AccountRepository;
use CsrDelft\service\AccessService;
use CsrDelft\service\security\LoginService;
use CsrDelft\view\login\AccountForm;
use CsrDelft\view\login\UpdateLoginForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 28/07/2019
 */
class AccountController extends AbstractController {
	/**
	 * @var CmsPaginaRepository
	 */
	private $cmsPaginaRepository;
	/**
	 * @var AccountRepository
	 */
	private $accountRepository;
	/**
	 * @var LoginService
	 */
	private $loginService;
	/**
	 * @var AccessService
	 */
	private $accessService;

	public function __construct(
		AccessService $accessService,
		AccountRepository $accountRepository,
		CmsPaginaRepository $cmsPaginaRepository,
		LoginService $loginService
	) {
		$this->accessService = $accessService;
		$this->accountRepository = $accountRepository;
		$this->cmsPaginaRepository = $cmsPaginaRepository;
		$this->loginService = $loginService;
	}

	/**
	 * @param null $uid
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 * @Route("/account/{uid}/aanmaken", methods={"GET", "POST"}, requirements={"uid": ".{4}"})
	 * @Auth(P_ADMIN)
	 */
	public function aanmaken($uid = null) {
		if (!LoginService::mag(P_ADMIN)) {
			throw $this->createAccessDeniedException();
		}

		if ($uid == null) {
			$account = $this->getUser();
		} else {
			$account = $this->accountRepository->find($uid);
		}

		if ($account) {
			setMelding('Account bestaat al', 0);
		} else {
			$account = $this->accountRepository->maakAccount($uid);
			if ($account) {
				setMelding('Account succesvol aangemaakt', 1);
			} else {
				throw new CsrGebruikerException('Account aanmaken gefaald');
			}
		}
		return $this->redirectToRoute('csrdelft_account_bewerken', ['uid' => $uid]);
	}

	/**
	 * @param null $uid
	 * @return Response
	 * @Route("/account/{uid}/bewerken", methods={"GET", "POST"}, requirements={"uid": ".{4}"})
	 * @Route("/account/bewerken", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function bewerken(Request $request, $uid = null) {
		if ($uid == null) {
			$uid = $this->getUid();
		}
		if ($uid !== $this->getUid() && !LoginService::mag(P_ADMIN)) {
			throw $this->createAccessDeniedException();
		}
		$account = $this->accountRepository->get($uid);
		if (!$account) {
			setMelding('Account bestaat niet', -1);
			throw $this->createAccessDeniedException();
		}
		if ($this->loginService->getAuthenticationMethod() !== AuthenticationMethod::recent_password_login) {
			$action = $this->generateUrl('csrdelft_account_bewerken', ['uid' => $uid]);
			$form = new UpdateLoginForm($action);

			// Reset loginmoment naar nu als de gebruiker zijn wachtwoord geeft.
			if ($form->validate() && $this->accountRepository->controleerWachtwoord($account, $form->getValues()['pass'])) {
				$this->loginService->setRecentLoginToken();
			} else {
				setMelding('U bent niet recent ingelogd, vul daarom uw wachtwoord in om uw account te wijzigen.', 2);
				return $this->render('default.html.twig', ['content' => new UpdateLoginForm($action)]);
			}
		}
		if (!$this->accessService->mag($account, P_LOGGED_IN)) {
			setMelding('Account mag niet inloggen', 2);
		}
		$form = $this->createFormulier(AccountForm::class, $account, [
			'action' => $this->generateUrl('csrdelft_account_bewerken', ['uid' => $account->uid])
		]);
		$form->handleRequest($request);
		if ($form->validate()) {
			if ($account->username == '') {
				$account->username = $account->uid;
			}
			// username, email & wachtwoord opslaan
			$pass_plain = $account->pass_plain;
			$this->accountRepository->wijzigWachtwoord($account, $pass_plain);
			setMelding('Inloggegevens wijzigen geslaagd', 1);
		}
		$account->eraseCredentials();
		return $this->render('default.html.twig', ['content' => $form->createView()]);
	}

	/**
	 * @return Response
	 * @Route("/account/{uid}/aanvragen", methods={"GET", "POST"}, requirements={"uid": ".{4}"})
	 * @Auth(P_PUBLIC)
	 */
	public function aanvragen() {
		return $this->render('default.html.twig', ['content' => $this->cmsPaginaRepository->find('accountaanvragen')]);
	}

	/**
	 * @param null $uid
	 * @return JsonResponse
	 * @Route("/account/{uid}/verwijderen", methods={"POST"}, requirements={"uid": ".{4}"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function verwijderen($uid = null) {
		if ($uid == null) {
			$uid = $this->getUid();
		}
		if ($uid !== $this->getUid() && !LoginService::mag(P_ADMIN)) {
			throw $this->createAccessDeniedException();
		}
		$account = $this->accountRepository->get($uid);
		if (!$account) {
			setMelding('Account bestaat niet', -1);
		} else {
			$result = $this->accountRepository->delete($account);
			if ($result === 1) {
				setMelding('Account succesvol verwijderd', 1);
			} else {
				setMelding('Account verwijderen mislukt', -1);
			}
		}
		return new JsonResponse('/profiel/' . $uid); // redirect
	}
}
