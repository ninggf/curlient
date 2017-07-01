<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace crawler\tests;

use curlient\crawler\ListCrawler;
use PHPUnit\Framework\TestCase;

class ListPageCrawlerTest extends TestCase {
	public function testparseListPageURL() {
		// 常规地址列表.
		$rst = ListCrawler::parseListPageURL(['list' => ['1', '2']]);
		self::assertContains('1', $rst);
		self::assertContains('2', $rst);
		//数值增减型
		$rst = ListCrawler::parseListPageURL(['expression' => 'abc/{{0,10,1,1,0}}.html']);
		self::assertEquals(10, count($rst));
		self::assertEquals('abc/10.html', $rst[0]);
		self::assertEquals('abc/1.html', $rst[9]);

		$rst = ListCrawler::parseListPageURL(['expression' => 'abc/{{0,5,10,2,2}}.html']);
		self::assertEquals(3, count($rst));
		self::assertEquals('abc/05.html', $rst[0]);
		self::assertEquals('abc/09.html', $rst[2]);
	}

	public function testGrabList() {
		$crawler = new ListCrawler();
		$config  = [
					'url'=>'http://www.ichong123.com/xuanchong',
					'list'=>[//列表页取内容规则
		                     'wrapper'=>['<ul class="conlist cf">','</ul>'],//start:开始字符; end: 结束字符,
		                     'pages' => ['<h3><a target="_blank" href="(*)">','(*1)'] ,//内容页URL
		                     'fields'=>[
			                     '缩略图.before'=>[
				                     ['concat','<img border="0" src="(*)">']
			                     ],
		                         '描述'=>[
		                         	['concat','<p>(*)<a']
		                         ]
		                     ],//提取字符
		                     'includes'=>[],//链接中要包含字符串
		                     'excludes'=>[]//链接中不能包含字符串
					],
		            'conf'=>[
		            	'cookie'=>[],
		                'header'=>[]
		            ]];
		$rst = $crawler->crawl($config);
		self::assertNotEmpty($rst);
		self::assertArrayHasKey('缩略图',$rst[0]['fields']);
		self::assertArrayHasKey('描述',$rst[0]['fields']);
	}
	public function testJsonData(){
		$crawler = new ListCrawler();
		$config  = [
			'url'=>'http://m.cp.360.cn/kaijiang/qkjlist?lotId=265108&page=1',
			'list'=>[//列表页取内容规则
			         'json'=>true,
			         'pages' => ['list[{$i}].Issue'] ,//内容页URL
			         'fields'=>[
				         '缩略图'=> [
					         ['json','list[{$i}].UpdateTime']
				         ]
			         ],//提取字符
			         'includes'=>[],//链接中要包含字符串
			         'excludes'=>['83x','de'] //链接中不能包含字符串
			],
			'conf'=>[
				'cookie'=>[],
				'header'=>[]
			]];
		$rst = $crawler->crawl($config);
		self::assertNotEmpty($rst);
		self::assertArrayHasKey('缩略图',$rst[0]['fields']);
	}
}
