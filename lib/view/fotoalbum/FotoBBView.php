<?php

namespace CsrDelft\view\fotoalbum;

use CsrDelft\entity\fotoalbum\Foto;
use CsrDelft\view\ToHtmlResponse;
use CsrDelft\view\ToResponse;
use CsrDelft\view\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FotoBBView
 * @package CsrDelft\view\fotoalbum
 */
class FotoBBView implements ToResponse, View {
	private $groot;
	private $responsive;
	private $model;

	public function __construct(
		Foto $foto,
		$groot = false,
		$responsive = false
	) {
		$this->model = $foto;
		$this->groot = $groot;
		$this->responsive = $responsive;
	}

	public function view() {
		echo $this->getHtml();
	}

	public function getHtml() {
		$html = '<a href="' . $this->model->getAlbumUrl();
		if ($this->groot) {
			$html .= '?fullscreen';
		}
		$html .= '#' . $this->model->getFullUrl() . '" class="';
		if ($this->responsive) {
			$html .= 'responsive';
		}
		if (!$this->groot AND lid_instelling('forum', 'fotoWeergave') == 'boven bericht') {
			$html .= ' hoverIntent"><div class="hoverIntentContent"><div class="bb-img-loading" src="' . $this->model->getResizedUrl() . '"></div></div>';
		} else {
			$html .= '">';
		}
		$html .= '<div class="bb-img-loading" src="';
		if (($this->groot AND lid_instelling('forum', 'fotoWeergave') != 'nee') OR lid_instelling('forum', 'fotoWeergave') == 'in bericht') {
			$html .= $this->model->getResizedUrl();
		} else {
			$html .= $this->model->getThumbUrl();
		}
		$html .= '"></div></a>';
		return $html;
	}

	public function toResponse(): Response {
		return new Response($this->getHtml());
	}

	public function getTitel() {
		return '';
	}

	public function getBreadcrumbs() {
		return '';
	}

	public function getModel() {
		return null;
	}
}
