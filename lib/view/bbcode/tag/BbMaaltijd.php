<?php

namespace CsrDelft\view\bbcode\tag;

use CsrDelft\common\CsrException;
use CsrDelft\model\maalcie\MaaltijdAanmeldingenModel;
use CsrDelft\model\maalcie\MaaltijdenModel;
use CsrDelft\model\security\LoginModel;
use CsrDelft\view\maalcie\persoonlijk\MaaltijdKetzerView;

/**
 * Geeft een maaltijdketzer weer met maaltijdgegevens, aantal aanmeldingen en een aanmeldknopje.
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 27/03/2019
 * @example [maaltijd=next]
 * @example [maaltijd=1234]
 * @example [maaltijd]next[/maaldijd]
 * @example [maaltijd]123[/maaltijd]
 */
class BbMaaltijd extends BbTag {

	public function getTagName() {
		return 'maaltijd';
	}

	public function parseLight($arguments = []) {

		if (isset($arguments['maaltijd'])) {
			$mid = $arguments['maaltijd'];
		} else {
			$mid = $this->getContent();
		}
		$mid = trim($mid);
		$maaltijd2 = null;

		try {
			if ($mid === 'next' || $mid === 'eerstvolgende' || $mid === 'next2' || $mid === 'eerstvolgende2') {
				$maaltijden = MaaltijdenModel::instance()->getKomendeMaaltijdenVoorLid(LoginModel::getUid()); // met filter
				$aantal = sizeof($maaltijden);
				if ($aantal < 1) {
					return '<div class="bb-block bb-maaltijd">Geen aankomende maaltijd.</div>';
				}
				$maaltijd = reset($maaltijden);
				if (endsWith($mid, '2') && $aantal >= 2) {
					unset($maaltijden[$maaltijd->maaltijd_id]);
					$maaltijd2 = reset($maaltijden);
				}
			} elseif (preg_match('/\d+/', $mid)) {
				$maaltijd = MaaltijdenModel::instance()->getMaaltijdVoorKetzer((int)$mid); // met filter
				if (!$maaltijd) {
					return '';
				}
			}
		} catch (CsrException $e) {
			if (strpos($e->getMessage(), 'Not found') !== false) {
				return '<div class="bb-block bb-maaltijd">Maaltijd niet gevonden: ' . htmlspecialchars($mid) . '</div>';
			}
			return $e->getMessage();
		}
		if (!isset($maaltijd)) {
			return '<div class="bb-block bb-maaltijd">Maaltijd niet gevonden: ' . htmlspecialchars($mid) . '</div>';
		}
		$url = $maaltijd->getUrl() . '#' . $maaltijd->maaltijd_id;
		return $this->lightLinkBlock('maaltijd', $url, $maaltijd->titel, $maaltijd->datum . ' ' . $maaltijd->tijd);
	}

	public function parse($arguments = []) {
		if (isset($arguments['maaltijd'])) {
			$mid = $arguments['maaltijd'];
		} else {
			$mid = $this->getContent();
		}
		$mid = trim($mid);
		$maaltijd2 = null;

		try {
			if ($mid === 'next' || $mid === 'eerstvolgende' || $mid === 'next2' || $mid === 'eerstvolgende2') {
				$maaltijden = MaaltijdenModel::instance()->getKomendeMaaltijdenVoorLid(LoginModel::getUid()); // met filter
				$aantal = sizeof($maaltijden);
				if ($aantal < 1) {
					return '<div class="bb-block bb-maaltijd">Geen aankomende maaltijd.</div>';
				}
				$maaltijd = reset($maaltijden);
				if (endsWith($mid, '2') && $aantal >= 2) {
					unset($maaltijden[$maaltijd->maaltijd_id]);
					$maaltijd2 = reset($maaltijden);
				}
			} elseif (preg_match('/\d+/', $mid)) {
				$maaltijd = MaaltijdenModel::instance()->getMaaltijdVoorKetzer((int)$mid); // met filter
				if (!$maaltijd) {
					return '';
				}
			}
		} catch (CsrException $e) {
			if (strpos($e->getMessage(), 'Not found') !== false) {
				return '<div class="bb-block bb-maaltijd">Maaltijd niet gevonden: ' . htmlspecialchars($mid) . '</div>';
			}
			return $e->getMessage();
		}
		if (!isset($maaltijd)) {
			return '<div class="bb-block bb-maaltijd">Maaltijd niet gevonden: ' . htmlspecialchars($mid) . '</div>';
		}
		$aanmeldingen = MaaltijdAanmeldingenModel::instance()->getAanmeldingenVoorLid(array($maaltijd->maaltijd_id => $maaltijd), LoginModel::getUid());
		if (empty($aanmeldingen)) {
			$aanmelding = null;
		} else {
			$aanmelding = $aanmeldingen[$maaltijd->maaltijd_id];
		}
		$ketzer = new MaaltijdKetzerView($maaltijd, $aanmelding);
		$result = $ketzer->getHtml();

		if ($maaltijd2 !== null) {
			$aanmeldingen2 = MaaltijdAanmeldingenModel::instance()->getAanmeldingenVoorLid(array($maaltijd2->maaltijd_id => $maaltijd2), LoginModel::getUid());
			if (empty($aanmeldingen2)) {
				$aanmelding2 = null;
			} else {
				$aanmelding2 = $aanmeldingen2[$maaltijd2->maaltijd_id];
			}
			$ketzer2 = new MaaltijdKetzerView($maaltijd2, $aanmelding2);
			$result .= $ketzer2->getHtml();
		}
		return $result;
	}
}
