<?php
/*
 * groep.php	| 	Jan Pieter Waagmeester (jieter@jpwaag.com)
 *
 *
 */


require_once('include.config.php');

require_once('groepen/class.groep.php');
require_once('groepen/class.groepcontent.php');
require_once('groepen/class.groepcontroller.php');

if(!isset($_GET['query'])){
	echo 'querystring niet aanwezig, dat gaat hiet werken (htdocs/groep.php)';
	exit;
}
$controller=new Groepcontroller($_GET['query']);


$pagina=new csrdelft($controller->getContent());

$pagina->addStylesheet('groepen.css');
$pagina->addScript('groepen.js');
$pagina->view();
?>