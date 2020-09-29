/**
 * Laad alle externe libs en knoop de goede dingen aan elkaar.
 */
import Bloodhound from 'corejs-typeahead';
import Dropzone from 'dropzone';
import $ from 'jquery';
import moment from 'moment';
import {
	registerBbContext,
	registerDataTableContext, registerFlatpickrContext,
	registerFormulierContext,
	registerGlobalContext,
	registerGrafiekContext,
	registerKnopContext,
} from './context';
import {init} from './ctx';
import {ketzerAjax} from './lib/ajax';
import {importAgenda} from './lib/courant';
import {initSluitMeldingen} from './lib/csrdelft';
import {domUpdate} from './lib/domUpdate';
import {formCancel, formInlineToggle, formSubmit, insertPlaatje} from './lib/formulier';
import {forumBewerken, saveConceptForumBericht} from './lib/forum';
import {takenColorSuggesties, takenShowOld, takenToggleDatum, takenToggleSuggestie} from './lib/maalcie';
import {docReady} from './lib/util';
import hoverintent from 'hoverintent'

moment.locale('nl');

window.$ = window.jQuery = $;

/**
 * jQuery extensies registreren zichzelf aan bovenstaande jQuery.
 */
require('bootstrap');
require('./ajax-csrf');
require('jquery.scrollto');
require('jquery-ui');
require('jquery-ui/ui/effect');
require('jquery-ui/ui/effects/effect-highlight');
require('jquery-ui/ui/effects/effect-fade');
require('jquery-ui/ui/widgets/slider');
require('./lib/external/jquery.markitup');
require('./lib/external/jquery.contextMenu');
require('timeago');
require('raty-js');
require('jquery.maskedinput');
require('lightbox2');
require('corejs-typeahead/dist/typeahead.jquery.js');

/**
 * Globale objecten gebruikt in PHP code.
 */
$.extend(window, {
	Bloodhound,
	Dropzone,
	docReady,
	hoverintent,
	context: {
		// See view/groepen/leden/GroepTabView.class.php
		domUpdate,
		// See view/formulier/invoervelden/LidField.class.php
		init: (el: HTMLElement) => init(el),
	},
	courant: {
		// See templates/courant/courantbeheer.tpl
		importAgenda,
	},
	formulier: {
		// See view/formulier/invoervelden/InputField.abstract.php
		formCancel,
		// See templates/instellingen/beheer/instelling_row.tpl
		formInlineToggle,
		// See view/formulier/invoervelden/InputField.abstract.php
		// See view/formulier/invoervelden/ZoekField.class.php
		formSubmit,
		insertPlaatje,
	},
	forum: {
		// See templates/forum/partial/post_lijst.html.twig
		forumBewerken,
		// See templates/forum/partial/post_forum.html.twig
		saveConceptForumBericht,
	},
	// See templates/maaltijden/bb.html.twig
	ketzerAjax,
	maalcie: {
		// See view/maalcie/forms/SuggestieLijst.php
		takenColorSuggesties,
		// See templates/maaltijden/corveetaak/beheer_taken.html.twig
		takenShowOld,
		// See templates/maaltijden/corveetaak/beheer_taak_datum.html.twig
		// See templates/maaltijden/corveetaak/beheer_taak_head.html.twig
		takenToggleDatum,
		// See templates/maaltijden/corveetaak/suggesties_lijst.html.twig
		// See view/maalcie/forms/SuggestieLijst.php
		takenToggleSuggestie,
	},
});

Dropzone.autoDiscover = false;

$.timeago.settings.strings = {
	day: '1 dag',
	days: '%d dagen',
	hour: '1 uur',
	hours: '%d uur',
	minute: '1 minuut',
	minutes: '%d minuten',
	month: '1 maand',
	months: '%d maanden',
	numbers: [],
	prefixAgo: '',
	inPast: '',
	prefixFromNow: 'sinds',
	seconds: 'nog geen minuut',
	suffixAgo: 'geleden',
	suffixFromNow: '',
	wordSeparator: ' ',
	year: '1 jaar',
	years: '%d jaar',
};

(async () => {
	await Promise.all([
		registerGrafiekContext(),
		registerFormulierContext(),
		registerGlobalContext(),
		registerKnopContext(),
		registerDataTableContext(),
		registerBbContext(),
		registerFlatpickrContext(),
	]);

	docReady(() => {
		initSluitMeldingen();
		init(document.body);

		const modal = $('#modal');
		if (modal.html() !== '') {
			modal.modal();
		}
	});
})();
