<?php

namespace CsrDelft\view\fotoalbum;

use CsrDelft\common\ContainerFacade;
use CsrDelft\entity\fotoalbum\FotoAlbum;
use CsrDelft\repository\CmsPaginaRepository;
use CsrDelft\view\cms\CmsPaginaView;
use CsrDelft\view\formulier\Dropzone;
use CsrDelft\view\formulier\uploadvelden\ImageField;
use CsrDelft\view\Icon;

class FotosDropzone extends Dropzone {

	public function __construct(FotoAlbum $album) {
		parent::__construct($album, '/fotoalbum/uploaden/' . $album->subdir, new ImageField('afbeelding', 'Foto', null, null, array('image/jpeg')), '/fotoalbum');
	}

	public function getBreadcrumbs() {
		return '<ul class="breadcrumb">' . FotoAlbumBreadcrumbs::getBreadcrumbs($this->model, false, true) . '</ul>';
	}

	public function view() {
		echo '<div class="card"><div class="card-header">Fotos toevoegen aan: ' .ucfirst($this->model->dirname). '</div><div class="card-body">';
		parent::view();
		echo '</div><div class="card-footer">';
		echo '<span class="cursief">Maak nooit inbreuk op de auteursrechten of het recht op privacy van anderen.</span>';
		echo '</div></div>';
		// Uitleg foto's toevoegen
		$body = new CmsPaginaView(ContainerFacade::getContainer()->get(CmsPaginaRepository::class)->find('fotostoevoegen'));
		$body->view();
	}

}
