<?php

namespace CsrDelft\view\formulier\knoppen;

use CsrDelft\view\formulier\FormElement;
use CsrDelft\view\Icon;

/**
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 30/03/2017
 */
class FormulierKnop implements FormElement {

	protected $id;
	public $url;
	public $action;
	public $data;
	public $icon;
	public $label;
	public $title;
	public $css_classes = array('FormulierKnop');

	public function __construct($url, $action, $label, $title, $icon) {
		$this->id = uniqid_safe('knop_');
		$this->url = $url;
		$this->action = $action;
		$this->label = $label;
		$this->title = $title;
		$this->icon = $icon;
		$this->css_classes[] = $this->getType();
		$this->css_classes[] = 'btn btn-primary';
	}

	public function getId() {
		return $this->id;
	}

	public function getModel() {
		return null;
	}

	public function getBreadcrumbs() {
		return null;
	}

	public function getTitel() {
		return $this->getType();
	}

	public function getType() {
		return classNameZonderNamespace(get_class($this));
	}

	public function getHtml() {
		$this->css_classes[] = $this->action;
		$html = '<a id="' . $this->getId() . '" href="'.($this->url ?: '#').'" class="' . implode(' ', $this->css_classes) . '" title="' . htmlspecialchars($this->title) . '" tabindex="0"';
		if (isset($this->data)) {
			$html .= ' data="' . $this->data . '"';
		}
		if (strpos($this->action, 'cancel') !== false) {
			$html .= ' data-dismiss="modal"';
		}
		$html .= '>';
		if ($this->icon) {
			$html .= Icon::getTag($this->icon, null, null, 'mr-1');
		}
		$html .= $this->label;
		return $html . '</a> ';
	}

	public function view() {
		echo $this->getHtml();
	}

	public function getJavascript() {
		return <<<JS

/* {$this->getId()} */
JS;
	}

}
