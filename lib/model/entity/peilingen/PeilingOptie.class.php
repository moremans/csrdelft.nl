<?php

namespace CsrDelft\model\entity\peilingen;

use CsrDelft\model\HasForeignKeys;
use CsrDelft\Orm\Entity\PersistentEntity;
use CsrDelft\Orm\Entity\T;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 */
class PeilingOptie extends PersistentEntity {
	use HasForeignKeys;
	/**
	 * Primary key
	 * @var int
	 */
	public $id;
	/**
	 * Foreign key
	 * @var int
	 */
	public $peiling_id;
	/**
	 * Titel
	 * @var string
	 */
	public $titel;
	/**
	 * @var string
	 */
	public $beschrijving;
	/**
	 * Aantal stemmen
	 * @var int
	 */
	public $stemmen = 0;
	/**
	 * @var string
	 */
	public $ingebracht_door;

	protected static $persistent_attributes = [
		'id' => [T::Integer, false, 'auto_increment'],
		'peiling_id' => [T::Integer],
		'titel' => [T::String],
		'beschrijving' => [T::Text, true],
		'stemmen' => [T::Integer],
		'ingebracht_door' => [T::UID, true],
	];

	protected static $primary_key = ['id'];

	protected static $foreign_keys = ['peiling_id' => Peiling::class];

	protected static $table_name = 'peiling_optie';
}
