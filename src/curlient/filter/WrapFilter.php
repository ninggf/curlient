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

class WrapFilter implements IFieldFilter {
	public function filter($content, $args) {
		@list($start, $end) = $args;
		if ($start) {
			$content = $start . $content;
		}
		if ($end) {
			$content .= $end;
		}

		return $content;
	}

	public function name() {
		return _t('Wrap Filter@crawler');
	}
}