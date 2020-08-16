<?php

namespace CsrDelft\view\login;

use CsrDelft\common\ContainerFacade;
use CsrDelft\service\security\LoginService;
use CsrDelft\view\formulier\elementen\HtmlComment;
use CsrDelft\view\formulier\Formulier;
use CsrDelft\view\formulier\invoervelden\HiddenField;
use CsrDelft\view\formulier\invoervelden\TextField;
use CsrDelft\view\formulier\invoervelden\WachtwoordField;
use CsrDelft\view\formulier\keuzevelden\CheckboxField;
use CsrDelft\view\formulier\knoppen\LoginFormKnoppen;

class LoginForm extends Formulier {

	public function __construct($showMelding = false) {
		parent::__construct(null, '/login');
		$this->formId = 'loginform';
		$this->showMelding = $showMelding;

		$fields = [];

		$redirectUri = filter_input(INPUT_GET, 'redirect', FILTER_UNSAFE_RAW);
		$fields['redirect'] = new HiddenField('redirect', $redirectUri);

		$fields['user'] = new TextField('user', null, null);
		$fields['user']->placeholder = 'Lidnummer of emailadres';

		$fields['pass'] = new WachtwoordField('pass', null, null);
		$fields['pass']->placeholder = 'Wachtwoord';

		if (ContainerFacade::getContainer()->get(LoginService::class)->hasError()) {
			$fields[] = new HtmlComment('<p class="error">' . ContainerFacade::getContainer()->get(LoginService::class)->getError() . '</p>');
		} else {
			$fields[] = new HtmlComment('<div class="float-left">');
			$fields[] = new HtmlComment('</div>');

			$fields['remember'] = new CheckboxField('remember', false, null, 'Blijf ingelogd');
		}

		$this->addFields($fields);

		$this->formKnoppen = new LoginFormKnoppen();
	}

	protected function getScriptTag() {
		// er is geen javascript
		return "";
	}
}
