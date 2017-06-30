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

use curlient\filter\NoHtmlFilter;
use PHPUnit\Framework\TestCase;

class NoHtmlFilterTest extends TestCase {
	public function testNoHtml() {
		$nohtml = new NoHtmlFilter();

		self::assertEquals('nihao', $nohtml->filter("<a href=adsfasdf cladfax>nihao</A>", []));
		self::assertEquals("\nni\nhao\n", $nohtml->filter("<a href=adsfasdf cladfax>\nni\nhao</A>\n", []));
	}
}
