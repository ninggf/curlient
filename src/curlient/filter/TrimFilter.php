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

class TrimFilter implements IFieldFilter {
	public function name() {
		return _t('Trim Filter@crawler');
	}

	public function filter($content, $args) {
		$content = trim($content);
		if ($args) {
			$content = trim($content, $args[0]);
		}

		return trim($content);
	}

}