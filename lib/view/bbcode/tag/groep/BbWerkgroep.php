<?php

namespace CsrDelft\view\bbcode\tag\groep;

use CsrDelft\repository\groepen\WerkgroepenRepository;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 27/03/2019
 */
class BbWerkgroep extends BbTagGroep {
	public function __construct(WerkgroepenRepository $model) {
		parent::__construct($model);
	}

	public static function getTagName() {
		return 'werkgroep';
	}

	public function getLidNaam() {
		return 'aanmeldingen';
	}
}
