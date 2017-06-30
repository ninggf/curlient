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

class ConcatFilter implements IFieldFilter {
	public function name() {
		return _t('Concat Filter@crawler');
	}

	public function filter($content, $args) {
		list($regex, $concat, $ary) = $args;
		if ($regex) {
			//正则解析：将简易的正则变为正则
			//(*)==>(.*?)
			//(参数)==>(?P<参数n>.*?)
			$args  = [];
			$i     = 0;
			$regex = preg_replace_callback('/\((\*|[a-z0-9_]+)\)/', function ($m) use (&$args, &$i) {
				$i++;
				$args[ $i ] = "$m[1]$i";
				if ($m[1] == '*') {
					return '(.*?)';
				} else {
					return "(?P<$m[1]$i>.*?)";
				}
			}, $regex);
			if ($i > 0) {
				if (!$concat) {
					$concat = "($args[1])";
				}
				$regex = str_replace('`', '\\`', $regex);
				if ($ary) {
					$concats = [];
					if (preg_match_all("`$regex`ums", $content, $mss, PREG_SET_ORDER)) {

						foreach ($mss as $ms) {
							$concat1 = $concat;
							foreach ($args as $arg) {
								$concat1 = str_replace("($arg)", $ms[ ltrim($arg, '*') ], $concat1);
							}
							$concats[] = [$concat1, $ms[0]];
						}
					}

					return $concats;
				} else {
					if (preg_match("`$regex`ums", $content, $ms)) {
						foreach ($args as $arg) {
							$concat = str_replace("($arg)", $ms[ ltrim($arg, '*') ], $concat);
						}
					} else {
						return '';
					}
				}

				return $concat;
			}
		}

		return $ary ? [] : '';
	}

}