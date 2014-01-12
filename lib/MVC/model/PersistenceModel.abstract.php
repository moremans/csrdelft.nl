<?php

require_once 'MVC/model/Database.class.php';
require_once 'MVC/model/Persistence.interface.php';
require_once 'MVC/model/entity/PersistentEntity.abstract.php';

/**
 * PersistenceModel.abstract.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Uses database to provide persistence.
 * 
 */
abstract class PersistenceModel implements Persistence {

	/**
	 * Find existing entities.
	 * 
	 * @param string $criteria WHERE
	 * @param array $criteria_params optional named parameters
	 * @param string $orderby
	 * @param int $limit
	 * @param int $start
	 * @return PersistentEntity[]
	 */
	function find(PersistentEntity $entity, $criteria = null, array $criteria_params = array(), $orderby = null, $limit = null, $start = 0) {
		$select = $entity::getFields();
		if ($criteria === null) {
			$where = '1';
		} else {
			$where = '';
		}
		$result = Database::sqlSelect($select, $entity::getTableName(), implode(', ', $where), $criteria_params, $orderby, $limit, $start);
		return $result->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, get_class($entity));
	}

	/**
	 * Save new entity.
	 * 
	 * @param PersistentEntity $entity
	 * @return string last insert id
	 */
	function create(PersistentEntity $entity) {
		return Database::sqlInsert($entity::getTableName(), $entity->getValues());
	}

	/**
	 * Load entity data.
	 * 
	 * @param PersistentEntity $entity
	 * @return PersistentEntity
	 */
	function retrieve(PersistentEntity $entity) {
		$select = $entity::getFields();
		$where = '';
		$params = array();
		foreach ($entity::getPrimaryKey() as $key) {
			$where .= $key . ' = ?';
			$params[] = $entity->$key;
		}
		$result = Database::sqlSelect($select, $entity::getTableName(), implode(', ', $where), $params, null, 1);
		$entity = $result->fetch(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, get_class($entity));
		return $entity;
	}

	/**
	 * Save existing entity.
	 * 
	 * @param PersistentEntity $entity
	 */
	function update(PersistentEntity $entity) {
		$where = '';
		$params = array();
		foreach ($entity::getPrimaryKey() as $key) {
			$where .= $key . ' = ?';
			$params[] = $entity->$key;
		}
		$rowcount = Database::sqlUpdate($entity::getTableName(), $entity->getValues(), $where, $params);
		if ($rowcount !== 1) {
			throw new Exception('delete $rowcount=' . $rowcount);
		}
	}

	/**
	 * Remove existing entity.
	 * 
	 * @param PersistentEntity $entity
	 * @throws Exception
	 */
	function delete(PersistentEntity $entity) {
		$where = implode(', ', $entity::getPrimaryKey());
		$params = array();
		foreach ($entity::getPrimaryKey() as $key) {
			$params[] = $entity->$key;
		}
		$rowcount = Database::sqlDelete($entity::getTableName(), $where, $params);
		if ($rowcount !== 1) {
			throw new Exception('delete $rowcount=' . $rowcount);
		}
	}

}
