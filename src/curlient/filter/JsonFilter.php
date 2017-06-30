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

class JsonFilter implements IFieldFilter {
	public function name() {
		return _t('JSON Filter@crawler');
	}

	public function filter($content, $args) {
		if (!is_array($content)) {
			$content = @json_decode($content, true);
		}
		if (is_array($content) && $content && $args && $args[0]) {
			if (isset($args[1])) {
				$args[0] = str_replace($args[1][0], $args[1][1], $args[0]);
			}
			$paths = explode('.', str_replace("'", '"', $args[0]));
			$len   = count($paths);
			for ($i = 0; $i < $len; $i++) {
				if (!is_array($content)) {//已经不是数组，拉下来的key将取到空值.
					return '';
				}
				$key = '';
				do {
					$tmp = $paths[ $i ];
					if ($tmp{0} == '"') {//key的第一部分
						$key = $tmp;
						$i++;
					} else if (substr($tmp, -1) == '"') {//key的最后部
						$key = "$key.$tmp";
						$key = trim($key, '"');
						break;
					} else if ($key) {//key的中间部分
						$key = "$key.$tmp";
						$i++;
					} else {//完整的key
						$key = $tmp;
						break;
					}
				} while ($i < $len);//取完整的key
				if (!$key) {//非法的key
					$content = '';
					break;
				}

				// key的规则为key,key[], key[1], key[-1], []
				if (preg_match('`^(?P<key>[^\[]*)(?P<ary>\[(?P<idx>0|-?[1-9]\d*)?\])?$`', $key, $ms)) {
					if (isset($ms['key']) && $ms['key']) {//设定了key
						$key     = $ms['key'];
						$content = isset($content[ $key ]) ? $content[ $key ] : '';
					}
					if (is_array($content) && isset($ms['ary'])) {//取数组值
						if (isset($ms['idx'])) {
							$idx = $ms['idx'];
							if ($idx < 0) {//从后边取，比如-1为最后一个元素
								$idx += count($content);
							}
							if (isset($content[ $idx ])) {
								$content = $content[ $idx ];
							}
						} elseif (!isset($ms['idx']) && isset($content[0])) {
							$content = $content[0];
						}
					} else if (isset($ms['ary'])) {//要取数组里的值，但是$content不是数组了.
						return '';
					}
				} else {//非法键名
					return '';
				}
			}

			return is_array($content) ? json_encode($content) : $content;
		}

		return '';
	}
}