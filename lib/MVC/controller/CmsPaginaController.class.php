<?php

require_once 'MVC/model/CmsPaginaModel.class.php';
require_once 'MVC/view/CmsPaginaView.class.php';

/**
 * CmsPaginaController.class.php
 * 
 * @author C.S.R. Delft <pubcie@csrdelft.nl>
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Controller van de agenda.
 */
class CmsPaginaController extends Controller {

	/**
	 * Hier alle namen van pagina's die in de nieuwe layout moeten worden weergegeven [FIXME]
	 * @var array
	 */
	private $nieuw = array('thuis', 'contact', 'csrindeowee', 'vereniging', 'lidworden', 'geloof', 'vorming', 'filmpjes', 'gezelligheid', 'sport', 'vragen', 'officieel', 'societeit', 'ontspanning', 'interesse', 'interesseverzonden', 'accountaanvragen');
	/**
	 * Data access model
	 * @var CmsPaginaModel
	 */
	private $model;

	public function __construct($query) {
		parent::__construct($query);
		$this->model = new CmsPaginaModel();
		$this->action = 'bekijken';
		$naam = 'thuis';
		if ($this->hasParam(2)) {
			if ($this->getParam(2) === 'bewerken') {
				if ($this->hasParam(3)) {
					$this->action = 'bewerken';
					$naam = $this->getParam(3);
				} else {
					$this->geentoegang();
				}
			} else {
				$naam = $this->getParam(2);
			}
		}
		$this->performAction(array($naam));
	}

	protected function hasPermission() {
		return true; // check permission on page itself
	}

	public static function magRechtenWijzigen() {
		return LoginLid::instance()->hasPermission('P_ADMIN');
	}

	public static function magVerwijderen() {
		return LoginLid::instance()->hasPermission('P_ADMIN');
	}

	public function bekijken($naam) {
		$pagina = $this->model->getPagina($naam);
		if (!($pagina instanceof CmsPagina)) { // 404
			$pagina = $this->model->getPagina('thuis');
		}
		if (!$pagina->magBekijken()) { // 403
			$this->geentoegang();
		}
		$body = new CmsPaginaView($pagina);
		if (in_array($pagina->naam, $this->nieuw) && !LoginLid::instance()->hasPermission('P_LOGGED_IN')) { // nieuwe layout alleen voor uitgelogde bezoekers
			$menu = '';
			$tmpl = 'content';
			if ($pagina->naam === 'thuis') {
				$tmpl = 'index';
			}
			elseif ($this->hasParam(2)) {
				$menu = $this->getParam(2);
				if ($menu === 'lidworden') {
					$tmpl = 'multi';
				}
				elseif ($menu === 'vereniging') {
					$tmpl = 'content';
				}
				else {
					$menu = '';
				}
			}
			$view = new csrdelft($body, 'csrdelft2');
			$view->view($tmpl, $menu);
			exit;
		}
		$this->view = new csrdelft($body);
	}

	public function bewerken($naam) {
		$pagina = $this->model->getPagina($naam);
		if (!($pagina instanceof CmsPagina)) {
			$pagina = $this->model->newPagina($naam);
		}
		if (!$pagina->magBewerken()) {
			$this->geentoegang();
		}
		$form = new CmsPaginaFormView($pagina); // fetches POST values itself
		if ($this->isPosted() AND $form->validate()) {
			$rowcount = $this->model->update($pagina);
			if ($rowcount > 0) {
				setMelding('Bijgewerkt', 1);
			} else {
				setMelding('Geen wijzigingen', 0);
			}
			invokeRefresh(CSR_ROOT . $pagina->naam);
		} else {
			$this->view = new csrdelft($form);
			$this->view->zijkolom[] = new CmsPaginaZijkolomView($this->model);
		}
	}

	public function verwijderen($naam) {
		$pagina = $this->model->getPagina($naam);
		if (!($pagina instanceof CmsPagina) OR !self::magVerwijderen()) {
			$this->geentoegang();
		}
		$this->model->delete($pagina);
		invokeRefresh(CSR_ROOT, 'Verwijderd', 1);
	}

}
