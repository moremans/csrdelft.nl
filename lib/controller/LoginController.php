<?php

namespace CsrDelft\controller;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\repository\security\RememberLoginRepository;
use CsrDelft\service\security\LoginService;
use CsrDelft\service\security\SuService;
use CsrDelft\view\login\LoginForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * LoginController.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Controller van de agenda.
 */
class LoginController extends AbstractController {
	use TargetPathTrait;

	/**
	 * @var LoginService
	 */
	private $loginService;
	/**
	 * @var RememberLoginRepository
	 */
	private $rememberLoginRepository;
	/**
	 * @var SuService
	 */
	private $suService;

	public function __construct(LoginService $loginService, SuService $suService, RememberLoginRepository $rememberLoginRepository) {
		$this->rememberLoginRepository = $rememberLoginRepository;
		$this->loginService = $loginService;
		$this->suService = $suService;
	}

	/**
	 * @param Request $request
	 * @param AuthenticationUtils $authenticationUtils
	 * @return Response
	 * @Route("/login", methods={"GET"})
	 * @Auth(P_PUBLIC)
	 */
	public function loginForm(Request $request, AuthenticationUtils $authenticationUtils) {
		if ($this->getUser()) {
			return $this->redirectToRoute('default');
		}

		$targetPath = $request->query->get('_target_path');
		if ($targetPath) {
			$this->saveTargetPath($request->getSession(), 'main', $targetPath);
		}

		$error = $authenticationUtils->getLastAuthenticationError();
		$userName = $authenticationUtils->getLastUsername();

		$response = new Response(view('layout-extern.login', ['loginForm' => new LoginForm($userName, $error)]));

		// Als er geredirect wordt, stuur dan een forbidden status
		if ($targetPath) {
			$response->setStatusCode(Response::HTTP_FORBIDDEN);
		}

		return $response;
	}

	/**
	 * @Route("/login_check", name="app_login_check", methods={"POST"})
	 * @Auth(P_PUBLIC)
	 */
	public function login_check() {
		throw new \LogicException('Deze route wordt opgevangen door de firewall, zie security.firewalls.main.form_login.check_path in config/packages/security.yaml');
	}

	/**
	 * @Route("/logout", name="app_logout")
	 * @Auth(P_PUBLIC)
	 */
	public function logout() {
		throw new \LogicException('Deze route wordt opgevangen door de firewall, zie security.firewalls.main.logout.path config/packages/security.yaml');
	}
}
