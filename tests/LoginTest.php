<?php


use CsrDelft\common\ContainerFacade;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends \Symfony\Component\Panther\PantherTestCase {
	public function testPageLoad() {
		$client = static::createPantherClient();
		ContainerFacade::init(self::$container);

		$client->request('GET', '/');

		$this->assertResponseIsSuccessful();
	}

	public function testLogin() {
		$client = static::createPantherClient(['webServerDir' => __DIR__ . '/../htdocs/']);

		$crawler = $client->request('GET', '/');

		$crawler->selectLink("Inloggen")->click();

		$form = $crawler->selectButton('Inloggen')->form();

		$form['_username'] = 'x101';
		$form['_password'] = 'stek open u voor mij!';

		$crawler = $client->submit($form);

		$pageContent = $crawler->filter('.cd-page-content')->text();

		$this->assertStringContainsString('Dit is de voorpagina.', $pageContent);
	}

}
