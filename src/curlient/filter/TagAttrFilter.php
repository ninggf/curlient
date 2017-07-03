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

class TagAttrFilter implements IFieldFilter {
	public function name() {
		return _tr('Tag Attribute Value@crawler');
	}

	public function filter($content, $args) {
		if ($content instanceof Crawler && $content->count() > 0) {
			@list($attr) = $args;
			if ($attr) {
				if ($attr == 'text') {
					return $content->text();
				} else if ($attr == 'html') {
					return $content->html();
				}

				return $content->attr($attr);
			}
		}

		return '';
	}
}