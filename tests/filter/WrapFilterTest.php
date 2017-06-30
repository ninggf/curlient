<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace tests\filter;

use curlient\filter\WrapFilter;
use PHPUnit\Framework\TestCase;

class WrapFilterTest extends TestCase {
	public function testWrap() {
		$wrap = new WrapFilter();

		self::assertEquals('1abc2', $wrap->filter('abc', ['1', '2']));
		self::assertEquals('abc2', $wrap->filter('abc', ['', '2']));
		self::assertEquals('1abc', $wrap->filter('abc', ['1']));
	}
}
