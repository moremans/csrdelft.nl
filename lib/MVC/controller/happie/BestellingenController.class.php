<?php

require_once 'MVC/model/happie/BestellingenModel.class.php';
require_once 'MVC/view/happie/BestellingenView.class.php';
require_once 'MVC/view/happie/forms/BestelForm.class.php';

/**
 * BestellingenController.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Controller van de Happietaria bestellingen.
 * 
 */
class HappieBestellingenController extends AclController {

	public function __construct($query) {
		parent::__construct($query, HappieBestellingenModel::instance());
		$this->acl = array(
			'overzicht'	 => 'groep:2014',
			'serveer'	 => 'groep:2014',
			'keuken'	 => 'groep:2014',
			'bar'		 => 'groep:2014',
			'kassa'		 => 'groep:2014',
			'nieuw'		 => 'groep:2014',
			'wijzig'	 => 'groep:2014'
		);
	}

	public function performAction(array $args = array()) {
		$this->action = 'nieuw';
		if ($this->hasParam(3)) {
			$this->action = $this->getParam(3);
		}
		parent::performAction($this->getParams(4));
	}

	public function overzicht($y = null, $m = null, $d = null) {
		if ($this->isPosted()) {
			$y = (int) $y;
			$m = (int) $m;
			$d = (int) $d;
			if (checkdate($m, $d, $y)) {
				$datum = $y . '-' . $m . '-' . $d;
				$data = $this->model->find('datum = ?', array($datum));
			} else {
				$data = $this->model->find();
			}
			$this->view = new DataTableResponse($data);
		} else {
			$body = new HappieBestellingenView();
			$this->view = new CsrLayout3Page($body);
		}
	}

	public function serveer() {
		if ($this->isPosted()) {
			$data = $this->model->find('datum = ?', array(date('Y-m-d')));
			$this->view = new DataTableResponse($data);
		} else {
			$body = new HappieServeerView();
			$this->view = new CsrLayout3Page($body);
		}
	}

	public function keuken() {
		if ($this->isPosted()) {
			$data = $this->model->find('datum = ?', array(date('Y-m-d')));
			$this->view = new DataTableResponse($data);
		} else {
			$body = new HappieKeukenView();
			$this->view = new CsrLayout3Page($body);
		}
	}

	public function bar() {
		if ($this->isPosted()) {
			$data = $this->model->find('datum = ?', array(date('Y-m-d')));
			$this->view = new DataTableResponse($data);
		} else {
			$body = new HappieBarView();
			$this->view = new CsrLayout3Page($body);
		}
	}

	public function kassa() {
		if ($this->isPosted()) {
			$data = $this->model->find('datum = ?', array(date('Y-m-d')));
			$this->view = new DataTableResponse($data);
		} else {
			$body = new HappieKassaView();
			$this->view = new CsrLayout3Page($body);
		}
	}

	public function nieuw() {
		$form = new HappieBestelForm();
		if ($this->isPosted() AND $form->validate()) {
			$bestellingen = array();
			$sum = 0;
			foreach ($form->getValues() as $item_id => $value) {
				if ($value['aantal'] > 0) {
					$bestellingen[] = $this->model->newBestelling($value['tafel'], $item_id, $value['aantal'], $value['opmerking']);
					$sum += $value['aantal'];
				}
			}
			setMelding('Totaal ' . $sum . ' dingen besteld voor tafel ' . $value['tafel'], 1);
			redirect(happieUrl . '/serveer');
		}
		$this->view = new CsrLayout3Page($form);
	}

	public function wijzig($id) {
		$bestelling = $this->model->getBestelling((int) $id);
		if (!$bestelling) {
			setMelding('Bestelling bestaat niet', -1);
			redirect(happieUrl);
		}
		$form = new HappieBestellingWijzigenForm($bestelling);
		if ($this->isPosted() AND $form->validate()) {
			$this->model->update($bestelling);
			setMelding('Wijziging succesvol opgeslagen', 1);
			redirect(happieUrl);
		}
		$this->view = new CsrLayout3Page($form);
	}

}
