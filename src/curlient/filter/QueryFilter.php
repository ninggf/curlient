<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace curlient\filter;

use curlient\IFieldFilter;
use Symfony\Component\DomCrawler\Crawler;

class QueryFilter implements IFieldFilter {
	public function name() {
		return _tr('Query@crawler');
	}

	public function filter($content, $args) {
		$rtn = $content;
		@list($selector, $attr, $sr) = $args;

		if ($selector) {
			if (is_array($attr)) {//替换与取值互换
				$sr   = $attr;
				$attr = null;
			}
			if ($sr) {
				$selector = str_replace($sr[0], $sr[1], $selector);
			}
			$selectors = explode(':', $selector);
			if (!$content instanceof Crawler) {
				$content = new Crawler($content);
			}
			$dom = $content;
			while ($selectors && $dom->count() > 0) {
				$selector = array_shift($selectors);
				if (strpos($selector, 'first-child') === 0) {
					$selector = trim(str_replace('first-child', '', $selector));
					$dom      = $dom->first();
					if ($selector) {
						$dom = $dom->filter($selector);
					}
				} else if (strpos($selector, 'last-child') === 0) {
					$selector = trim(str_replace('last-child', '', $selector));
					$dom      = $dom->last();
					if ($selector) {
						$dom = $dom->filter($selector);
					}
				} else if (strpos($selector, 'nth-child') === 0) {
					$selector = trim(str_replace('nth-child', '', $selector));
					if ($selector) {
						if (preg_match('#\(\s*([1-9]\d*)\s*\)(.*)#', $selector, $ms)) {
							$dom = $dom->eq($ms[1] - 1);
							if (isset($ms[2]) && $ms[2]) {
								$dom = $dom->filter($ms[2]);
								continue;
							}
						}
						$dom = new Crawler();
						break;
					} else {
						$dom = $dom->eq(0);
					}
				} else {
					$dom = $dom->filter($selector);
				}
			};
			$rtn = $dom;
			if ($attr && $dom->count() > 0) {
				switch ($attr) {
					case 'text':
						$rtn = $dom->text();
						break;
					case 'html':
						$rtn = $dom->html();
						break;
					default:
						$rtn = $dom->attr($attr);
				}
			}
		}

		return $rtn;
	}
}
