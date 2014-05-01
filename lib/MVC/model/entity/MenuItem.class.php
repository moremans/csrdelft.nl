<?php

/**
 * MenuItem.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Een menu-item instantie beschrijft een menu onderdeel van een menu-boom
 * en heeft daarom een parent.
 * 
 */
class MenuItem extends PersistentEntity {

	/**
	 * Primary key
	 * @var int
	 */
	public $item_id;
	/**
	 * Dit menu-item is een sub-item van
	 * @var int
	 */
	public $parent_id;
	/**
	 * Volgorde van weergave
	 * @var int
	 */
	public $prioriteit;
	/**
	 * Link tekst
	 * @var string
	 */
	public $tekst;
	/**
	 * Link url
	 * @var string
	 */
	public $link;
	/**
	 * LoginLid::hasPermission
	 * @var string
	 */
	public $rechten_bekijken;
	/**
	 * Zichtbaar of verborgen
	 * @var boolean
	 */
	public $zichtbaar;
	/**
	 * State of menu GUI
	 * @var boolean
	 */
	public $active;
	/**
	 * De sub-items van dit menu-item
	 * @var array
	 */
	public $children = array();
	/**
	 * Database table fields
	 * @var array
	 */
	protected static $persistent_fields = array(
		'item_id' => array('int', 11, false, null, 'auto_increment'),
		'parent_id' => array('int', 11, false, 0),
		'prioriteit' => array('int', 11, false, 0),
		'tekst' => array('string', 50),
		'link' => array('string', 255),
		'rechten_bekijken' => array('string', 255, false, 'P_NOBODY'),
		'zichtbaar' => array('boolean', null, false, true)
	);
	/**
	 * Database primary key
	 * @var array
	 */
	protected static $primary_key = array('item_id');
	/**
	 * Database table name
	 * @var string
	 */
	protected static $table_name = 'menus';

	/**
	 * Bepaald of het gevraagde menu-item een
	 * sub-item is van dit menu-item.
	 * 
	 * @param MenuItem $item
	 * @return boolean
	 */
	public function isParentOf(MenuItem $item) {
		if ($this->item_id === $item->parent_id) {
			return true;
		}
		foreach ($this->children as $child) {
			if ($child->isParentOf($item)) {
				return true;
			}
		}
		return false;
	}

}
