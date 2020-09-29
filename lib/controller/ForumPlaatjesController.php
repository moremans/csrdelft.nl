<?php


namespace CsrDelft\controller;


use CsrDelft\common\Annotation\Auth;
use CsrDelft\repository\ForumPlaatjeRepository;
use CsrDelft\service\security\LoginService;
use CsrDelft\view\plaatjes\PlaatjesUploadModalForm;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ForumPlaatjesController extends AbstractController {
	/** @var ForumPlaatjeRepository  */
	private $forumPlaatjeRepository;

	public function __construct(ForumPlaatjeRepository $forumPlaatjeRepository) {
		$this->forumPlaatjeRepository = $forumPlaatjeRepository;
	}

	/**
	 * @return PlaatjesUploadModalForm|Response
	 * @Route("/forum/plaatjes/upload", methods={"GET","POST"})
	 * @Auth(P_LOGGED_IN)
	 */
	public function upload() {
		$form = new PlaatjesUploadModalForm();
		if ($form->isPosted()) {
			$plaatje = $this->forumPlaatjeRepository->fromUploader($form->uploader, $this->getUid());
			return $this->render('forum/partial/insert_plaatje.html.twig', ['plaatje' => $plaatje]);
		} else {
			return $form;
		}
	}

	/**
	 * @param $id
	 * @param bool $resized
	 * @return BinaryFileResponse
	 * @Route("/forum/plaatjes/bekijken/{id}", methods={"GET"}, requirements={"id"="[a-zA-Z0-9]*"})
	 * @Route("/forum/plaatjes/bekijken/{id}/resized", methods={"GET"}, requirements={"id"="[a-zA-Z0-9]*"}, defaults={"resized"=true})
	 * @Auth(P_LOGGED_IN)
	 */
	public function bekijken($id, $resized=false) {
		$plaatje = $this->forumPlaatjeRepository->getByKey($id);
		if (!$plaatje) {
			throw new NotFoundHttpException();
		}
		$image = $plaatje->getAfbeelding($resized);
		if (!$image->exists()) {
			throw new NotFoundHttpException();
		}
		$response = new BinaryFileResponse($image->getFullPath());
		$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
		return $response;
	}
}
