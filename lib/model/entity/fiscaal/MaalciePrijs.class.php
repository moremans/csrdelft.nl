<?php

use CsrDelft\Orm\Entity\PersistentEntity;
use CsrDelft\Orm\Entity\T;

class MaalciePrijs extends PersistentEntity {
	public $van;
	public $tot;
	public $productid;
	public $prijs;

	protected static $table_name = 'maalcieprijs';
	protected static $persistent_attributes = array(
		'van' => array(T::Timestamp),
		'tot' => array(T::Timestamp),
		'productid' => array(T::Integer),
		'prijs' => array(T::Integer)
	);
	protected static $primary_key = array('van', 'productid');
}
