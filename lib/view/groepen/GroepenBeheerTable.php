<?php

namespace CsrDelft\view\groepen;

use CsrDelft\model\AbstractGroepenModel;
use CsrDelft\model\CmsPaginaModel;
use CsrDelft\view\cms\CmsPaginaView;
use CsrDelft\view\formulier\datatable\DataTable;
use CsrDelft\view\formulier\datatable\knop\DataTableKnop;
use CsrDelft\view\formulier\datatable\knop\ConfirmDataTableKnop;
use CsrDelft\view\formulier\datatable\Multiplicity;


/**
 * GroepenBeheerTable.php
 *
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 */
class GroepenBeheerTable extends DataTable {

	private $naam;
	private $pagina;

	public function __construct(AbstractGroepenModel $model) {
		parent::__construct($model::ORM, $model->getUrl() . 'beheren', null, 'familie');

		$this->naam = $model->getNaam();
		$this->titel = 'Beheer ' . $this->naam;

		$this->pagina = CmsPaginaModel::get($this->naam);
		if (!$this->pagina) {
			$this->pagina = CmsPaginaModel::get('');
		}

		$this->hideColumn('id', false);
		$this->hideColumn('samenvatting');
		$this->hideColumn('omschrijving');
		$this->hideColumn('maker_uid');
		$this->hideColumn('keuzelijst');
		$this->hideColumn('rechten_aanmelden');
		$this->hideColumn('status_historie');
		$this->searchColumn('naam');
		$this->searchColumn('status');
		$this->searchColumn('soort');

		$preview = new DataTableKnop(Multiplicity::One(), $this->dataUrl . '/voorbeeld', 'Voorbeeld', 'Voorbeeldweergave van de ketzer', 'show');
		$this->addKnop($preview);

		$create = new DataTableKnop(Multiplicity::Zero(), $this->dataUrl . '/nieuw', 'Nieuw', 'Nieuwe toevoegen', 'toevoegen');
		$this->addKnop($create);

		$next = new DataTableKnop(Multiplicity::One(), $this->dataUrl . '/aanmaken', 'Opvolger', 'Nieuwe toevoegen die de huidige opvolgt', 'toevoegen');
		$this->addKnop($next);

		$update = new DataTableKnop(Multiplicity::One(), $this->dataUrl . '/wijzigen', 'Wijzigen', 'Wijzig eigenschappen', 'bewerken');
		$this->addKnop($update);

		if (property_exists($model::ORM, 'aanmelden_vanaf')) {
			$sluiten = new DataTableKnop(Multiplicity::Any(), $this->dataUrl . '/sluiten', 'Sluiten', 'Inschrijvingen nu sluiten', 'lock');
			$this->addKnop($sluiten);
		}

		$opvolg = new DataTableKnop(Multiplicity::Any(), $this->dataUrl . '/opvolging', 'Opvolging', 'Familienaam en groepstatus instellen', 'timeline_marker');
		$this->addKnop($opvolg);

		$convert = new DataTableKnop(Multiplicity::Any(), $this->dataUrl . '/converteren', 'Converteren', 'Converteer naar ander soort groep', 'lightning');
		$this->addKnop($convert);

		$delete = new ConfirmDataTableKnop(Multiplicity::Any(), $this->dataUrl . '/verwijderen', 'Verwijderen', 'Definitief verwijderen (groep moet hier voor leeg zijn)', 'delete');
		$this->addKnop($delete);

		$log = new DataTableKnop(Multiplicity::One(), $this->dataUrl . '/logboek', 'Logboek', 'Logboek bekijken', 'log');
		$this->addKnop($log);
	}

	public function getBreadcrumbs() {
		return '<a href="/groepen" title="Groepen"><span class="fa fa-users module-icon"></span></a> » <a href="' . $this->dataUrl . '">' . ucfirst($this->naam) . '</a> » <span class="active">Beheren</span>';
	}

	public function view() {
		$view = new CmsPaginaView($this->pagina);
		$view->view();
		parent::view();
	}

}
