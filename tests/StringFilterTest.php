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

use curlient\StringFilter;
use PHPUnit\Framework\TestCase;

class StringFilterTest extends TestCase {
	public function testGrab() {
		$grabber  = StringFilter::getInstance();
		$config[] = ['sub', 'i'];
		self::assertEquals('hao', $grabber->getContent('nihao', $config));
		$config2[] = ['sub', '', 'h'];
		self::assertEquals('ni', $grabber->getContent('nihao', $config2));
		$config1[] = ['sub', '<body class="a">', '</body>'];
		self::assertEquals('abc', $grabber->getContent('<body class="a">abc</body>', $config1));

		$config1[] = ['replace', 'a', 'b'];
		self::assertEquals('bbc', $grabber->getContent('<body class="a">abc</body>', $config1));

		$config1[] = ['replace', '/b+/', 'c', 1];
		self::assertEquals('cc', $grabber->getContent('<body class="a">abc</body>', $config1));

		$config1[] = ['sub'];
		self::assertEquals('cc', $grabber->getContent('<body class="a">abc</body>', $config1));
	}
}
