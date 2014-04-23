<?php

/**
 * MenuModel.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 */
class MenuModel extends PersistenceModel {

	const orm = 'MenuItem';

	protected static $instance;

	/**
	 * Lijst van alle menus.
	 * 
	 * @return array
	 */
	public function getAlleMenus() {
		$sql = 'SELECT tekst FROM menus WHERE parent_id = 0';
		$query = Database::instance()->prepare($sql);
		$query->execute();
		return $query->fetchAll(PDO::FETCH_COLUMN, 0);
	}

	/**
	 * Haalt alle menu-items op (die zichtbaar zijn voor de gebruiker).
	 * Filtert de menu-items met de permissies van het ingelogede lid.
	 * 
	 * @param string $menu_naam
	 * @param boolean $admin
	 * @return MenuItem[]
	 */
	public function getMenuTree($menu_naam, $admin = false) {
		// get root
		$where = 'parent_id = 0 AND tekst = ?';
		$params = array($menu_naam);
		$root = $this->find($where, $params, 'parent_id ASC, prioriteit ASC');
		if (sizeof($root) <= 0) {
			$item = $this->newMenuItem(0);
			$item->tekst = $menu_naam;
			return $item;
		}
		$this->getChildren($root[0], $admin);
		return $root[0];
	}

	public function getChildren(MenuItem $parent, $admin = false) {
		$where = 'parent_id = ?' . ($admin ? '' : ' AND zichtbaar = true');
		$parent->children = $this->find($where, array($parent->item_id), 'prioriteit ASC');
		$child_active = false;
		foreach ($parent->children as $i => $child) {
			if (!$admin AND ! LoginLid::mag($child->rechten_bekijken)) {
				unset($parent->children[$i]);
				continue;
			}
			$child->active = startsWith(Instellingen::get('stek', 'request'), $child->link);
			$child_active |= $child->active;
			$this->getChildren($child, $admin);
		}
		$parent->active |= $child_active; // make parent of active child also active
	}

	public function getMenuItem($id) {
		return $this->retrieveByPrimaryKey(array($id));
	}

	public function newMenuItem($parent_id) {
		$item = new MenuItem();
		$item->parent_id = $parent_id;
		$item->prioriteit = 0;
		$item->link = '/';
		$item->rechten_bekijken = 'P_NOBODY';
		$item->zichtbaar = true;
		return $item;
	}

	public function removeMenuItem(MenuItem $item) {
		$db = Database::instance();
		try {
			$db->beginTransaction();
			// give new parent to otherwise future orphans
			$properties = array('parent_id' => $item->parent_id);
			$count = Database::sqlUpdate($this->orm_entity->getTableName(), $properties, 'parent_id = :oldid', array(':oldid' => $item->item_id));
			$this->delete($item);
			$db->commit();
			setMelding($count . ' menu-items nieuwe parent gegeven.', 2);
		} catch (\Exception $e) {
			$db->rollback();
			throw $e; // rethrow to controller
		}
	}

}
