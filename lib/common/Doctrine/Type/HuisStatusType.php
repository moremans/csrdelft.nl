<?php


namespace CsrDelft\common\Doctrine\Type;


use CsrDelft\entity\groepen\enum\HuisStatus;

class HuisStatusType extends EnumType {
	public function getEnumClass() {
		return HuisStatus::class;
	}

	public function getName() {
		return 'enumstatus';
	}
}