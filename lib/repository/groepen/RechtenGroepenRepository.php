<?php

namespace CsrDelft\repository\groepen;

use CsrDelft\entity\groepen\enum\GroepStatus;
use CsrDelft\entity\groepen\RechtenGroep;
use CsrDelft\repository\AbstractGroepenRepository;
use CsrDelft\repository\groepen\leden\CommissieLedenRepository;
use CsrDelft\repository\ProfielRepository;
use Doctrine\Persistence\ManagerRegistry;

class RechtenGroepenRepository extends AbstractGroepenRepository {
	/** @var BesturenRepository */
	private $besturenRepository;
	/** @var CommissieLedenRepository */
	private $commissieLedenRepository;
	/** @var CommissiesRepository */
	private $commissiesRepository;

	public function __construct(BesturenRepository $besturenRepository, CommissiesRepository $commissiesRepository, CommissieLedenRepository $commissieLedenRepository, ManagerRegistry $registry) {
		parent::__construct($registry, RechtenGroep::class);

		$this->besturenRepository = $besturenRepository;
		$this->commissiesRepository = $commissiesRepository;
		$this->commissieLedenRepository = $commissieLedenRepository;
	}

	public function nieuw($soort = null) {
		/** @var RechtenGroep $groep */
		$groep = parent::nieuw();
		$groep->rechten_aanmelden = P_LEDEN_MOD;
		return $groep;
	}

	public static function getNaam() {
		return 'overig';
	}

	/**
	 * Groepen waarvan de gevraagde gebruiker de wikipagina's mag lezen en bewerken.
	 *
	 * @param string $uid
	 * @return array
	 */
	public function getWikiToegang($uid) {
		$result = [];
		$profiel = ProfielRepository::get($uid);
		if (!$profiel) {
			return $result;
		}
		if ($profiel->isLid() OR $profiel->isOudlid()) {
			$result[] = 'htleden-oudleden';
		}
		// 1 generatie vooruit en 1 achteruit (default order by)
		$ft = $this->besturenRepository->findOneBy(['status' => GroepStatus::FT()]);
		$ht = $this->besturenRepository->findOneBy(['status' => GroepStatus::HT()]);
		$ot = $this->besturenRepository->findOneBy(['status' => GroepStatus::OT()], ['id' => 'DESC']);
		if (($ft && $ft->getLid($uid)) || ($ht && $ht->getLid($uid)) || ($ot && $ot->getLid($uid))) {
			$result[] = 'bestuur';
		}
		foreach ($this->commissieLedenRepository->findBy(['uid' => $uid]) as $commissielid) {
			$commissie = $this->commissiesRepository->get($commissielid->groep_id);
			if ($commissie->status === GroepStatus::HT() OR $commissie->status === GroepStatus::FT()) {
				$result[] = $commissie->familie;
			}
		}
		return $result;
	}

}
