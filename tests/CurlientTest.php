<?php

use curlient\Curlient;
use PHPUnit\Framework\TestCase;

class SpiderTest extends TestCase {
	/**
	 * @var \curlient\Curlient
	 */
	private $spider;

	protected function setUp() {
		$this->spider = new Curlient();
	}

	public function testRequestGet() {
		$req     = $this->spider->request('https://www.baidu.com/');
		$content = $req->text();
		self::assertNotEmpty($content);

		self::assertEquals('UTF-8', $req->encoding);
		self::assertEquals('https://www.baidu.com/', $req->referer);

		$cookies = $req->getCookies();
		self::assertNotEmpty($cookies);
		self::assertArrayHasKey('BDSVRTM', $cookies);

		$req->request('https://sp0.baidu.com/5a1Fazu8AA54nxGko9WTAnF6hhy/su?wd=php&json=1&p=3&sid={$H_PS_PSSID}&req=2&csor=3&pwd=ph&cb=jQuery110209400699396293535_1498552434684&_=1498552434698');

		$content = $req->text();

		self::assertNotEmpty($content);

		self::assertContains('jQuery', $content);
	}

	public function testRequests() {
		$client         = new Curlient();
		$cbs['onStart'] = function (Curlient $c) {
			if ($c->url == 'aaa') {
				return false;
			} else {
				return true;
			}
		};
		$results        = $client->requests(['https://www.baidu.com/', 'http://v1.wulacms.com/dashboard/login', 'http://v1.wulacms.com/dashboard/loginw', 'aaa'], $cbs);

		self::assertEquals(2, count($results[0]));

		self::assertEquals(1, count($results[1]));

		self::assertEquals(1, count($results[2]));

		self::assertArrayHasKey('phpsid', $results[0][1]->getCookies());

		self::assertEquals('UTF-8', $results[0][1]->encoding);
	}

	public function testGoodURL() {
		$base  = 'http://a.com/';
		$url[] = ['url' => 'http://a.com/a.html'];
		$url[] = ['url' => 'https://a.com/a.html'];
		$url[] = ['url' => 'ftp://a.com/a.html'];
		$url[] = ['url' => 'ftps://a.com/a.html'];
		$url[] = ['url' => '//a.com/a.html'];
		$url[] = ['url' => '/a.html'];
		$url[] = ['url' => 'a.html'];
		Curlient::goodURL($base, $url);
		self::assertEquals('http://a.com/a.html', $url[0]['url']);
		self::assertEquals('https://a.com/a.html', $url[1]['url']);
		self::assertEquals('ftp://a.com/a.html', $url[2]['url']);
		self::assertEquals('ftps://a.com/a.html', $url[3]['url']);

		self::assertEquals('http://a.com/a.html', $url[4]['url']);
		self::assertEquals('http://a.com/a.html', $url[5]['url']);
		self::assertEquals('http://a.com/a.html', $url[6]['url']);
	}

	public function testGoodURL1() {
		$base   = 'http://a.com/a/a.html';
		$urls[] = ['url' => './b.html'];
		$urls[] = ['url' => '../b.html'];
		Curlient::goodURL($base, $urls);
		self::assertEquals('http://a.com/a/./b.html', $urls[0]['url']);
		self::assertEquals('http://a.com/a/../b.html', $urls[1]['url']);
	}

	public function testGoodURL2() {
		$base   = 'http://a.com/a/b/';
		$urls[] = ['url' => './b.html'];
		$urls[] = ['url' => '../b.html'];
		Curlient::goodURL($base, $urls);
		self::assertEquals('http://a.com/a/./b.html', $urls[0]['url']);
		self::assertEquals('http://a.com/a/../b.html', $urls[1]['url']);
	}
}