<?php

namespace CsrDelft\view\eetplan;

use CsrDelft\entity\groepen\Woonoord;
use CsrDelft\view\datatable\DataTableResponse;

/**
 * Data voor EetplanHuizenTable op /eetplan/woonoorden
 *
 * Class EetplanHuizenView
 */
class EetplanHuizenResponse extends DataTableResponse {
	/**
	 * @param Woonoord $entity
	 */
	public function renderElement($entity) {
		return array(
			'UUID' => $entity->getUUID(),
			'id' => $entity->id,
			'naam' => '<a href="' . $entity->getUrl() . '">' . $entity->naam . '</a>',
			'soort' => $entity->soort->getDescription(),
			'eetplan' => $entity->eetplan
		);
	}
}
