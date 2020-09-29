import axios from 'axios';
import Popper from 'popper.js';
import hoverintent from "hoverintent";
import {once} from "./util";

// Cache
const kaartjes: Record<string, HTMLElement> = {};

export const initKaartjes = (el: HTMLElement): void => {
	const uid = el.dataset.visite;

	if (!uid) {
		throw new Error("data-visite niet gezet op link")
	}

	if (!(uid in kaartjes)) {
		kaartjes[uid] = document.createElement('div');
		kaartjes[uid].style.zIndex = '1000';
	}

	el.addEventListener('mouseenter', once(async () =>
		kaartjes[uid].innerHTML = (await axios.get(`/profiel/${el.dataset.visite}/kaartje`)).data))

	hoverintent(el,
		() => {
			el.append(kaartjes[uid]);
			new Popper(el, kaartjes[uid], {placement: 'bottom-start'});
		},
		() => kaartjes[uid].remove()
	);
};
