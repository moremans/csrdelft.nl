<?php

namespace CsrDelft\model\entity\fiscaat;

use CsrDelft\common\Enum;

/**
 * Er zijn een aantal CiviProducten die in de code gebruikt worden. Deze staan hier.
 *
 * @author Gerben Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 19/02/2018
 */
class CiviProductTypeEnum extends Enum {
	/**
	 * CiviProductTypeEnum opties.
	 */
	const PINTRANSACTIE = 24;
	const CONTANT = 6;
	const OVERGEMAAKT = 25;

	protected static $mapChoiceToDescription = [
		self::PINTRANSACTIE => 'PIN',
		self::CONTANT => 'Contant',
		self::OVERGEMAAKT => 'Overgemaakt',
	];
}
