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

use curlient\filter\SubFilter;
use PHPUnit\Framework\TestCase;

class SubFilterTest extends TestCase {
	public function testSub() {
		$filter = new SubFilter();

		self::assertEquals('abc', $filter->filter('<body class="a">abc</body>', ['<body class="a">', '</body>']));
	}
}
