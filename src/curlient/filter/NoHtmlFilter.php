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

class NoHtmlFilter implements IFieldFilter {
	public function name() {
		return _t('Remove Html@crawler');
	}

	public function filter($content, $args) {
		if ($args && $args[0]) {
			$tags = str_replace(',', '|', $args[0]);
			if ($tags) {
				return preg_replace('#</?' . $tags . '[^>]*>#iums', '', $content);
			}
		}

		return preg_replace('#<[^>]*>#iums', '', $content);
	}
}