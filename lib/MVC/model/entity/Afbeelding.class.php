<?php

require_once 'MVC/model/entity/Bestand.class.php';

/**
 * Afbeelding.class.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 * 
 */
class Afbeelding extends Bestand {

	/**
	 * Breedte in pixels
	 * @var int
	 */
	public $width;
	/**
	 * Hoogte in pixels
	 * @var int
	 */
	public $height;
	/**
	 * Mime-types van afbeeldingen
	 * @var array
	 */
	public static $mimeTypes = array('image/png', 'image/jpeg', 'image/gif');

	public function __construct($path, $parse = true) {
		parent::__construct();
		if ($parse) {
			$this->filesize = @filesize($path);
			$image = @getimagesize($path);
			if ($image AND $this->filesize !== false) {
				$this->width = $image[0];
				$this->height = $image[1];
				$this->mimetype = $image['mime'];
			} else {
				throw new Exception('Afbeelding parsen mislukt: ' . $path);
			}
		}
	}

}
