<?php

/*
 * index.php	| 	Jan Pieter Waagmeester (jieter@jpwaag.com)
 *
 * Documentenketzerding.
 */
require_once 'configuratie.include.php';

require_once 'documenten/documentcontroller.class.php';

if (isset($_GET['querystring'])) {
	$docControl = new DocumentController($_GET['querystring']);
} else {
	die('epic fail');
}

$pagina = new CsrLayoutPage($docControl->getContent());
$pagina->addStylesheet('js/datatables/css/datatables_basic.css');
$pagina->addStylesheet('documenten.css');

$pagina->addScript('datatables/jquery.dataTables.min.js');
$pagina->addScript('documenten.js');

$pagina->view();
?>