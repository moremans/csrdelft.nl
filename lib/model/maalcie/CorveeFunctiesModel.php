<?php

namespace CsrDelft\model\maalcie;

use CsrDelft\common\CsrGebruikerException;
use CsrDelft\model\entity\maalcie\CorveeFunctie;
use CsrDelft\Orm\CachedPersistenceModel;

/**
 * FunctiesModel.class.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 */
class CorveeFunctiesModel extends CachedPersistenceModel {

	const ORM = CorveeFunctie::class;
	/**
	 * @var CorveeTakenModel
	 */
	private $corveeTakenModel;
	/**
	 * @var CorveeRepetitiesModel
	 */
	private $corveeRepetitiesModel;

	public function __construct(CorveeTakenModel $corveeTakenModel, CorveeRepetitiesModel $corveeRepetitiesModel) {
		parent::__construct();
		$this->corveeTakenModel = $corveeTakenModel;
		$this->corveeRepetitiesModel = $corveeRepetitiesModel;
	}

	/**
	 * Lazy loading of kwalificaties.
	 *
	 * @param int $fid
	 * @return CorveeFunctie|false
	 */
	public function get($fid) {
		return $this->retrieveByPrimaryKey(array($fid));
	}

	/**
	 * Optional eager loading of kwalificaties.
	 *
	 * @return CorveeFunctie[]
	 */
	public function getAlleFuncties() {
		return group_by_distinct('functie_id', $this->prefetch());
	}

	public function nieuw() {
		$functie = new CorveeFunctie();
		$functie->kwalificatie_benodigd = (boolean)instelling('corvee', 'standaard_kwalificatie');
		return $functie;
	}

	public function removeFunctie(CorveeFunctie $functie) {
		if ($this->corveeTakenModel->existFunctieTaken($functie->functie_id)) {
			throw new CsrGebruikerException('Verwijder eerst de bijbehorende corveetaken!');
		}
		if ($this->corveeRepetitiesModel->existFunctieRepetities($functie->functie_id)) {
			throw new CsrGebruikerException('Verwijder eerst de bijbehorende corveerepetities!');
		}
		if ($functie->hasKwalificaties()) {
			throw new CsrGebruikerException('Verwijder eerst de bijbehorende kwalificaties!');
		}
		return $this->delete($functie);
	}

}