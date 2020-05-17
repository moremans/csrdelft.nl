<?php

namespace CsrDelft\entity\maalcie;

use CsrDelft\common\ContainerFacade;
use CsrDelft\repository\fiscaat\CiviProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Monolog\DateTimeImmutable;

/**
 * MaaltijdRepetitie.class.php  |  P.W.G. Brussee (brussee@live.nl)
 *
 *
 * Een mlt_repetitie instantie beschrijft een maaltijd die periodiek wordt gehouden als volgt:
 *  - uniek identificatienummer
 *  - op welke dag van de week de maaltijd wordt gehouden
 *  - na hoeveel dagen deze opnieuw wordt gehouden
 *  - de standaard naam van de maaltijd (bijv. donderdag-maaltijd)
 *  - de standaard tijd van de maaltijd (bijv. 18:00)
 *  - of er een abonnement kan worden genomen op deze periodieke maaltijden
 *  - de standaard limiet van het aantal aanmeldingen
 *  - of er restricties gelden voor wie zich mag abonneren op deze maaltijd
 *
 *
 * De standaard titel, limiet en filter worden standaard overgenomen, maar kunnen worden overschreven per maaltijd.
 * Bij het aanmaken van een nieuwe maaltijd (op basis van deze repetitie) worden alle leden met een abonnement op deze repetitie aangemeldt voor deze nieuwe maaltijd.
 *
 * @see MaaltijdAbonnement
 *
 * @ORM\Entity(repositoryClass="CsrDelft\repository\maalcie\MaaltijdRepetitiesRepository")
 * @ORM\Table("mlt_repetities")
 */
class MaaltijdRepetitie {
	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 */
	public $mlt_repetitie_id;
	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	public $product_id;
	/**
	 * 0: Sunday
	 * 6: Saturday
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	public $dag_vd_week;
	/**
	 * @var int
	 * @ORM\Column(type="integer")
	 */
	public $periode_in_dagen;
	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	public $standaard_titel;
	/**
	 * @var DateTimeImmutable
	 * @ORM\Column(type="time")
	 */
	public $standaard_tijd;
	/**
	 * @var int|null
	 * @ORM\Column(type="integer", nullable=true)
	 */
	public $standaard_prijs;
	/**
	 * @var boolean
	 * @ORM\Column(type="boolean")
	 */
	public $abonneerbaar;
	/**
	 * @var integer
	 * @ORM\Column(type="integer")
	 */
	public $standaard_limiet;
	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	public $abonnement_filter;

	public function getStandaardPrijs() {
		return ContainerFacade::getContainer()->get(CiviProductRepository::class)->getPrijs(ContainerFacade::getContainer()->get(CiviProductRepository::class)->getProduct($this->product_id))->prijs;
	}

	public function getDagVanDeWeekText() {
		return strftime('%A', ($this->dag_vd_week + 3) * 24 * 3600);
	}

	public function getPeriodeInDagenText() {
		switch ($this->periode_in_dagen) {
			case 0:
				return '-';
			case 1:
				return 'elke dag';
			case 7:
				return 'elke week';
			default:
				if ($this->periode_in_dagen % 7 === 0) {
					return 'elke ' . ($this->periode_in_dagen / 7) . ' weken';
				} else {
					return 'elke ' . $this->periode_in_dagen . ' dagen';
				}
		}
	}

	public function getStandaardPrijsFloat() {
		return (float)$this->getStandaardPrijs() / 100.0;
	}

	public function getFirstOccurrence() {
		$datum = time();
		$shift = $this->dag_vd_week - date('w', $datum) + 7;
		$shift %= 7;
		if ($shift > 0) {
			$datum = strtotime('+' . $shift . ' days', $datum);
		}
		return date('Y-m-d', $datum);
	}
}
