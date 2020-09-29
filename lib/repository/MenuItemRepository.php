<?php

namespace CsrDelft\repository;

use CsrDelft\common\ContainerFacade;
use CsrDelft\entity\documenten\DocumentCategorie;
use CsrDelft\entity\forum\ForumCategorie;
use CsrDelft\entity\MenuItem;
use CsrDelft\repository\documenten\DocumentCategorieRepository;
use CsrDelft\repository\forum\ForumCategorieRepository;
use CsrDelft\service\security\LoginService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * @method MenuItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method MenuItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method MenuItem[]    findAll()
 * @method MenuItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuItemRepository extends AbstractRepository {
	/**
	 * @var CacheInterface
	 */
	private $cache;

	public function __construct(ManagerRegistry $registry, CacheInterface $cache) {
		parent::__construct($registry, MenuItem::class);
		$this->cache = $cache;
	}

	/**
	 * Get menu for viewing.
	 * Use 2 levels of caching.
	 *
	 * @param string $naam
	 * @return MenuItem root
	 */
	public function getMenu($naam) {
		if (empty($naam)) {
			return null;
		}

		return $this->cache->get($this->createCacheKey($naam), function () use ($naam) {
			try {
				$root = $this->getMenuRoot($naam);

				if ($root == null) {
					return null;
				}

				$this->getExtendedTree($root);

				// Voorkom dat extendedTree updates doorvoert
				$this->_em->clear(MenuItem::class);

				return $root;
			} catch (EntityNotFoundException $ex) {
				return null;
			}
		});
	}

	private function createCacheKey($naam) {
		return 'stek.menu.' . urlencode($naam);
	}

	/**
	 * @param string $naam
	 *
	 * @return MenuItem|null
	 */
	public function getMenuRoot($naam) {
		return $this->findOneBy(['parent' => null, 'tekst' => $naam]);
	}

	/**
	 * Voeg forum-categorien, forum-delen, documenten-categorien en verticalen toe aan menu.
	 * Deze komen in memcache terecht.
	 *
	 * @param MenuItem $parent
	 * @return MenuItem
	 */
	private function getExtendedTree(MenuItem $parent) {
		if ($parent->children)
			foreach ($parent->children as $child) {
				$this->getExtendedTree($child);
			}
		// append additional children
		switch ($parent->tekst) {

			case 'Forum':
				foreach (ContainerFacade::getContainer()->get(ForumCategorieRepository::class)->findAll() as $categorie) {
					/** @var ForumCategorie $categorie */
					$item = $this->nieuw($parent);
					$item->item_id = -$categorie->categorie_id; // nodig voor getParent()
					$item->rechten_bekijken = $categorie->rechten_lezen;
					$item->link = '/forum#' . $categorie->categorie_id;
					$item->tekst = $categorie->titel;
					$parent->children[] = $item;

					foreach ($categorie->forum_delen as $deel) {
						$subitem = $this->nieuw($item);
						$subitem->rechten_bekijken = $deel->rechten_lezen;
						$subitem->link = '/forum/deel/' . $deel->forum_id;
						$subitem->tekst = $deel->titel;
						$item->children[] = $subitem;
					}
				}
				foreach ($this->getMenu('remotefora')->children as $remotecat) {
					$parent->children[] = $remotecat;
				}
				break;

			case 'Documenten':
				$overig = false;
				$documentCategorieRepository = ContainerFacade::getContainer()->get(DocumentCategorieRepository::class);
				$categorien = $documentCategorieRepository->findAll();
				foreach ($categorien as $categorie) {
					/** @var DocumentCategorie $categorie */
					$item = $this->nieuw($parent);
					$item->rechten_bekijken = $categorie->leesrechten;
					$item->link = '/documenten/categorie/' . $categorie->id;
					$item->tekst = $categorie->naam;
					if (!$overig AND $item->tekst == 'Overig') {
						$overig = $item;
					} else {
						$parent->children[] = $item;
					}
				}
				if ($overig) {
					$parent->children[] = $overig;
				}
				break;
		}
		return $parent;
	}

	/**
	 * @param MenuItem $parent
	 *
	 * @return MenuItem
	 */
	public function nieuw($parent) {
		$item = new MenuItem();
		$item->parent = $parent;
		$item->volgorde = 0;
		$item->rechten_bekijken = LoginService::getUid();
		$item->zichtbaar = true;
		return $item;
	}

	/**
	 * Flatten tree structure.
	 *
	 * @param MenuItem $root
	 * @return MenuItem[]
	 */
	public function flattenMenu(MenuItem $root) {
		return $this->cache->get($this->createFlatCacheKey($root->tekst), function () use ($root) {
			return $this->_flattenMenu($root);
		});
	}

	private function createFlatCacheKey($naam) {
		return 'stek.menu-flat.' . urlencode($naam);
	}

	private function _flattenMenu(MenuItem $root) {
		$list = [$root];

		if ($root->children) {
			foreach ($root->children as $child) {
				$list[] = $child;
				foreach ($this->_flattenMenu($child) as $subChild) {
					$list[] = $subChild;
				}
			}
		}
		return $list;

	}

	/**
	 * Lijst van alle menu roots om te beheren.
	 *
	 * @return MenuItem[]|false
	 */
	public function getMenuBeheerLijst() {
		if (LoginService::mag(P_ADMIN)) {
			return $this->findBy(['parent' => null]);
		} else {
			return false;
		}
	}

	/**
	 * Get menu item by id (cached).
	 *
	 * @param int $id
	 * @return MenuItem|false
	 */
	public function getMenuItem($id) {
		return $this->find($id);
	}

	/**
	 * @param MenuItem $item
	 *
	 * @return int
	 */
	public function removeMenuItem(MenuItem $item) {
		$manager = $this->getEntityManager();
		$manager->beginTransaction();

		$count = 0;

		try {
			if ($item->children) {
				foreach ($item->children as $child) {
					$child->parent = $item->parent;
					$count++;
				}
			}

			$this->deleteItemFromCache($item);

			$manager->remove($item);
			$manager->flush();

			$manager->commit();
		} catch (ORMException $exception) {
			$manager->rollback();
		}

		return $count;
	}

	/**
	 * @param MenuItem[] $breadcrumbs
	 * @return string
	 */
	public function renderBreadcrumbs($breadcrumbs) {
		if (empty($breadcrumbs)) {
			return '';
		}

		$html = '<ol class="breadcrumb">';
		foreach ($breadcrumbs as $k => $breadcrumb) {
			if (is_string($breadcrumb)) {
				$breadcrumb = (object)['link' => $k, 'tekst' => $breadcrumb];
			}

			if (startsWith($k, '-') || $k == array_key_last($breadcrumbs)) {
				$html .= $this->renderBreadcrumb($breadcrumb, true);
			} else {
				$html .= $this->renderBreadcrumb($breadcrumb, false);
			}
		}
		$html .= '</ol>';

		return $html;
	}

	/**
	 * @param MenuItem $breadcrumb
	 * @param $active
	 * @return string
	 */
	protected function renderBreadcrumb($breadcrumb, $active) {
		$tekst = $breadcrumb->tekst;

		if ($tekst == 'main') {
			$tekst = '<i class="fa fa-home"></i>';
		}

		if ($active) {
			return '<li class="breadcrumb-item active">' . $tekst . '</li>';
		} else {
			return '<li class="breadcrumb-item"><a href="' . $breadcrumb->link . '">' . $tekst . '</a></li>';
		}
	}

	/**
	 * Haal de breadcrumbs op voor een link.
	 *
	 * @param $link
	 * @return MenuItem[]
	 */
	public function getBreadcrumbs($link) {
		if ($link == '/') {
			return [
				'/' => 'main',
				'' => 'Vereniging van christenstudenten',
			];
		}

		$items = $this->findBy(['link' => $link, 'zichtbaar' => $link]);

		foreach ($items as $item) {
			if ($item->magBekijken()) {
				$breadcrumbs = [$item];

				do {
					$breadcrumbs[] = $item;
				} while ($item = $item->parent);

				return array_reverse($breadcrumbs);
			}
		}

		return [];
	}

	/**
	 * Gooit ook de cache leeg.
	 *
	 * @param MenuItem $item
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function persist(MenuItem $item) {
		$this->getEntityManager()->persist($item);
		$this->getEntityManager()->flush();

		$this->deleteItemFromCache($item);
	}

	public function deleteItemFromCache(MenuItem $item) {
		do {
			$this->cache->delete($this->createCacheKey($item->tekst));
			$this->cache->delete($this->createFlatCacheKey($item->tekst));
		} while ($item = $item->parent);
	}

	/**
	 * @param MenuItem $item
	 *
	 * @return MenuItem
	 */
	public function getRoot(MenuItem $item) {
		if (!$item->parent) {
			return $item;
		}
		return $this->getRoot($item->parent);
	}

	public function getSuggesties($query) {
		return $this->createQueryBuilder('menuItem')
			->where('menuItem.tekst like :query or menuItem.link like :query')
			->setParameter('query', sql_contains($query))
			->setMaxResults(20)
			->getQuery()->getResult();
	}
}
