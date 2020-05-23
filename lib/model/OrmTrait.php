<?php


namespace CsrDelft\model;

use CsrDelft\Orm\Persistence\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * Voeg standaard methodes van csrdelft/orm toe aan een doctrine repository.
 *
 * Voor gebruik in een EntityServiceRepository
 * @package CsrDelft\model
 */
trait OrmTrait {
	public function exists($entity) {
		/** @var ClassMetadata $metadata */
		$metadata = $this->getClassMetadata();

		$identifierValues = $metadata->getIdentifierValues($entity);

		if ($identifierValues == null) {
			return false;
		}

		return parent::find($identifierValues) !== null;
	}

	/**
	 * De oude find methode uit csrdelft/orm
	 *
	 * @param null $criteria
	 * @param array $criteria_params
	 * @param null $group_by
	 * @param null $order_by
	 * @param null $limit
	 * @param int $start
	 * @return array
	 */
	public function ormFind(
		$criteria = null,
		$criteria_params = [],
		$group_by = null,
		$order_by = null,
		$limit = null,
		$start = 0
	) {
		/** @var EntityManager $entityManager */
		$entityManager = $this->getEntityManager();
		/** @var ClassMetadata $metadata */
		$metadata = $this->getClassMetadata();

		$rsm = new ResultSetMappingBuilder($entityManager);
		$rsm->addRootEntityFromClassMetadata($metadata->getName(), 'u');

		$query = (new QueryBuilder())->buildSelect(
			['u.*'],
			$metadata->getTableName() . ' u',
			$criteria,
			$group_by,
			$order_by,
			$limit,
			$start
		);

		return $entityManager
			->createNativeQuery($query, $rsm)
			->setParameters($criteria_params)
			->getResult();
	}
}
