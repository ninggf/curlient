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

use curlient\filter\JsonFilter;
use PHPUnit\Framework\TestCase;

class JsonFilterTest extends TestCase {
	public function testJson() {

		$json = new JsonFilter();

		$data = json_encode(['a' => ['b' => '1', 'd.d' => 'd.d'], 'c' => '1', 'e' => ['e1', 'e2', 'e3', ['e4' => 'e4']], 'f.g.h' => 'fgh']);
		self::assertEquals('1', $json->filter($data, ['a.b']));
		self::assertEquals('1', $json->filter($data, ['c']));
		self::assertEquals('e1', $json->filter($data, ['e[]']));
		self::assertEquals('e2', $json->filter($data, ['e[1]']));
		self::assertEquals('e3', $json->filter($data, ['e[-2]']));
		self::assertEquals('fgh', $json->filter($data, ['"f.g.h"']));
		self::assertEquals('e4', $json->filter($data, ['e[-1].e4']));
		self::assertEquals('d.d', $json->filter($data, ['a."d.d"']));
		$data = json_encode([['a' => 'a'], ['a' => 'b']]);
		self::assertEquals('a', $json->filter($data, ['[].a']));
		self::assertEquals('a', $json->filter($data, ['[0].a']));

		self::assertEmpty($json->filter($data, ['a.e.g']));
	}
}
