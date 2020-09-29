import FunctionColumnRender = DataTables.FunctionColumnRender;
import moment from 'moment';
import {formatBedrag, formatFilesize} from '../lib/util';
import {getApiFromSettings} from './api';

/**
 * Standaard gedefinieerde render functies.
 */
export default {
	default(data, type) {
		if (data === null || typeof data !== 'object') { return data; }
		switch (type) {
			case 'sort':
				return data.sort;
			case 'export':
				return data.export;
			default:
				return data.display;
		}
	},
	bedrag(data) {
		return formatBedrag(data);
	},
	check(data) {
		return '<span class="ico ' + (data ? 'tick' : 'cross') + '"></span>';
	},
	aanmeldFilter(data) {
		return data ? `<span class="ico group_key" title="Aanmeld filter actief: '${data}'"></span>` : '';
	},
	aanmeldingen(data, type, row) {
		return (row.aantal_aanmeldingen || 0) + ' (' + row.aanmeld_limiet + ')';
	},
	totaalPrijs(data, type, row) {
		return formatBedrag(row.aantal_aanmeldingen * parseInt(row.prijs, 10));
	},
	date(data) {
		const datum = moment(data);
		if (datum.isValid()) {
			return datum.format('L');
		}

		return data;
	},
	time(data) {
		const tijd = moment(data);
		if (tijd.isValid()) {
			return tijd.format('LT');
		}

		return data;
	},
	datetime(date) {
		if (Number(date) == date) {
			return moment(date * 1000).format('YYYY-MM-DD HH:mm');
		}

		if (!date) {
			return '';
		}

		if (date.substr(0, 5) === '<time') {
			return date;
		}

		const datumTijd = moment(date);

		if (datumTijd.isValid()) {
			return moment(date).format('YYYY-MM-DD HH:mm');
		}

		return date;
	},
	timeago(data, type, row, meta) {
		const api = getApiFromSettings(meta.settings);
		const cell = api.cell(meta.row, meta.col).node().firstChild as HTMLTimeElement;

		switch (type) {
			case 'sort':
			case 'export':
				return cell.dateTime;
			default:
				return data;
		}
	},
	filesize(data, type) {
		switch (type) {
			case 'sort':
				return Number(data);
			default:
				return formatFilesize(data);
		}
	},
} as { [s: string]: FunctionColumnRender };
