/**
 * Selecteer de tekst van een DOM-element.
 * @source http://stackoverflow.com/questions/985272/
 *
 * @see templates/fotoalbum/album.tpl
 */
export function selectText(elmnt: HTMLElement): void {
	const selection = window.getSelection();

	if (!selection) {
		throw new Error("Geen getSelection in window")
	}

	const range = document.createRange();
	range.selectNodeContents(elmnt);
	selection.removeAllRanges();
	selection.addRange(range);
}

/**
 *  discuss at: http://phpjs.org/functions/dirname/
 * original by: Ozh
 * improved by: XoraX (http://www.xorax.info)
 *   example 1: dirname('/etc/passwd');
 *   returns 1: '/etc'
 *   example 2: dirname('c:/Temp/x');
 *   returns 2: 'c:/Temp'
 *   example 3: dirname('/dir/test/');
 *   returns 3: '/dir'
 */
export function dirname(path: string): string {
	return path.replace(/\\/g, '/')
		.replace(/\/[^/]*\/?$/, '');
}

export function basename(path: string, suffix = ''): string {
	//  discuss at: http://phpjs.org/functions/basename/
	// original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// improved by: Ash Searle (http://hexmen.com/blog/)
	// improved by: Lincoln Ramsay
	// improved by: djmix
	// improved by: Dmitry Gorelenkov
	//   example 1: basename('/www/site/home.htm', '.htm');
	//   returns 1: 'home'
	//   example 2: basename('ecra.php?p=1');
	//   returns 2: 'ecra.php?p=1'
	//   example 3: basename('/some/path/');
	//   returns 3: 'path'
	//   example 4: basename('/some/path_ext.ext/','.ext');
	//   returns 4: 'path_ext'

	let base = path;
	const lastChar = base.charAt(base.length - 1);

	if (lastChar === '/' || lastChar === '\\') {
		base = base.slice(0, -1);
	}

	base = base.replace(/^.*[/\\]/g, '');

	if (suffix !== '' && base.substr(base.length - suffix.length) === suffix) {
		base = base.substr(0, base.length - suffix.length);
	}

	return base;
}

export function route(path: string, cb: () => void): void {
	if (window.location.pathname.startsWith(path)) {
		cb();
	}
}

/**
 * Verwerk een multipliciteit in de vorm van `== 1` of `!= 0` of `> 3` voor de selecties
 */
export function evaluateMultiplicity(expression: string, num: number): boolean {
	// Altijd laten zien bij geen expressie
	if (expression.length === 0) {
		return true;
	}

	const [expressionOperator, expressionAantalString] = expression.split(' ');

	const expressionAantal = parseInt(expressionAantalString, 10);

	const mapOperationToFunction: { [op: string]: (a: number, b: number) => boolean } = {
		'!=': (a, b) => a !== b,
		'<': (a, b) => a < b,
		'<=': (a, b) => a <= b,
		'==': (a, b) => a === b,
		'>': (a, b) => a > b,
		'>=': (a, b) => a >= b,
	};

	return mapOperationToFunction[expressionOperator](num, expressionAantal);
}

export function formatFilesize(data: string): string {
	const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	let i = 0;
	let size = Number(data);
	while (size >= 1024) {
		size /= 1024;
		++i;
	}
	return size.toFixed(1) + ' ' + units[i];
}

export function formatBedrag(data: number): string {
	if (data > 0) {
		return '€' + (data / 100).toFixed(2);
	} else {
		return '-€' + (data / -100).toFixed(2);
	}
}

export function singleLineString(strings: TemplateStringsArray, ...values: string[]): string {
	// Interweave the strings with the
	// substitution vars first.
	let output = '';
	for (let i = 0; i < values.length; i++) {
		output += strings[i] + values[i];
	}
	output += strings[values.length];

	// Split on newlines.
	const lines = output.split(/(?:\r\n|\n|\r)/);

	// Rip out the leading whitespace.
	return lines.map((line) => line.replace(/^\s+/gm, '')).join(' ').trim();
}

export function html(strings: TemplateStringsArray, ...values: Array<string | undefined | null>): HTMLElement {
	let output = '';
	for (let i = 0; i < values.length; i++) {
		output += strings[i] + values[i];
	}
	output += strings[values.length];

	return (new DOMParser().parseFromString(output, 'text/html').body.firstChild) as HTMLElement;
}

export function htmlParse(htmlString: string): Node[] {
	return jQuery.parseHTML(htmlString, null, true) as Node[];
}

export function preloadImage(url: string, callback: () => void): void {
	const img = new Image();
	img.src = url;
	img.onload = callback;
}

export function parseData(el: HTMLElement): Record<string, unknown> {
	const data = el.dataset;

	const out: Record<string, unknown> = {};

	for (const item of Object.keys(data)) {
		if (data[item] === 'false') {
			out[item] = false;
		} else if (data[item] === 'true') {
			out[item] = true;
		} else if (!isNaN(Number(data[item]))) {
			out[item] = Number(data[item]);
		} else {
			out[item] = data[item];
		}
	}

	return out;
}

export function htmlEncode(str: string): string {
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

export function ontstuiter(func: (...args: unknown[]) => unknown, wait: number, immediate: boolean): (...args: unknown[]) => void {
	let timeout: number | undefined;
	return function (this: unknown, ...args: unknown[]) {
		const later = () => {
			timeout = undefined;
			if (!immediate) {
				func.apply(this, args);
			}
		};
		const callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = window.setTimeout(later, wait);
		if (callNow) {
			func.apply(this, args);
		}
	};
}

export function docReady(fn: () => void): void {
	if (document.readyState === 'complete' || document.readyState === 'interactive') {
		fn();
	} else {
		document.addEventListener('DOMContentLoaded', fn);
	}
}

export function isLoggedIn(): boolean {
	const elem = document.querySelector('meta[property=\'X-CSR-LOGGEDIN\']');
	if (!elem) {
		return false;
	}
	return elem.getAttribute('content') === 'true';
}

export function throwError(message: string): void {
	throw new Error(message)
}


/**
 * Voer de meegegeven functie éénmaal uit.
 * @param func
 */
export const once = <T extends unknown[], U>(func: (...args: T) => U): (...args: T) => U => {
	let called = false;
	let returnValue: U;
	return (...args: T): U => {
		if (!called) {
			called = true;
			returnValue = func(...args)
		}

		return returnValue
	}
}

export const wait = (ms: number): Promise<void> => {
	return new Promise(resolve =>  setTimeout(resolve, ms))
}

export const fadeAway = async (el: HTMLElement, ms: number): Promise<void> => {
	const transitionValue = `opacity ${ms}ms`
	if (el.style.transition) {
		el.style.transition += `, ${transitionValue}`
	} else {
		el.style.transition = transitionValue
	}

	el.style.opacity = "0";

	await wait(ms)

	el.remove()
}

// Grote beunmethode om te zien of we een light theme hebben.
export const isLightMode = (): boolean => {
	const bgColor = window.getComputedStyle(document.body).backgroundColor;

	const sep = bgColor.indexOf(',') > -1 ? ',' : ' ';
	const rgb = bgColor.substr(4).split(')')[0].split(sep);

	return (Number(rgb[0]) > 124 && Number(rgb[1]) > 124 && Number(rgb[2]) > 124)
}
