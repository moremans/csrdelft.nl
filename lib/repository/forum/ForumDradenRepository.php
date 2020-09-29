<?php

namespace CsrDelft\repository\forum;

use CsrDelft\common\ContainerFacade;
use CsrDelft\common\CsrException;
use CsrDelft\common\CsrGebruikerException;
use CsrDelft\entity\forum\ForumDeel;
use CsrDelft\entity\forum\ForumDraad;
use CsrDelft\entity\forum\ForumZoeken;
use CsrDelft\repository\AbstractRepository;
use CsrDelft\repository\Paging;
use CsrDelft\service\security\LoginService;
use Doctrine\DBAL\Exception\SyntaxErrorException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 30/03/2017
 * @method ForumDraad|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumDraad|null findOneBy(array $criteria, array $orderBy = null)
 * @method PersistentCollection|ForumDraad[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumDradenRepository extends AbstractRepository implements Paging {
	/**
	 * Mogelijke markeringen voor belangrijke draadjes
	 * @var array
	 */
	public static $belangrijk_opties = array(
		'Plaatje' => array(
			'asterisk_orange' => 'Asterisk',
			'ruby' => 'Robijn',
			'rosette' => 'Rozet'
		),
		'Vlag' => array(
			'flag_red' => 'Rood',
			'flag_orange' => 'Oranje',
			'flag_yellow' => 'Geel',
			'flag_green' => 'Groen',
			'flag_blue' => 'Blauw',
			'flag_purple' => 'Paars',
			'flag_pink' => 'Roze'
		)
	);
	/**
	 * Default ORDER BY
	 * @var string
	 */
	protected $default_order = 'plakkerig DESC, laatst_gewijzigd DESC';
	/**
	 * Huidige pagina
	 * @var int
	 */
	private $pagina;
	/**
	 * Aantal draden per pagina
	 * Gebruik @see ForumDradenRepository::getAantalPerPagina()
	 * @var int|null
	 */
	private $per_pagina;
	/**
	 * Totaal aantal paginas per forumdeel
	 * @var int[]
	 */
	private $aantal_paginas;
	/**
	 * Aantal plakkerige draden
	 * @var int
	 */
	private $aantal_plakkerig;
	/**
	 * @var ForumDradenGelezenRepository
	 */
	private $forumDradenGelezenRepository;

	/**
	 * @var ForumDradenReagerenRepository
	 */
	private $forumDradenReagerenRepository;

	/**
	 * @var ForumDradenVerbergenRepository
	 */
	private $forumDradenVerbergenRepository;

	/**
	 * @var ForumDradenMeldingRepository
	 */
	private $forumDradenMeldingRepository;

	/**
	 * @var ForumPostsRepository
	 */
	private $forumPostsRepository;

	public function __construct(
		ManagerRegistry $registry,
		ForumDradenGelezenRepository $forumDradenGelezenRepository,
		ForumDradenReagerenRepository $forumDradenReagerenRepository,
		ForumDradenVerbergenRepository $forumDradenVerbergenRepository,
		ForumDradenMeldingRepository $forumDradenMeldingRepository,
		ForumPostsRepository $forumPostsRepository
	) {
		parent::__construct($registry, ForumDraad::class);
		$this->pagina = 1;
		$this->aantal_paginas = array();
		$this->aantal_plakkerig = null;

		$this->forumDradenGelezenRepository = $forumDradenGelezenRepository;
		$this->forumDradenReagerenRepository = $forumDradenReagerenRepository;
		$this->forumDradenVerbergenRepository = $forumDradenVerbergenRepository;
		$this->forumDradenMeldingRepository = $forumDradenMeldingRepository;
		$this->forumPostsRepository = $forumPostsRepository;
	}

	/**
	 * @param $id
	 * @return ForumDraad
	 * @throws CsrGebruikerException
	 */
	public function get($id) {
		$draad = $this->find($id);
		if (!$draad) {
			throw new CsrGebruikerException('Forum-onderwerp bestaat niet!');
		}
		return $draad;
	}

	public function getAantalPerPagina() {
		if (!$this->per_pagina) {
			$this->per_pagina = (int)lid_instelling('forum', 'draden_per_pagina');
		}
		return $this->per_pagina;
	}

	public function setAantalPerPagina($aantal) {
		$this->per_pagina = (int)$aantal;
	}

	public function getHuidigePagina() {
		return $this->pagina;
	}

	public function setHuidigePagina($pagina, $forum_id) {
		if (!is_int($pagina) || $pagina < 1) {
			$pagina = 1;
		} elseif ($forum_id !== 0 && $pagina > $this->getAantalPaginas($forum_id)) {
			$pagina = $this->getAantalPaginas($forum_id);
		}
		$this->pagina = $pagina;
	}

	public function getAantalPaginas($forum_id = null) {
		if (!isset($forum_id)) { // recent en zoeken hebben onbeperkte paginas
			return $this->pagina + 1;
		}
		if (!array_key_exists($forum_id, $this->aantal_paginas)) {
			$qb = $this->createQueryBuilder('d');
			$qb->select('count(d.draad_id)');
			$qb->where('d.forum_id = :forum_id and d.wacht_goedkeuring = false and d.verwijderd = false');
			$qb->setParameter('forum_id', $forum_id);
			$this->filterLaatstGewijzigdExtern($qb);

			$aantal = $qb->getQuery()->getSingleScalarResult();

			$this->aantal_paginas[$forum_id] = (int)ceil($aantal / $this->getAantalPerPagina());
		}
		return max(1, $this->aantal_paginas[$forum_id]);
	}

	public function createQueryBuilder($alias, $indexBy = null) {
		return parent::createQueryBuilder($alias, $indexBy)
			->orderBy($alias . '.plakkerig', 'DESC')
			->addOrderBy($alias . '.laatst_gewijzigd', 'DESC');
	}

	public function setLaatstePagina($forum_id) {
		$this->pagina = $this->getAantalPaginas($forum_id);
	}

	public function getPaginaVoorDraad(ForumDraad $draad) {
		if ($draad->plakkerig) {
			return 1;
		}
		if ($this->aantal_plakkerig === null) {
			$qb = $this->createQueryBuilder('d');
			$qb->select('count(d.draad_id)');
			$qb->where('d.forum_id = :forum_id and d.plakkerig = true and d.wacht_goedkeuring = false and d.verwijderd = false');
			$qb->setParameter('forum_id', $draad->forum_id);
			$this->aantal_plakkerig = $qb->getQuery()->getSingleScalarResult();
		}

		$qb = $this->createQueryBuilder('d');
		$qb->select('count(d.draad_id)');
		$qb->where('d.forum_id = :forum_id and d.laatst_gewijzigd >= :laatst_gewijzigd and d.plakkerig = false and d.wacht_goedkeuring = false and d.verwijderd = false');
		$qb->setParameter('forum_id', $draad->forum_id);
		$qb->setParameter('laatst_gewijzigd', $draad->laatst_gewijzigd);

		$count = $this->aantal_plakkerig + $qb->getQuery()->getSingleScalarResult();
		return (int)ceil($count / $this->getAantalPerPagina());
	}

	public function zoeken(ForumZoeken $forumZoeken) {
		$qb = $this->createQueryBuilder('draad');
		// Als er geen spatie in de zoekterm zit, doe dan keyword search met '<zoekterm>*'
		if (strstr($forumZoeken->zoekterm, ' ') == false) {
			$qb->addSelect('MATCH(draad.titel) AGAINST (:query IN BOOLEAN MODE) AS score');
		} else {
			$qb->addSelect('MATCH(draad.titel) AGAINST (:query) AS score');
		}

		$qb->setParameter('query', $forumZoeken->zoekterm);
		$qb->where('draad.wacht_goedkeuring = false and draad.verwijderd = false and draad.laatst_gewijzigd >= :van and draad.laatst_gewijzigd <= :tot');
		$qb->setParameter('van', $forumZoeken->van);
		$qb->setParameter('tot', $forumZoeken->tot);
		$this->filterLaatstGewijzigdExtern($qb, 'draad');
		$qb->orderBy('score', 'DESC');
		$qb->addOrderBy('draad.plakkerig', 'DESC');
		$qb->having('score > 0');
		$qb->setMaxResults($forumZoeken->limit);
		try {
			$results = $qb->getQuery()->getResult();
		} catch (SyntaxErrorException $ex) {
			setMelding('Op deze term kan niet gezocht worden', -1);
			// Syntax error in de MATCH in BOOLEAN MODE
			return [];
		}
		return $results;
	}


	public function getPrullenbakVoorDeel(ForumDeel $deel) {
		return $this->findBy(['forum_id' => $deel->forum_id, 'verwijderd' => true], ['plakkerig' => 'DESC', 'laatst_gewijzigd' => 'DESC']);
	}

	public function getBelangrijkeForumDradenVoorDeel(ForumDeel $deel) {
		$qb = $this->createQueryBuilder('d');
		$qb->where('d.forum_id = :forum_id and d.wacht_goedkeuring = false and d.verwijderd = false and d.belangrijk = true');
		$qb->setParameter('forum_id', $deel->forum_id);

		$this->filterLaatstGewijzigdExtern($qb);

		return $qb->getQuery()->getResult();
	}

	public function getForumDradenVoorDeel(ForumDeel $deel) {
		$qb = $this->createQueryBuilder('d');
		$qb->where('(d.forum_id = :forum_id or d.gedeeld_met = :forum_id) and d.wacht_goedkeuring = false and d.verwijderd = false');
		$qb->setParameter('forum_id', $deel->forum_id);

		$this->filterLaatstGewijzigdExtern($qb);

		$qb->setFirstResult(($this->pagina - 1) * $this->getAantalPerPagina());
		$qb->setMaxResults($this->getAantalPerPagina());

		$paginator = new Paginator($qb);

		return $paginator->getIterator();
	}

	/**
	 * Laad recente (niet) (belangrijke) draadjes.
	 * Eager loading van laatste ForumPost
	 * Check leesrechten van gebruiker.
	 * RSS: use token & return delen.
	 *
	 * @param int|null $aantal
	 * @param boolean|null $belangrijk
	 * @param boolean $rss
	 * @param int $offset
	 * @return ForumDraad[]
	 */
	public function getRecenteForumDraden($aantal, $belangrijk, $rss = false, $offset = 0) {
		if (!is_int($aantal)) {
			$aantal = $this->getAantalPerPagina();
			$pagina = $this->pagina;
			$offset = ($pagina - 1) * $aantal;
		}
		$delenById = ContainerFacade::getContainer()->get(ForumDelenRepository::class)->getForumDelenVoorLid($rss);
		if (count($delenById) < 1) {
			return [];
		}
		$forum_ids = array_keys($delenById);

		$qb = $this->createQueryBuilder('d');
		$qb->orderBy('d.laatst_gewijzigd', 'DESC');
		$qb->setFirstResult($offset);
		$qb->setMaxResults($aantal);
		$qb->where('d.forum_id in (:forum_ids) or d.forum_id in (:forum_ids)');
		$qb->setParameter('forum_ids', $forum_ids);

		$verbergen = $this->forumDradenVerbergenRepository->findBy(['uid' => LoginService::getUid()]);
		$draden_ids = array_keys(group_by_distinct('draad_id', $verbergen));
		if (count($draden_ids) > 0) {
			$qb->andWhere('d.draad_id not in (:draden_ids)');
			$qb->setParameter('draden_ids', $draden_ids);
		}

		$qb->andWhere('d.wacht_goedkeuring = false and d.verwijderd = false');

		if (is_bool($belangrijk)) {
			if ($belangrijk) {
				$qb->andWhere('d.belangrijk is not null');
			} else {
				if (!isset($pagina) || lid_instelling('forum', 'belangrijkBijRecent') === 'nee') {
					$qb->andWhere('d.belangrijk is null');
				}
			}
		}
		$this->filterLaatstGewijzigdExtern($qb);
		$dradenById = group_by_distinct('draad_id', $qb->getQuery()->getResult());
		$count = count($dradenById);
		if ($count > 0) {
			$draden_ids = array_keys($dradenById);
			array_unshift($draden_ids, LoginService::getUid());
		}
		return $dradenById;
	}

	/**
	 * @param array $ids
	 * @return array|ForumDraad[]
	 */
	public function getForumDradenById(array $ids) {
		$count = count($ids);
		if ($count < 1) {
			return array();
		}

		$draden = $this->createQueryBuilder('d')
			->where('d.draad_id in (:ids)')
			->setParameter('ids', $ids)
			->getQuery()->getResult();
		return group_by_distinct('draad_id', $draden);
	}

	public function maakForumDraad($deel, $titel, $wacht_goedkeuring) {
		$draad = new ForumDraad();
		$draad->deel = $deel;
		$draad->gedeeld_met_deel = null;
		$draad->uid = LoginService::getUid();
		$draad->titel = $titel;
		$draad->datum_tijd = date_create_immutable();
		$draad->laatst_gewijzigd = $draad->datum_tijd;
		$draad->laatste_post_id = null;
		$draad->laatste_wijziging_uid = null;
		$draad->gesloten = false;
		$draad->verwijderd = false;
		$draad->wacht_goedkeuring = $wacht_goedkeuring;
		$draad->plakkerig = false;
		$draad->belangrijk = null;
		$draad->eerste_post_plakkerig = false;
		$draad->pagina_per_post = false;
		$this->getEntityManager()->persist($draad);
		$this->getEntityManager()->flush();
		return $draad;
	}

	public function wijzigForumDraad(ForumDraad $draad, $property, $value) {
		if (!property_exists($draad, $property)) {
			throw new CsrException('Property undefined: ' . $property);
		}
		$draad->$property = $value;

		$this->getEntityManager()->persist($draad);
		$this->getEntityManager()->flush();

		if ($property === 'belangrijk') {
			$this->forumDradenVerbergenRepository->toonDraadVoorIedereen([$draad->draad_id]);
		} elseif ($property === 'gesloten') {
			$this->forumDradenMeldingRepository->stopMeldingenVoorIedereen([$draad->draad_id]);
		} elseif ($property === 'verwijderd') {
			$this->forumDradenMeldingRepository->stopMeldingenVoorIedereen([$draad->draad_id]);
			$this->forumDradenVerbergenRepository->toonDraadVoorIedereen([$draad->draad_id]);
			$this->forumDradenGelezenRepository->verwijderDraadGelezen([$draad->draad_id]);
			$this->forumDradenReagerenRepository->verwijderReagerenVoorDraad([$draad->draad_id]);
			$this->forumPostsRepository->verwijderForumPostsVoorDraad($draad);
		}
	}

	public function resetLastPost(ForumDraad $draad) {
		// reset last post
		$last_post = $this->forumPostsRepository->findBy(['draad_id' => $draad->draad_id, 'wacht_goedkeuring' => false, 'verwijderd' => false], ['laatst_gewijzigd' => 'DESC'])[0];
		if ($last_post) {
			$draad->laatste_post_id = $last_post->post_id;
			$draad->laatste_wijziging_uid = $last_post->uid;
			$draad->laatst_gewijzigd = $last_post->laatst_gewijzigd;
		} else {
			$draad->laatste_post_id = null;
			$draad->laatste_wijziging_uid = null;
			$draad->laatst_gewijzigd = null;
			$draad->verwijderd = true;
			setMelding('Enige bericht in draad verwijderd: draad ook verwijderd', 2);
		}
		$this->getEntityManager()->persist($draad);
		$this->getEntityManager()->flush();
	}

	public function update(ForumDraad $draad) {
		try {
			$this->getEntityManager()->persist($draad);
			$this->getEntityManager()->flush();

			return 1;
		} catch (Exception $ex) {
			return 0;
		}
	}

	private function filterLaatstGewijzigdExtern($qb, $alias = 'd') {
		if (!LoginService::mag(P_LOGGED_IN)) {
			$qb->andWhere("({$alias}.gesloten = true and {$alias}.laatst_gewijzigd >= :laatst_gewijzigd_gesloten) or ({$alias}.gesloten = false and {$alias}.laatst_gewijzigd >= :laatst_gewijzigd_open)");
			$qb->setParameter('laatst_gewijzigd_gesloten', date_create_immutable(instelling('forum', 'externen_geentoegang_gesloten')));
			$qb->setParameter('laatst_gewijzigd_open', date_create_immutable(instelling('forum', 'externen_geentoegang_open')));
		}
	}

}
