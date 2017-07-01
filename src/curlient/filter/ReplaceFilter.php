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

/**
 * 替换，支持正则.
 *
 * @package curlient\filter
 */
class ReplaceFilter implements IFieldFilter {
	public function name() {
		return _t('Replace Filter@crawler');
	}

	public function filter($content, $args) {
		@list($search, $replace, $reg) = $args;
		if ($reg) {
			return preg_replace($search, $replace, $content);
		} else {
			return str_replace($search, $replace, $content);
		}
	}
}