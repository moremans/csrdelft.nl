<?php
namespace CsrDelft\view\bbcode\tag;

use CsrDelft\view\bbcode\Parser;

abstract class BbTag {
	/**
	 * @var Parser
	 */
	protected $parser;

	/**
	 * Wordt meegegeven aan alle tags, is in deze parse-sessie uniek.
	 *
	 * @var \stdClass
	 */
	protected $env;

	public function __construct(Parser $parser, $env) {
		$this->parser = $parser;
		$this->env = $env;
	}

	/**
	 * Probeer deze tag uit te lezen.
	 *
	 * @param string[] $forbidden
	 * @return string|null
	 */
	protected function getContent($forbidden = []) {
		$stoppers = [];

		if (is_array($this->getTagName())) {
			foreach ($this->getTagName() as $tagName) {
				$stoppers[] = $this->createStopper($tagName);
			}
		} else {
			$stoppers[] = $this->getTagName();
		}

		return $this->parser->parseArray($stoppers, $forbidden);
	}

	private function createStopper($tagName) {
		return "[/$tagName]";
	}

	/**
	 * Templates for light mode
	 */
	protected function lightLinkInline($tag, $url, $content) {
		if ($this->env->email_mode && isset($url[0]) && $url[0] === '/') {
			// Zorg voor werkende link in e-mail
			$url = CSR_ROOT . $url;
		}

		return <<<HTML
			<a class="bb-link-inline bb-tag-{$tag}" href="{$url}">{$content}</a>
HTML;
	}
	protected function lightLinkBlock($tag, $url, $titel, $beschrijving, $thumbnail = '') {
		$titel = htmlspecialchars($titel);
		$beschrijving = htmlspecialchars($beschrijving);
		if ($thumbnail !== '') {
			$thumbnail = '<img src="' . $thumbnail . '" />';
		}
		return <<<HTML
			<a class="bb-link-block bb-tag-{$tag}" href="{$url}">
				{$thumbnail}
				<h2>{$titel}</h2>
				<p>{$beschrijving}</p>
			</a>
HTML;
	}

	protected function lightLinkThumbnail($tag, $url, $thumbnail) {
		return <<<HTML
			<a class="bb-link-thumbnail bb-tag-{$tag}" href="{$url}">
				<img src="{$thumbnail}" />
			</a>
HTML;
	}

	function video_preview(array $params, $previewthumb) {
		$params = json_encode($params);

		$html = <<<HTML
<div class="bb-video">
	<div class="bb-video-preview" onclick="event.preventDefault();window.bbcode.bbvideoDisplay(this);" data-params='{$params}' title="Klik om de video af te spelen">
		<div class="play-button fa fa-play-circle-o fa-5x"></div>
		<div class="bb-img-loading" src="{$previewthumb}"></div>
	</div>
</div>
HTML;

		return $html;
	}

	abstract public function getTagName();
	abstract public function parse($arguments = []);
	public function parseLight($arguments = []) {
		return $this->parse($arguments);
	}
}
