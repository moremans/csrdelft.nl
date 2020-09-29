<?php

namespace CsrDelft\entity\groepen;

use CsrDelft\Component\DataTable\DataTableEntry;
use CsrDelft\entity\groepen\enum\CommissieFunctie;
use CsrDelft\entity\profiel\Profiel;
use CsrDelft\model\entity\groepen\GroepKeuzeSelectie;
use CsrDelft\repository\ProfielRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * AbstractGroepLid.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Een lid van een groep.
 *
 * @ORM\MappedSuperclass()
 */
abstract class AbstractGroepLid implements DataTableEntry {

	public function getUUID() {
		return $this->groep_id . '.' . $this->uid . '@' . strtolower(short_class($this)) . '.csrdelft.nl';
	}
	/**
	 * Shared primary key
	 * Foreign key
	 * @var int
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 */
	public $groep_id;
	/**
	 * Lidnummer
	 * Shared primary key
	 * Foreign key
	 * @var string
	 * @ORM\Column(type="uid")
	 * @ORM\Id()
	 * @Serializer\Groups("vue")
	 */
	public $uid;
	/**
	 * @var Profiel
	 * @ORM\ManyToOne(targetEntity="CsrDelft\entity\profiel\Profiel")
	 * @ORM\JoinColumn(name="uid", referencedColumnName="uid")
	 */
	public $profiel;
	/**
	 * CommissieFunctie of opmerking bij lidmaatschap
	 * @var CommissieFunctie
	 * @ORM\Column(type="string", nullable=true)
	 */
	public $opmerking;
	/**
	 * @var GroepKeuzeSelectie[]
	 * @ORM\Column(type="groepkeuzeselectie", nullable=true)
	 * @Serializer\Groups("vue")
	 */
	public $opmerking2;
	/**
	 * Datum en tijd van aanmelden
	 * @var DateTimeImmutable
	 * @ORM\Column(type="datetime")
	 */
	public $lid_sinds;
	/**
	 * Lidnummer van aanmelder
	 * @var string
	 * @ORM\Column(type="uid")
	 */
	public $door_uid;
	/**
	 * @var Profiel
	 * @ORM\ManyToOne(targetEntity="CsrDelft\entity\profiel\Profiel")
	 * @ORM\JoinColumn(name="door_uid", referencedColumnName="uid")
	 */
	public $door_profiel;
	/**
	 * @return string|null
	 */
	public function getLid() {
		return ProfielRepository::getLink($this->uid);
	}

	/**
	 * @return string|null
	 */
	public function getDoorUid() {
		return ProfielRepository::getLink($this->door_uid);
	}

	/**
	 * @return string|null
	 * @Serializer\Groups("vue")
	 */
	public function getLink() {
		return ProfielRepository::getLink($this->uid);
	}

	/**
	 * @return AbstractGroep
	 */
	abstract public function getGroep();
}
