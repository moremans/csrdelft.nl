<?php

namespace CsrDelft\controller;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\entity\Streeplijst;
use CsrDelft\repository\groepen\LichtingenRepository;
use CsrDelft\repository\groepen\VerticalenRepository;
use CsrDelft\repository\ProfielRepository;
use CsrDelft\repository\StreeplijstRepository;
use CsrDelft\view\renderer\TemplateView;
use CsrDelft\view\streeplijst\StreeplijstForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * StreeplijstController.class.php
 *
 * @author J. de Jong
 *
 * Controller voor streeplijst generator
 */
class StreeplijstController extends AbstractController
{
	/**
	 * @var StreeplijstRepository
	 */
	private $streeplijstRepository;
	/**
	 * @var ProfielRepository
	 */
	private $profielRepository;
	/**
	 * @var VerticalenRepository
	 */
	private $verticalenRepository;

	public function __construct(
		StreeplijstRepository $streeplijstRepository, ProfielRepository $profielRepository, VerticalenRepository $verticalenRepository)
	{
		$this->streeplijstRepository = $streeplijstRepository;
		$this->profielRepository = $profielRepository;
		$this->verticalenRepository = $verticalenRepository;
	}

	/**
	 * @return TemplateView
	 * @Route("/streeplijst", methods={"GET"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function overzicht()
	{
		return view('streeplijst.overzicht', [
			'streeplijstoverzicht' => $this->streeplijstRepository->getAlleStreeplijsten(),
			'huidigestreeplijst' => new Streeplijst(),
			'verticalen' => $this->verticalenRepository->findAll(),
			'jongstelidjaar' => LichtingenRepository::getJongsteLidjaar(),
		]);
	}

	/**
	 * @param Request $request
	 * @return RedirectResponse
	 * @Route("/streeplijst/aanmaken", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function aanmaken(Request $request)
	{
		$inhoud_streeplijst = $request->query->get("inhoud_streeplijst");
		$leden_streeplijst = $request->query->get("leden_streeplijst");
		$naam_streeplijst = $request->query->get("naam_streeplijst");
		$nieuwelijst = $this->streeplijstRepository->nieuw($naam_streeplijst, $leden_streeplijst, $inhoud_streeplijst);
		$manager = $this->getDoctrine()->getManager();
		$manager->persist($nieuwelijst);
		$manager->flush();
		return $this->redirectToRoute('csrdelft_streeplijst_overzicht');
	}

	/**
	 * @param $id
	 * @return StreeplijstForm
	 * @Route("/streeplijst/bewerken/{id}", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function bewerken($id)
	{
		$streeplijst = $this->streeplijstRepository->find($id);
		return view('streeplijst.overzicht', [
			'streeplijstoverzicht' => $this->streeplijstRepository->getAlleStreeplijsten(),
			'huidigestreeplijst' => $streeplijst,
			'verticalen' => $this->verticalenRepository->findAll(),
			'jongstelidjaar' => LichtingenRepository::getJongsteLidjaar(),
		]);
	}

	/**
	 * @param $id
	 * @return RedirectResponse
	 * @Route("/streeplijst/verwijderen/{id}", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function verwijderen($id)
	{
		$streeplijst = $this->streeplijstRepository->find($id);
		$manager = $this->getDoctrine()->getManager();
		$manager->remove($streeplijst);
		$manager->flush();
		return $this->redirectToRoute('csrdelft_streeplijst_overzicht');
	}

	/**
	 * @param Request $request
	 * @return RedirectResponse
	 * @Route("/streeplijst/selectie", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function selectie(Request $request)
	{
		$verticale = $request->request->get('verticale');
		$lichting = $request->request->get('lichting');
		$ledentype = $request->request->get('ledentype');
		$criteria = ['status' => $ledentype];
		if ($verticale && $verticale != 'alle') {
			$criteria['verticale'] = $verticale;
		}
		if ($lichting && $lichting != 'alle') {
			$criteria['lidjaar'] = $lichting;
		}
		$profielen = $this->profielRepository->findBy($criteria);
		$namen = [];
		$naamopmaak = $request->request->get('naamopmaak');
		foreach ($profielen as $profiel) {
			$namen[] = $profiel->getNaam($naamopmaak);
		}
		$goederen = $request->request->get('streepbareUnits');
		$opmaakInhoud = $request->request->get('opmaakInhoud');

		if ($opmaakInhoud) {

			sort($goederen);
		}
		$stringGoederen = null;

		if ($goederen != null) {

			$stringGoederen = implode("; ", $goederen);
		}

		$opmaakSorteringWantCasperVindDatMooierKlinken = $request->request->get('opmaakabc');

		if ($opmaakSorteringWantCasperVindDatMooierKlinken) {
			sort($namen);
		}
		$stringNamen = implode("; ", $namen);

		$streeplijst = new Streeplijst();
		$streeplijst->leden_streeplijst = $stringNamen;
		$streeplijst->inhoud_streeplijst = $stringGoederen;

		return view('streeplijst.overzicht', [
			'streeplijstoverzicht' => $this->streeplijstRepository->getAlleStreeplijsten(),
			'huidigestreeplijst' => $streeplijst,
			'verticalen' => $this->verticalenRepository->findAll(),
			'jongstelidjaar' => LichtingenRepository::getJongsteLidjaar(),
		]);
	}

	/**
	 * @param $id
	 * @return TemplateView
	 * @Route("/streeplijst/genereren/{id}", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function genereren($id)
	{
		$streeplijst = $this->streeplijstRepository->find($id);

		return view('streeplijst.streeplijst', [
			'streeplijst' => $streeplijst
		]);
	}

	/**
	 * @param Request $request
	 * @return TemplateView
	 * @Route("/streeplijst/genererenZonderId", methods={"GET", "POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function genererenZonderId(Request $request)
	{
		$naam_streeplijst = $request->query->get("naam_streeplijst");
		$leden_streeplijst = $request->query->get("leden_streeplijst");
		$inhoud_streeplijst = $request->query->get("inhoud_streeplijst");
		$nieuwelijst = $this->streeplijstRepository->nieuw($naam_streeplijst, $leden_streeplijst, $inhoud_streeplijst);

		return view('streeplijst.streeplijst', [
			'streeplijst' => $nieuwelijst
		]);
	}
}
