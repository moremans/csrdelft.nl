<?php

namespace CsrDelft\view;

use CsrDelft\repository\agenda\AgendaRepository;
use CsrDelft\repository\forum\ForumDradenRepository;
use CsrDelft\repository\forum\ForumPostsRepository;
use CsrDelft\repository\fotoalbum\FotoAlbumRepository;
use CsrDelft\repository\groepen\LichtingenRepository;
use CsrDelft\repository\instellingen\LidInstellingenRepository;
use CsrDelft\repository\MenuItemRepository;
use CsrDelft\service\security\LoginService;
use CsrDelft\service\VerjaardagenService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * @author C.S.R. Delft <pubcie@csrdelft.nl>
 *
 */
class Zijbalk {
	/**
	 * @var Environment
	 */
	private $twig;
	/**
	 * @var MenuItemRepository
	 */
	private $menuItemRepository;
	/**
	 * @var ForumDradenRepository
	 */
	private $forumDradenRepository;
	/**
	 * @var AgendaRepository
	 */
	private $agendaRepository;
	/**
	 * @var ForumPostsRepository
	 */
	private $forumPostsRepository;
	/**
	 * @var FotoAlbumRepository
	 */
	private $fotoAlbumRepository;
	/**
	 * @var VerjaardagenService
	 */
	private $verjaardagenService;
	/**
	 * @var LidInstellingenRepository
	 */
	private $lidInstellingenRepository;
	/**
	 * @var SessionInterface
	 */
	private $session;

	public function __construct(
		SessionInterface $session,
		Environment $twig,
		MenuItemRepository $menuItemRepository,
		ForumDradenRepository $forumDradenRepository,
		AgendaRepository $agendaRepository,
		ForumPostsRepository $forumPostsRepository,
		FotoAlbumRepository $fotoAlbumRepository,
		VerjaardagenService $verjaardagenService,
		LidInstellingenRepository $lidInstellingenRepository
	) {
		$this->twig = $twig;
		$this->menuItemRepository = $menuItemRepository;
		$this->forumDradenRepository = $forumDradenRepository;
		$this->agendaRepository = $agendaRepository;
		$this->forumPostsRepository = $forumPostsRepository;
		$this->fotoAlbumRepository = $fotoAlbumRepository;
		$this->verjaardagenService = $verjaardagenService;
		$this->lidInstellingenRepository = $lidInstellingenRepository;
		$this->session = $session;
	}

	/**
	 * @return string[]
	 */
	public function getZijbalk() {
		return array_filter_empty([
			$this->blockIsHetAl(),
			$this->blockFavorieten(),
			$this->blockSponsors(),
			$this->blockAgenda(),
			$this->blockForumNieuwsteBelangrijkBerichten(),
			$this->blockForumNieuwsteBerichten(),
			$this->blockForumZelfgepost(),
			$this->blockNieuwsteFotoAlbum(),
			$this->blockKomendeVerjaardagen(),
		]);
	}

	private function blockIsHetAl() {
		// Is het al...
		if (lid_instelling('zijbalk', 'ishetal') != 'niet weergeven') {
			return (new IsHetAlView($this->lidInstellingenRepository, $this->session, $this->agendaRepository, lid_instelling('zijbalk', 'ishetal')))->toString();
		}

		return null;
	}

	private function blockFavorieten() {
		// Favorieten menu
		if (LoginService::mag(P_LOGGED_IN) && lid_instelling('zijbalk', 'favorieten') == 'ja') {
			$menu = $this->menuItemRepository->getMenu(LoginService::getUid());
			$menu->tekst = 'Favorieten';
			return $this->twig->render('menu/block.html.twig', ['root' => $menu]);
		}

		return null;
	}

	private function blockSponsors() {
		// Sponsors
		if (LoginService::mag(P_LOGGED_IN)) {
			$sponsor_menu = $this->menuItemRepository->getMenu("sponsors");
			if ($sponsor_menu) {
				$sponsor_menu->tekst = 'Mogelijkheden';
				return $this->twig->render('menu/block.html.twig', ['root' => $sponsor_menu]);
			}
		}

		return null;
	}

	private function blockAgenda() {
		// Agenda
		if (LoginService::mag(P_AGENDA_READ) && lid_instelling('zijbalk', 'agendaweken') > 0 && lid_instelling('zijbalk', 'agenda_max') > 0) {
			$aantalWeken = lid_instelling('zijbalk', 'agendaweken');
			$items = $this->agendaRepository->getAllAgendeerbaar(date_create_immutable(), date_create_immutable('next saturday + ' . $aantalWeken . ' weeks'), false, true);
			if (count($items) > lid_instelling('zijbalk', 'agenda_max')) {
				$items = array_slice($items, 0, lid_instelling('zijbalk', 'agenda_max'));
			}
			return $this->twig->render('agenda/zijbalk.html.twig', ['items' => $items]);
		}

		return null;
	}

	private function blockForumNieuwsteBelangrijkBerichten() {
		// Nieuwste belangrijke forumberichten
		if (lid_instelling('zijbalk', 'forum_belangrijk') > 0) {
			return $this->twig->render('forum/partial/draad_zijbalk.html.twig', [
				'draden' => $this->forumDradenRepository->getRecenteForumDraden((int)lid_instelling('zijbalk', 'forum_belangrijk'), true),
				'aantalWacht' => $this->forumPostsRepository->getAantalWachtOpGoedkeuring(),
				'belangrijk' => true
			]);
		}

		return null;
	}

	private function blockForumNieuwsteBerichten() {
		// Nieuwste forumberichten
		if (lid_instelling('zijbalk', 'forum') > 0) {
			$belangrijk = (lid_instelling('zijbalk', 'forum_belangrijk') > 0 ? false : null);
			return $this->twig->render('forum/partial/draad_zijbalk.html.twig', [
				'draden' => $this->forumDradenRepository->getRecenteForumDraden((int)lid_instelling('zijbalk', 'forum'), $belangrijk),
				'aantalWacht' => $this->forumPostsRepository->getAantalWachtOpGoedkeuring(),
				'belangrijk' => $belangrijk
			]);
		}

		return null;
	}

	private function blockForumZelfgepost() {
		// Zelfgeposte forumberichten
		if (lid_instelling('zijbalk', 'forum_zelf') > 0) {
			$posts = $this->forumPostsRepository->getRecenteForumPostsVanLid(LoginService::getUid(), (int)lid_instelling('zijbalk', 'forum_zelf'), true);
			return $this->twig->render('forum/partial/post_zijbalk.html.twig', ['posts' => $posts]);
		}

		return null;
	}

	private function blockNieuwsteFotoAlbum() {
		// Nieuwste fotoalbum
		if (lid_instelling('zijbalk', 'fotoalbum') == 'ja') {
			$album = $this->fotoAlbumRepository->getMostRecentFotoAlbum();
			if ($album !== null) {
				return $this->twig->render('fotoalbum/zijbalk.html.twig', ['album' => $album, 'jaargang' => LichtingenRepository::getHuidigeJaargang()]);
			}
		}

		return null;
	}

	private function blockKomendeVerjaardagen() {
		// Komende verjaardagen
		if (LoginService::mag(P_LOGGED_IN) && lid_instelling('zijbalk', 'verjaardagen') > 0) {
			return $this->twig->render('verjaardagen/komende.html.twig', [
				'verjaardagen' => $this->verjaardagenService->getKomende((int)lid_instelling('zijbalk', 'verjaardagen')),
				'toonpasfotos' => lid_instelling('zijbalk', 'verjaardagen_pasfotos') == 'ja',
			]);
		}

		return null;
	}

}
