<?php

use CsrDelft\Orm\Entity\PersistentEntity;
use CsrDelft\Orm\Entity\T;

class CiviBestellingInhoud extends PersistentEntity {
	public $bestelling_id;
	public $product_id;
	public $aantal;

	protected static $table_name = 'CiviBestellingInhoud';
	protected static $persistent_attributes = array(
		'bestelling_id' => array(T::Integer),
		'product_id' => array(T::Integer),
		'aantal' => array(T::Integer)
	);
	protected static $primary_key = array('bestelling_id', 'product_id');
}
