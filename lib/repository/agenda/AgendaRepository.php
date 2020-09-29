<?php

namespace CsrDelft\repository\agenda;

use CsrDelft\entity\agenda\AgendaItem;
use CsrDelft\entity\agenda\AgendaVerbergen;
use CsrDelft\entity\agenda\Agendeerbaar;
use CsrDelft\entity\groepen\Activiteit;
use CsrDelft\entity\groepen\enum\ActiviteitSoort;
use CsrDelft\entity\security\enum\AccessAction;
use CsrDelft\entity\security\enum\AuthenticationMethod;
use CsrDelft\repository\AbstractRepository;
use CsrDelft\repository\corvee\CorveeTakenRepository;
use CsrDelft\repository\groepen\ActiviteitenRepository;
use CsrDelft\repository\maalcie\MaaltijdenRepository;
use CsrDelft\service\security\LoginService;
use CsrDelft\service\VerjaardagenService;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * @author C.S.R. Delft <pubcie@csrdelft.nl>
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * De Agenda bevat alle Agendeerbare objecten die voorkomen in de webstek.
 *
 * @method AgendaItem find($id, $lockMode = null, $lockVersion = null)
 * @method AgendaItem[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgendaRepository extends AbstractRepository {
	/**
	 * @var AgendaVerbergenRepository
	 */
	private $agendaVerbergenRepository;
	/**
	 * @var ActiviteitenRepository
	 */
	private $activiteitenRepository;
	/**
	 * @var CorveeTakenRepository
	 */
	private $corveeTakenRepository;
	/**
	 * @var MaaltijdenRepository
	 */
	private $maaltijdenRepository;
	/**
	 * @var VerjaardagenService
	 */
	private $verjaardagenService;
	/**
	 * @var Security
	 */
	private $security;

	public function __construct(
		ManagerRegistry $registry,
		Security $security,
		AgendaVerbergenRepository $agendaVerbergenRepository,
		ActiviteitenRepository $activiteitenRepository,
		CorveeTakenRepository $corveeTakenRepository,
		MaaltijdenRepository $maaltijdenRepository,
		VerjaardagenService $verjaardagenService
	) {
		parent::__construct($registry, AgendaItem::class);

		$this->agendaVerbergenRepository = $agendaVerbergenRepository;
		$this->activiteitenRepository = $activiteitenRepository;
		$this->corveeTakenRepository = $corveeTakenRepository;
		$this->maaltijdenRepository = $maaltijdenRepository;
		$this->verjaardagenService = $verjaardagenService;
		$this->security = $security;
	}

	/**
	 * Vergelijkt twee Agendeerbaars op beginMoment t.b.v. sorteren.
	 * @param Agendeerbaar $foo
	 * @param Agendeerbaar $bar
	 * @return int
	 */
	public static function vergelijkAgendeerbaars(Agendeerbaar $foo, Agendeerbaar $bar) {
		$a = $foo->getBeginMoment();
		$b = $bar->getBeginMoment();
		if ($a > $b) {
			return 1;
		} elseif ($a < $b) {
			return -1;
		} else {
			return 0;
		}
	}

	/**
	 * @param $itemId
	 * @return AgendaItem|null
	 */
	public function getAgendaItem($itemId) {
		return $this->find($itemId);
	}

	public function getICalendarItems() {
		return $this->filterVerborgen($this->getAllAgendeerbaar(date_create_immutable(instelling('agenda', 'ical_from')), date_create_immutable(instelling('agenda', 'ical_to')), true));
	}

	public function filterVerborgen(array $items) {
		// Items verbergen
		$itemsByUUID = array();
		foreach ($items as $index => $item) {
			$itemsByUUID[$item->getUUID()] = $item;
			unset($items[$index]);
		}
		if (count($itemsByUUID) > 0) {
			/** @var AgendaVerbergen[] $verborgen */
			$verborgen = $this->agendaVerbergenRepository->createQueryBuilder('av')
				->where('av.uid = :uid and av.refuuid in (:uuids)')
				->setParameter('uid', LoginService::getUid())
				->setParameter('uuids', array_keys($itemsByUUID))
				->getQuery()->getResult();

			foreach ($verborgen as $verbergen) {
				unset($itemsByUUID[$verbergen->refuuid]);
			}
		}
		return $itemsByUUID;
	}

	/**
	 * @param DateTimeImmutable $van
	 * @param DateTimeImmutable $tot
	 * @param $query
	 * @param $limiet
	 * @return AgendaItem[]
	 */
	public function zoeken(DateTimeImmutable $van, DateTimeImmutable $tot, $query, $limiet) {
		return $this->createQueryBuilder('a')
			->where('a.eind_moment >= :van and a.begin_moment <= :tot')
			->andWhere('a.titel like :query or a.beschrijving like :query or a.locatie like :query')
			->setParameter('van', $van)
			->setParameter('tot', $tot)
			->setParameter('query', sql_contains($query))
			->orderBy('a.begin_moment', 'ASC')
			->addOrderBy('a.titel', 'ASC')
			->setMaxResults($limiet)
			->getQuery()->getResult();
	}

	/**
	 * @param DateTimeImmutable $van
	 * @param DateTimeImmutable $tot
	 * @param bool $ical
	 * @param bool $zijbalk
	 * @return Agendeerbaar[]
	 */
	public function getAllAgendeerbaar(DateTimeImmutable $van, DateTimeImmutable $tot, $ical = false, $zijbalk = false) {
		$result = array();

		// AgendaItems
		/** @var AgendaItem[] $items */
		$items = $this->createQueryBuilder('a')
			->where('a.begin_moment >= :begin_moment and a.begin_moment < :eind_moment')
			->orWhere('a.eind_moment >= :begin_moment and a.eind_moment < :eind_moment')
			->setParameter('begin_moment', $van)
			->setParameter('eind_moment', $tot->add(DateInterval::createFromDateString('1 day')))
			->getQuery()->getResult();
		foreach ($items as $item) {
			if ($item->magBekijken($ical)) {
				$result[] = $item;
			}
		}

		$auth = ($ical ? AuthenticationMethod::getEnumValues() : null);

		// Activiteiten
		/** @var Activiteit[] $activiteiten */
		$activiteiten = $this->activiteitenRepository->createQueryBuilder('a')
			->where("a.in_agenda = true and ((a.begin_moment >= :van and a.begin_moment <= :tot) or (a.eind_moment >= :van and a.eind_moment <= :tot))")
			->setParameter('van', $van)
			->setParameter('tot', $tot)
			->getQuery()->getResult();
		foreach ($activiteiten as $activiteit) {
			// Alleen bekijken in agenda (leden bekijken mag dus niet)
			if (in_array($activiteit->soort, [ActiviteitSoort::Extern, ActiviteitSoort::OWee, ActiviteitSoort::IFES]) OR $activiteit->mag(AccessAction::Bekijken, $auth)) {
				$result[] = $activiteit;
			}
		}

		// Maaltijden
		if (lid_instelling('agenda', 'toonMaaltijden') === 'ja') {
			$result = array_merge($result, $this->maaltijdenRepository->getMaaltijdenVoorAgenda($van->getTimestamp(), $tot->getTimestamp()));
		}

		// CorveeTaken
		if (lid_instelling('agenda', 'toonCorvee') === 'iedereen') {
			$result = array_merge($result, $this->corveeTakenRepository->getTakenVoorAgenda($van, $tot, true));
		} elseif (lid_instelling('agenda', 'toonCorvee') === 'eigen') {
			$result = array_merge($result, $this->corveeTakenRepository->getTakenVoorAgenda($van, $tot, false));
		}

		// Verjaardagen
		$toonVerjaardagen = ($ical ? 'toonVerjaardagenICal' : 'toonVerjaardagen');
		if (!$zijbalk && LoginService::mag(P_VERJAARDAGEN, $auth) AND lid_instelling('agenda', $toonVerjaardagen) === 'ja') {
			//Verjaardagen. Omdat Lid-objectjes eigenlijk niet Agendeerbaar, maar meer iets als
			//PeriodiekAgendeerbaar zijn, maar we geen zin hebben om dat te implementeren,
			//doen we hier even een vieze hack waardoor het wel soort van werkt.
			$GLOBALS['agenda_van'] = $van;
			$GLOBALS['agenda_tot'] = $tot;

			$result = array_merge($result, $this->verjaardagenService->getTussen($van, $tot));
		}

		// Sorteren
		usort($result, array(AgendaRepository::class, 'vergelijkAgendeerbaars'));

		return $result;
	}

	/**
	 * Zoek in de activiteiten (titel en beschrijving) van vandaag
	 * naar het woord $woord, geef de eerste terug.
	 * @param $woord string
	 * @return mixed|null
	 */
	public function zoekWoordAgenda($woord) {
		$beginDag = date_create_immutable()->setTime(0, 0, 0);
		foreach ($this->getItemsByDay($beginDag) as $item) {
			if (stristr($item->getTitel(), $woord) !== false OR stristr($item->getBeschrijving(), $woord) !== false) {
				return $item;
			}
		}
		return null;
	}

	public function getItemsByDay(DateTimeImmutable $dag) {
		return $this->getAllAgendeerbaar($dag, $dag);
	}

	public function nieuw($begin_moment, $eind_moment) {
		$item = new AgendaItem();
		$item->begin_moment = $begin_moment ? date_create_immutable($begin_moment) : date_create_immutable()->add(new DateInterval('P1D'));
		$item->eind_moment = $eind_moment ? date_create_immutable($eind_moment) : date_create_immutable()->add(new DateInterval('P2D'));
		if (LoginService::mag(P_AGENDA_MOD)) {
			$item->rechten_bekijken = instelling('agenda', 'standaard_rechten');
		} else {
			$item->rechten_bekijken = 'verticale:' . $this->security->getUser()->profiel->verticale;
		}
		return $item;
	}
}
