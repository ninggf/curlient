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

use curlient\StringFilter;
use PHPUnit\Framework\TestCase;

class QueryFilterTest extends TestCase {

	public function testQuery() {
		$html = <<<'HTML'
<!DOCTYPE html>
<html>
    <body>
        <p class="message">Hello World!</p>
        <p>Hello Crawler!</p>
        <div>
            <div>1</div>
            <div>
                <p>good</p>
            </div>
            <div>hi</div>
            <div title="abc">title</div>
        </div>
        <span id="abc">abc</span>
    </body>
</html>
HTML;

		$filter   = StringFilter::getInstance();
		$config[] = ['query', 'body > p:first-child'];
		$config[] = ['attr', 'class'];
		$p        = $filter->filter($html, $config);
		self::assertEquals('message', $p);
		$config   = [];
		$config[] = ['query', 'body > p:last-child'];
		$config[] = ['attr', 'text'];
		$p        = $filter->filter($html, $config);
		self::assertEquals('Hello Crawler!', $p);

		$config   = [];
		$config[] = ['query', 'div > div:nth-child(2) p'];
		$config[] = ['attr', 'text'];
		$p        = $filter->filter($html, $config);
		self::assertEquals('good', $p);

		$config   = [];
		$config[] = ['query', '#abc'];
		$config[] = ['attr', 'text'];
		$p        = $filter->filter($html, $config);
		self::assertEquals('abc', $p);

		$config   = [];
		$config[] = ['query', 'div[title="abc"]','text'];
		$p        = $filter->filter($html, $config);
		self::assertEquals('title', $p);
	}
}
