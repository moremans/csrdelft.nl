<?php

/**
 * CmsPaginaView.class.php
 * 
 * @author C.S.R. Delft <pubcie@csrdelft.nl>
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Bekijken van een CmsPagina.
 */
class CmsPaginaView implements View {

	private $pagina;

	public function __construct(CmsPagina $pagina) {
		$this->pagina = $pagina;
	}

	function getModel() {
		return $this->pagina;
	}

	function getTitel() {
		return $this->pagina->titel;
	}

	public function view() {
		echo SimpleHTML::getMelding();
		if ($this->pagina->magBewerken()) {
			echo '<a href="/pagina/bewerken/' . $this->pagina->naam . '" class="knop float-right"title="Bewerk pagina&#013;' . $this->pagina->laatst_gewijzigd . '">' . Icon::getTag('bewerken') . '</a>';
		}
		echo CsrHtmlUbb::parse(htmlspecialchars_decode($this->pagina->inhoud));
	}

}

/**
 * CmsPaginaForm.class.php
 * 
 * @author C.S.R. Delft <pubcie@csrdelft.nl>
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Bewerken van een CmsPagina.
 */
class CmsPaginaForm extends Formulier {

	function __construct(CmsPagina $pagina) {
		parent::__construct($pagina, 'cms-pagina-form', '/pagina/bewerken/' . $pagina->naam);
		$this->titel = 'Pagina bewerken: ' . $pagina->naam;

		$fields[] = new HtmlComment('<div><label>Laatst gewijzigd</label>' . reldate($pagina->laatst_gewijzigd) . '</div>');
		$fields[] = new TextField('titel', $pagina->titel, 'Titel');
		if ($pagina->magRechtenWijzigen()) {
			$fields[] = new RechtenField('rechten_bekijken', $pagina->rechten_bekijken, 'Rechten bekijken');
			$fields[] = new RechtenField('rechten_bewerken', $pagina->rechten_bewerken, 'Rechten bewerken');
		} else {
			$fields[] = new HtmlComment('<div><label>Rechten bekijken</label>' . $pagina->rechten_bekijken .
					'</div><div class="clear-left"><label>Rechten bewerken</label>' . $pagina->rechten_bewerken . '</div>');
		}
		$fields[] = new UbbPreviewField('inhoud', $pagina->inhoud, 'Inhoud');
		$fields['btn'] = new FormButtons('/pagina/' . $pagina->naam, true, true, true, '/pagina/verwijderen/' . $pagina->naam);
		$fields['btn']->deleteTitle = 'Pagina definitief verwijderen';
		$fields['btn']->deleteAction = 'confirm ReloadPage';

		$this->addFields($fields);
	}

}

/**
 * CmsPaginaZijbalkView.class.php
 * 
 * @author C.S.R. Delft <pubcie@csrdelft.nl>
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 * Tonen van de lijst met CmsPaginas voor de zijbalk.
 */
class CmsPaginaZijbalkView implements View {

	private $paginas;

	public function __construct(CmsPaginaModel $model) {
		$this->paginas = $model->getAllePaginas();
	}

	public function getModel() {
		return $this->paginas;
	}

	public function getTitel() {
		return 'Pagina\'s';
	}

	public function view() {
		echo '<h1>' . $this->getTitel() . '</h1>';
		foreach ($this->paginas as $pagina) {
			echo '<div class="item">';
			echo '<a href="/pagina/' . $pagina->naam . '" title="' . $pagina->naam . '" >' . $pagina->titel . '</a><br />';
			echo '</div>';
		}
	}

}
