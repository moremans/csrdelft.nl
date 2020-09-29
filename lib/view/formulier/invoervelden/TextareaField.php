<?php

namespace CsrDelft\view\formulier\invoervelden;

/**
 * @author P.W.G. Brussee <brussee@live.nl>
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 30/03/2017
 *
 * Een Textarea die groter wordt als de inhoud niet meer in het veld past.
 */
class TextareaField extends TextField {

	protected $wrapperClassName = 'form-group';
	protected $labelClassName = '';
	protected $fieldClassName = '';


	public function __construct($name, $value, $description, $rows = 2, $max_len = null, $min_len = null) {
		parent::__construct($name, $value, $description, $max_len, $min_len);
		if (is_int($rows)) {
			$this->rows = $rows;
		}
		$this->css_classes[] = 'AutoSize';
		$this->css_classes[] = 'textarea-transition';
	}

	public function getHtml() {
		return '<textarea' . $this->getInputAttribute(array('id', 'name', 'origvalue', 'class', 'disabled', 'readonly', 'placeholder', 'maxlength', 'rows', 'autocomplete')) . '>' . $this->value . '</textarea>';
	}
}
