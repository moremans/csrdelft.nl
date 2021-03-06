<?php

namespace CsrDelft\repository\groepen;

use CsrDelft\entity\groepen\enum\GroepStatus;
use CsrDelft\entity\groepen\enum\OnderverenigingStatus;
use CsrDelft\entity\groepen\Ondervereniging;
use CsrDelft\repository\AbstractGroepenRepository;
use CsrDelft\service\security\LoginService;
use Doctrine\Persistence\ManagerRegistry;

class OnderverenigingenRepository extends AbstractGroepenRepository {
	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, Ondervereniging::class);
	}

	public function nieuw($soort = null) {
		/** @var Ondervereniging $ondervereniging */
		$ondervereniging = parent::nieuw();
		$ondervereniging->status = GroepStatus::FT();
		$ondervereniging->soort = OnderverenigingStatus::AdspirantOndervereniging;
		$ondervereniging->status_historie = '[div]Aangemaakt als ' . $ondervereniging->status->getDescription() . ' door [lid=' . LoginService::getUid() . '] op [reldate]' . getDatetime() . '[/reldate][/div][hr]';
		return $ondervereniging;
	}
}
