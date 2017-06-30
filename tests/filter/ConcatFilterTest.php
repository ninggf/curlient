<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace curlient\tests\filter;

use curlient\filter\ConcatFilter;
use PHPUnit\Framework\TestCase;

class ConcatFilterTest extends TestCase {
	public function testConcat() {
		$concat = new ConcatFilter();

		$rst = $concat->filter('<a href="abc">aaa</a>', ['<a\s*href="(*)"']);
		self::assertEquals('abc', $rst);

		$rst = $concat->filter('<a href="abc">aaa</a>', ['<a\s*href="(arg)"']);
		self::assertEquals('abc', $rst);

		$rst = $concat->filter('<a href="abc">aaa</a>', ['<a\s*href="(arg)"', '(arg1)def']);
		self::assertEquals('abcdef', $rst);

		$rst = $concat->filter('<a href="abc">aaa</a>', ['<a\s*(*)="(arg)"', '(*1)=(arg2)']);
		self::assertEquals('href=abc', $rst);
	}

	public function testConcats() {
		$concat = new ConcatFilter();
		$rst    = $concat->filter('<a href="abc">aaa</a>adfasdf<a href="def">aaa</a>adsfeaf<a href="hig">aaa</a>adfasdfasdf', ['<a\s*href="(*)"', '(*1)', 1]);
		self::assertEquals(3, count($rst));

		self::assertContains('abc', $rst[0]);
		self::assertContains('def', $rst[1]);
		self::assertContains('hig', $rst[2]);
	}
}
