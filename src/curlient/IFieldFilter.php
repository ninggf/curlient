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
/**
 * 字段过滤器接口.
 * @package crawler\classes
 */
interface IFieldFilter {
	/**
	 * what's your name?
	 *
	 * @return string
	 */
	public function name();

	/**
	 * @param string $content 要过滤的字符.
	 * @param array  $args    参数.
	 *
	 * @return string 过滤后的字符.
	 */
	public function filter($content, $args);
}