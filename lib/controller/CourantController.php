<?php

namespace CsrDelft\controller;

use CsrDelft\common\CsrToegangException;
use CsrDelft\common\Ini;
use CsrDelft\controller\framework\QueryParamTrait;
use CsrDelft\model\CourantBerichtModel;
use CsrDelft\model\CourantModel;
use CsrDelft\model\entity\courant\Courant;
use CsrDelft\model\entity\courant\CourantBericht;
use CsrDelft\model\security\LoginModel;
use CsrDelft\Orm\Persistence\Database;
use CsrDelft\view\courant\CourantArchiefView;
use CsrDelft\view\courant\CourantBeheerView;
use CsrDelft\view\courant\CourantBerichtFormulier;
use CsrDelft\view\courant\CourantView;
use CsrDelft\view\CsrLayoutPage;
use CsrDelft\view\PlainView;


/**
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Controller van de courant.
 */
class CourantController {
	use QueryParamTrait;

	private $courantModel;
	private $courantBerichtModel;

	public function __construct() {
		$this->courantModel = CourantModel::instance();
		$this->courantBerichtModel = CourantBerichtModel::instance();
	}

	public function archief() {
		return new CsrLayoutPage(new CourantArchiefView($this->courantModel->find()));
	}

	public function bekijken($id) {
		$courant = $this->courantModel->get($id);
		return new CourantView($courant);
	}

	public function toevoegen() {
		$bericht = new CourantBericht();
		$bericht->volgorde = 0;
		$bericht->datumTijd = getDateTime();
		$bericht->uid = LoginModel::getUid();
		$form = new CourantBerichtFormulier($bericht, '/courant');
		if ($form->isPosted() && $form->validate()) {
			$this->courantBerichtModel->create($bericht);
			setMelding('Uw bericht is opgenomen in ons databeest, en het zal in de komende C.S.R.-courant verschijnen.', 1);
			redirect("/courant");
		}
		return new CsrLayoutPage(new CourantBeheerView($this->courantModel, $form));
	}

	public function bewerken($id) {
		$bericht = $this->courantBerichtModel->get($id);
		$form = new CourantBerichtFormulier($bericht, '/courant/bewerken/' . $id);

		if ($form->isPosted() && $form->validate()) {
			$this->courantBerichtModel->update($bericht);
			setMelding('Bericht is bewerkt', 1);
			redirect('/courant');
		}

		return new CsrLayoutPage(new CourantBeheerView($this->courantModel, $form));
	}

	public function verwijderen($id) {
		$bericht = $this->courantBerichtModel->get($id);
		if (!$bericht OR !$this->courantModel->magBeheren($bericht->uid)) {
			throw new CsrToegangException();
		}
		if ($this->courantBerichtModel->delete($bericht)) {
			setMelding('Uw bericht is verwijderd.', 1);
		} else {
			setMelding('Uw bericht is niet verwijderd.', -1);
		}
		redirect("/courant");
	}

	public function verzenden($iedereen = null) {
		if ($this->courantBerichtModel->getNieuweBerichten() < 1) {
			setMelding('Lege courant kan niet worden verzonden', 0);
			redirect('/courant');
		}

		$courant = new Courant();
		$courant->template = 'courant.tpl';
		$courant->verzendMoment = getDateTime();
		$courant->verzender = LoginModel::getUid();

		$courantView = new CourantView($courant);
		if ($iedereen === 'iedereen') {
			$this->courantModel->verzenden(Ini::lees(Ini::EMAILS, 'leden'), $courantView);

			Database::transaction(function () use ($courant) {
				$courant->id = $this->courantModel->create($courant);
				$berichten = $this->courantBerichtModel->getNieuweBerichten();
				foreach ($berichten as $bericht) {
					$bericht->courantId = $courant->id;
					$this->courantBerichtModel->update($bericht);
				}
			});

			return new PlainView('aan iedereen verzonden');
		} else {
			$this->courantModel->verzenden(Ini::lees(Ini::EMAILS, 'pubcie'), $courantView);
			return new PlainView('<a href="/courant/verzenden/iedereen">aan iedereen verzenden</a>');
		}
	}
}
