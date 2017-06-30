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

class NoStyleFilter implements IFieldFilter {
	public function name() {
		return _t('Remove Style@crawler');
	}

	public function filter($content, $args) {
		return preg_replace('#\s*style\s*=\s*([\'"])[^\'"]*\1#iums', '', $content);
	}
}