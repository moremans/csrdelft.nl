<?php

namespace CsrDelft\controller\api;

use CsrDelft\common\Annotation\Auth;
use CsrDelft\repository\forum\ForumDradenGelezenRepository;
use CsrDelft\repository\forum\ForumDradenRepository;
use CsrDelft\repository\forum\ForumPostsRepository;
use CsrDelft\repository\ProfielRepository;
use CsrDelft\view\bbcode\CsrBB;
use Exception;
use Jacwright\RestServer\RestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiForumController {
	private $forumDradenRepository;
	private $forumPostsRepository;
	/**
	 * @var ForumDradenGelezenRepository
	 */
	private $forumDradenGelezenRepository;

	public function __construct(ForumDradenGelezenRepository $forumDradenGelezenRepository, ForumPostsRepository $forumPostsRepository, ForumDradenRepository $forumDradenRepository) {
		$this->forumDradenGelezenRepository = $forumDradenGelezenRepository;
		$this->forumPostsRepository = $forumPostsRepository;
		$this->forumDradenRepository = $forumDradenRepository;
	}

	/**
	 * @Route("/API/2.0/forum/recent", methods={"GET"})
	 * @Auth(P_OUDLEDEN_READ)
	 * @return JsonResponse
	 */
	public function getRecent() {
		$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0;
		$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;

		$draden = $this->forumDradenRepository->getRecenteForumDraden($limit, null, false, $offset);

		foreach ($draden as $draad) {
			$draad->ongelezen = $draad->getAantalOngelezenPosts();
			$draad->laatste_post = $this->forumPostsRepository->get($draad->laatste_post_id);
			$draad->laatste_wijziging_naam = ProfielRepository::getNaam($draad->laatste_wijziging_uid, 'civitas');
		}

		return new JsonResponse(array('data' => array_values($draden)));
	}

	/**
	 * @Route("/API/2.0/forum/onderwerp/{id}", methods={"GET"})
	 * @Auth(P_OUDLEDEN_READ)
	 * @param int offset
	 * @param int limit
	 * @return JsonResponse
	 */
	public function getOnderwerp($id) {
		$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0;
		$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;

		try {
			$draad = $this->forumDradenRepository->get((int)$id);
		} catch (Exception $e) {
			throw new RestException(404);
		}

		if (!$draad->magLezen()) {
			throw new RestException(403);
		}

		$this->forumDradenGelezenRepository->setWanneerGelezenDoorLid($draad, date_create_immutable());

		$posts = $this->forumPostsRepository->findBy(['draad_id' => $id, 'wacht_goedkeuring' => false, 'verwijderd' => false], ['datum_tijd' => 'DESC'], $limit, $offset);

		// Most recent first
		$posts = array_reverse($posts);

		foreach ($posts as $post) {
			$post->uid_naam = ProfielRepository::getNaam($post->uid, 'civitas');
			$post->tekst = CsrBB::parseLight($post->tekst);
		}

		return new JsonResponse(array('data' => $posts));
	}

}
