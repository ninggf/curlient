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

class SubFilter implements IFieldFilter {

	function name() {
		return _t('Sub@crawler');
	}

	public function filter($content, $args) {
		if (!empty($content)) {
			@list($start, $end) = $args;
			$startPos = $start ? mb_strpos($content, $start) : 0;
			$endPos   = $end ? mb_strpos($content, $end, $startPos) : null;
			if ($startPos || $endPos) {
				$startPos += mb_strlen($start);
			}
			$len = $endPos ? $endPos - $startPos : null;
			if ($startPos && $endPos) {
				$content = mb_substr($content, $startPos, $len);
			} else {
				$content = '';
			}
		}

		return $content;
	}
}