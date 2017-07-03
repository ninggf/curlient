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

use curlient\crawler\PageCrawler;
use PHPUnit\Framework\TestCase;

class ContentPageCrawlerTest extends TestCase {
	public function testCrawl() {
		$crawler = new PageCrawler();
		$conf    = [
			'url'=>'http://www.chinacaipu.com/menu/rouleishipu/152635.html',
		    'page'=>[
				'concat'=>'内容',
		        'concatStr'=>'[:page:]',
				'fields'=>[
					'标题'=>[
						['sub','<title>','</title>']
					],
				    '内容'=>[
				    	['sub','<div class="content" id="content">','<div id="xgyd">'],
				        ['nohtml','a'],
				        ['nostyle']
				    ]
				],//字段
		        'pager'=>[
		        	'wrapper'=>['<div id="pagestring">','</div>'],
		            'type'=>'next',
		            'pages'=>["<a title='下一页' href='(*)'",'(*1)']
		        ]//分页
		    ],
		    'conf'=>[
				'interval'=>0
		    ]
		];
		$rst     = $crawler->crawl($conf);

		self::assertNotEmpty($rst);
		self::assertArrayHasKey('内容',$rst['fields']);
		self::assertNotEmpty($rst['fields']['内容']);
		self::assertEquals('白萝卜炖筒子骨的做法_白萝卜炖筒子骨怎么做_菜谱网',$rst['fields']['标题']);
		self::assertEquals(16,count($crawler->pages));
	}

	public function testDom(){
		$crawler = new PageCrawler();
		$conf    = [
			'url'=>'http://www.chinacaipu.com/menu/rouleishipu/152635.html',
			'page'=>[
				'concat'=>'内容',
				'concatStr'=>'[:page:]',
				'dom'=>true,
				'fields'=>[
					'标题'=>[
						['query','head > title','text']
					],
					'内容'=>[
						['query','div#content','text'],
						['nohtml','a'],
						['nohtml','script,style,div',1],
						['nostyle']
					]
				],//字段
				'pager'=>[
					'type'=>'next',
					'pages'=>["div#pagestring > a[title='下一页']",'href']
				]//分页
			],
			'conf'=>[
				'interval'=>0
			]
		];
		$rst     = $crawler->crawl($conf);

		self::assertNotEmpty($rst);
		self::assertArrayHasKey('内容',$rst['fields']);
		self::assertNotEmpty($rst['fields']['内容']);
		self::assertEquals('白萝卜炖筒子骨的做法_白萝卜炖筒子骨怎么做_菜谱网',$rst['fields']['标题']);
		self::assertEquals(16,count($crawler->pages));
	}
}
