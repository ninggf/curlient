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
		@list($args, $type) = $args;
		if ($args) {
			if (empty($type)) {
				$tags = str_replace(',', '|', $args);
				if ($tags) {
					return preg_replace('#</?' . $tags . '[^>]*>#iums', '', $content);
				}
			} else {
				$tags = str_replace(',', '|', $args);
				if ($tags) {
					return preg_replace('#<(' . $tags . ')[^>]*>.*?</\1>#iums', '', $content);
				}
			}
		}

		return preg_replace('#<[^>]*>#iums', '', $content);
	}
}