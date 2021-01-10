<?php

namespace CsrDelft\repository\civimelder;

use CsrDelft\entity\civimelder\Activiteit;
use CsrDelft\entity\civimelder\Reeks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activiteit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activiteit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activiteit[]    findAll()
 * @method Activiteit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActiviteitRepository extends ServiceEntityRepository {
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Activiteit::class);
	}

	/**
	 * Haalt de maaltijden op die beschikbaar zijn voor aanmelding voor het lid in de ingestelde periode vooraf.
	 *
	 * @return Activiteit[]
	 */
	public function getKomendeActiviteiten(Reeks $reeks) {
		$reeks_id = $reeks->getId();
		/** @var Activiteit[] $activiteiten */
//		$activiteiten = $this->createQueryBuilder('a')
		//	->where('a.start >= :van_datum and a.start <= :tot_datum')
		//	->setParameter('van_datum', date_create(instelling('civimelder_activiteit', 'aanmelden_vanaf')))		// Laat zien vanaf: gisteren
		//	->setParameter('tot_datum', date_create(instelling('civimelder_activiteit', 'eind'))) // laat zien tot: 'eind'
//			->setParameter('cur_reeks_id', $reeks_id)
//			->where('a.reeks = :cur_reeks_id')
//			->orderBy('a.start', 'ASC')
//			->addOrderBy('a.start', 'ASC')
//			->getQuery()->getResult();
		$activiteiten = $reeks->getActiviteiten()->filter(function(Activiteit $activiteit){
			return $activiteit->magBekijken() && $activiteit->isInToekomst();
		});
		return $activiteiten;
	}
}
