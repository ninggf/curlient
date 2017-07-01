<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace curlient;

use curlient\filter\ConcatFilter;
use curlient\filter\JsonFilter;
use curlient\filter\NoHtmlFilter;
use curlient\filter\NoStyleFilter;
use curlient\filter\ReplaceFilter;
use curlient\filter\SubFilter;
use curlient\filter\TrimFilter;
use curlient\filter\WrapFilter;

class StringFilter {
	private static $filters  = null;
	private static $INSTANCE = null;

	private function __construct() {
		self::$filters['sub']     = new SubFilter();
		self::$filters['replace'] = new ReplaceFilter();
		self::$filters['concat']  = new ConcatFilter();
		self::$filters['nohtml']  = new NoHtmlFilter();
		self::$filters['nostyle'] = new NoStyleFilter();
		self::$filters['wrap']    = new WrapFilter();
		self::$filters['trim']    = new TrimFilter();
		self::$filters['json']    = new JsonFilter();
		if (function_exists('fire')) {
			fire('string\initFilter');
		}
	}

	/**
	 * 获取一个字符处理器实例.
	 * @return \curlient\StringFilter
	 */
	public static function getInstance() {
		if (!self::$INSTANCE) {
			self::$INSTANCE = new StringFilter();
		}

		return self::$INSTANCE;
	}

	/**
	 * @param string                 $id
	 * @param \curlient\IFieldFilter $filter
	 */
	public static function register($id, IFieldFilter $filter) {
		if ($id) {
			self::$filters[ $id ] = $filter;
		}
	}

	/**
	 * 取中间的字符串.
	 *
	 * @param string $content
	 * @param array  ...$args
	 *
	 * @return string
	 */
	public static function sub($content, ...$args) {
		array_unshift($args, 'sub');

		return self::getInstance()->filter($content, [$args]);
	}

	/**
	 * 通过正则(简易正则，使用'参数'代表.+?))匹配后再根据规则合并字符（类似于preg_match_callback）.
	 *
	 * @param string $content
	 * @param array  ...$args
	 *
	 * @return string
	 */
	public static function concat($content, ...$args) {
		array_unshift($args, 'concat');

		return self::getInstance()->filter($content, [$args]);
	}

	/**
	 * @param string $content
	 * @param array  $fieldConf
	 *
	 * @return string
	 */
	public function filter($content, array $fieldConf) {
		if ($content) {
			foreach ($fieldConf as $conf) {
				if ($conf) {
					$filterId = array_shift($conf);
					if ($filterId && isset(self::$filters[ $filterId ])) {
						/**@var \curlient\IFieldFilter $filter */
						$filter  = self::$filters[ $filterId ];
						$content = $filter->filter($content, $conf);
					}
				}
			}
		}

		return $content;
	}
}
