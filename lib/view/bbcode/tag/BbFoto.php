<?php

namespace CsrDelft\view\bbcode\tag;

use CsrDelft\bb\BbException;
use CsrDelft\bb\BbTag;
use CsrDelft\entity\fotoalbum\Foto;
use CsrDelft\repository\fotoalbum\FotoAlbumRepository;
use CsrDelft\view\bbcode\BbHelper;
use CsrDelft\view\fotoalbum\FotoBBView;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Toont de thumbnail van een foto met link naar fotoalbum.
 *
 * @param optional Boolean $arguments['responsive'] Responsive sizing
 *
 * @since 27/03/2019
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @example [foto responsive]/pad/naar/foto[/foto]
 */
class BbFoto extends BbTag {

	/**
	 * @var bool
	 */
	private $responsive;
	/**
	 * @var Foto
	 */
	private $foto;
	/**
	 * @var FotoAlbumRepository
	 */
	private $fotoAlbumRepository;

	public function __construct(FotoAlbumRepository $fotoAlbumRepository) {
		$this->fotoAlbumRepository = $fotoAlbumRepository;
	}

	public static function getTagName() {
		return 'foto';
	}

	public function isAllowed() {
		return $this->foto->magBekijken();
	}

	public function renderLight() {
		return BbHelper::lightLinkThumbnail('foto', $this->foto->getAlbumUrl() . '#' . $this->foto->getResizedUrl(), CSR_ROOT . $this->foto->getThumbUrl());
	}

	public function render() {
		$url = $this->content;
		$parts = explode('/', $url);
		$fototag = new FotoBBView($this->foto, in_array('Posters', $parts), $this->responsive);
		return $fototag->getHtml();
	}

	/**
	 * @param array $arguments
	 */
	public function parse($arguments = []) {
		$this->responsive = isset($arguments['responsive']);
		$this->readMainArgument($arguments);
		$this->foto = $this->getFoto(explode('/', $this->content), $this->content);
	}

	/**
	 * @param array $parts
	 * @param string $url
	 * @return Foto
	 * @throws BbException
	 */
	private function getFoto(array $parts, string $url): Foto {
		$filename = str_replace('#', '', array_pop($parts)); // replace # (foolproof)
		$path = implode('/', $parts);
		$path = str_replace('fotoalbum/', '', $path);
		try {
			$album = $this->fotoAlbumRepository->getFotoAlbum($path);
			$foto = new Foto($filename, $album);
			if (!$foto->exists()) {
				throw new BbException('Foto niet gevonden.');
			}
			return $foto;
		} catch (NotFoundHttpException $ex) {
			throw new BbException('<div class="bb-block">Fotoalbum niet gevonden: ' . htmlspecialchars($url) . '</div>');
		}
	}
}
