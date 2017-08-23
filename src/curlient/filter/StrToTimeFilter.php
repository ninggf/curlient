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

class StrToTimeFilter implements IFieldFilter {
	public function name() {
		return _t('StrToTime@crawler');
	}

	public function filter($content, $args) {
		if ($content && is_string($content)) {
			return @strtotime($content);
		}

		return 0;
	}
}