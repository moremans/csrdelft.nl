<?php

/**
 * Paging.interface.php
 * 
 * @author P.W.G. Brussee <brussee@live.nl>
 *
 * Paginering van items.
 * 
 */
interface Paging {

	public function getAantalPerPagina();

	public function getHuidigePagina();

	public function setHuidigePagina($int, $voor);

	public function getAantalPaginas($voor);
}
