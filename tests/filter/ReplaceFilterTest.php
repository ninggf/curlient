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

use curlient\filter\ReplaceFilter;
use PHPUnit\Framework\TestCase;

class ReplaceFilterTest extends TestCase {
	public function testFilter() {
		$filter = new ReplaceFilter();

		self::assertEquals('nihao', $filter->filter('wohao', ['wo', 'ni']));
		self::assertEquals('nihao', $filter->filter('wwwoohao', ['/w+o+/', 'ni', 1]));
	}
}
