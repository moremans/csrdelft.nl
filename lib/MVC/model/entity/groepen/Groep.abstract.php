<?php

require_once 'MVC/model/entity/groepen/GroepLid.class.php';
require_once 'MVC/model/entity/groepen/GroepStatus.enum.php';

/**
 * Groep.abstract.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Een groep met leden en status.
 * 
 */
abstract class Groep extends PersistentEntity {

	/**
	 * Primary key
	 * @var int
	 */
	public $id;
	/**
	 * Deze groep valt onder deze categorie
	 * @var int
	 */
	public $categorie_id;
	/**
	 * Naam
	 * @var string
	 */
	public $naam;
	/**
	 * Korte omschrijving
	 * @var string
	 */
	public $samenvatting;
	/**
	 * Lange omschrijving
	 * @var string
	 */
	public $omschrijving;
	/**
	 * Rechten benodigd voor bekijken
	 * @var string
	 */
	public $rechten_bekijken;
	/**
	 * Rechten benodigd voor aanmelden
	 * @var string
	 */
	public $rechten_aanmelden;
	/**
	 * Rechten benodigd voor beheren
	 * @var string
	 */
	public $rechten_beheren;
	/**
	 * Uid van eigenaar
	 * @var string
	 */
	public $eigenaar_lid_id;
	/**
	 * URL van website
	 * @var string
	 */
	public $website;
	/**
	 * Groepsleden
	 * @var GroepLid[]
	 */
	private $groep_leden;
	/**
	 * Database table fields
	 * @var array
	 */
	protected static $persistent_fields = array(
		'id' => array('int', 11, false, null, 'auto_increment'),
		'categorie_id' => array('int', 11),
		'naam' => array('string', 255),
		'samenvatting' => array('text'),
		'omschrijving' => array('text'),
		'rechten_bekijken' => array('string', 255),
		'rechten_aanmelden' => array('string', 255),
		'rechten_beheren' => array('string', 255),
		'eigenaar_lid_id' => array('string', 4),
		'website' => array('string', 255)
	);
	/**
	 * Database primary key
	 * @var array
	 */
	protected static $primary_key = array('id');

	/**
	 * Lazy loading by foreign key.
	 * 
	 * @return GroepLid[]
	 */
	public function getGroepLeden() {
		if (!isset($this->groep_leden)) {
			$this->setGroepLeden(GroepLedenModel::instance()->getLedenVoorGroep($this));
		}
		return $this->groep_leden;
	}

	public function hasGroepLeden() {
		return count($this->getGroepLeden()) > 0;
	}

	public function setGroepLeden(array $leden) {
		$this->groep_leden = $leden;
	}

	/**
	 * Gaat er vanuit dat er precies 1 groeplid met de gevraagde functie bestaat in deze groep.
	 * 
	 * @see BestuurFunctie
	 * @param string $functie
	 * @return GroepLid
	 */
	public function getGroepLidByFunctie($functie) {
		foreach ($this->getGroepLeden() as $groeplid) {
			if ($groeplid->omschrijving === $functie) {
				return $groeplid;
			}
		}
	}

	public function magBekijken() {
		return LoginLid::mag($this->rechten_bekijken);
	}

	public function magAanmelden() {
		return LoginLid::mag($this->rechten_aanmelden);
	}

	public function magBeheren() {
		return LoginLid::mag($this->rechten_beheren);
	}

}
