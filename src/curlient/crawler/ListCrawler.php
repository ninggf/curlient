<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace curlient\crawler;

use curlient\Curlient;
use curlient\filter\ConcatFilter;
use curlient\filter\JsonFilter;
use curlient\StringFilter;

class ListCrawler {
	private $ip;

	/**
	 * ListPageCrawler constructor.
	 *
	 * @param string $ip
	 */
	public function __construct($ip = '') {
		$this->ip = $ip;
	}

	/**
	 * 抓取一个列表页.
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	public function crawl($config) {
		$pages = [];
		$gcfg  = $config['conf'];
		$cfg   = $config['list'];
		$url   = $config['url'];

		if (isset($cfg['pages1'])) {//自定义页面
			foreach ($cfg['pages1'] as $page) {
				$pages[] = ['url' => $page, 'fields' => ['URL' => $pages], 'conf' => $gcfg, 'refer' => $url];
			}
		}

		if ($url && $cfg['pages']) {//可以抓取
			$gcfg['ip'] = $this->ip;
			$client     = Curlient::build($gcfg);

			if (isset($cfg['method']) && $cfg['method'] == 'post') {
				$client->request($url, 'post', isset($cfg['data']) ? $cfg['data'] : null);
			} else {
				$client->request($url);
			}

			if (isset($cfg['ajax'])) {
				$client->ajax();
			}

			if (isset($cfg['encoding'])) {
				$encoding = $cfg['encoding'];
			} else if (isset($gcfg['encoding'])) {
				$encoding = $gcfg['encoding'];
			}

			$isJson = false;
			if (isset($cfg['json']) && $cfg['json']) {
				$content = $client->json(isset($encoding) ? $encoding : null);
				$isJson  = true;
			} else {
				$content = $client->text(isset($encoding) ? $encoding : null);
			}
			$cookies = $client->getCookies();
			if ($cookies) {
				$gcfg['cookie'] = isset($gcfg['cookie']) && is_array($gcfg['cookie']) ? @array_merge($gcfg['cookie'], $cookies) : $cookies;
			}
			if ($content) {//解析内容
				if ($isJson) {
					$this->parseJsonData($content, $cfg, $pages, $gcfg, $url);
				} else {
					$this->parseTextData($content, $cfg, $pages, $gcfg, $url);
				}
			}
		}
		$pages = $this->unique($pages);
		if ($cfg['includes']) {
			$pages = $this->includes($pages, $cfg['includes']);
		}
		if ($cfg['excludes']) {
			$pages = $this->excludes($pages, $cfg['excludes']);
		}
		if ($pages) {
			Curlient::goodURL($url, $pages);
		}

		return $pages;
	}

	private function parseTextData($data, $cfg, &$pages, $gcfg, $url) {
		static $grabber = false;
		if ($grabber === false) {
			$grabber = StringFilter::getInstance();
		}
		if (isset($cfg['wrapper'])) {
			list($start, $end) = $cfg['wrapper'];
			$data = StringFilter::sub($data, $start, $end);
		}
		if ($data) {
			$filterCfg   = $cfg['pages'];
			$filterCfg[] = true;//匹配全部
			$filter      = new ConcatFilter();
			$links       = $filter->filter($data, $filterCfg);
			if ($links) {
				if ($cfg['fields']) {
					$preLink = '';//前一个匹配链接全字符，用于定位后一个链接.
					foreach ($links as $link) {
						$page = ['url' => $link[0], 'fields' => ['URL' => $link[0]], 'conf' => $gcfg, 'refer' => $url];
						foreach ($cfg['fields'] as $name => $fieldCfg) {
							$name = explode('.', $name);
							$pos  = isset($name[1]) ? $name[1] : 'after';
							$name = $name[0];
							if ($pos == 'after') {
								$fdata = StringFilter::sub($data, $link[1]);
							} else {
								$fdata = StringFilter::sub($data, $preLink, $link[1]);
							}
							$page['fields'][ $name ] = $grabber->filter($fdata, $fieldCfg);
						}
						$preLink = $link[1];
						$pages[] = $page;
					}
				} else {
					foreach ($links as $link) {//每一个页面
						$page    = ['url' => $link[0], 'fields' => ['URL' => $link[0]], 'conf' => $gcfg, 'refer' => $url];
						$pages[] = $page;
					}
				}
			}
		}
	}

	private function parseJsonData($data, $cfg, &$pages, $gcfg, $url) {
		$limit     = isset($gcfg['limit']) ? intval($gcfg['limit']) : 100;
		$limit     = $limit <= 0 ? 100 : $limit;
		$links     = [];
		$i         = 0;
		$filterCfg = $cfg['pages'];
		$grabber   = StringFilter::getInstance();
		$filter    = new JsonFilter();
		do {
			$filterCfg[] = ['{$i}', $i];
			$link        = $filter->filter($data, $filterCfg);
			if (!$link) {
				break;
			}
			$links[ $i ] = $link;
			$i++;
		} while ($i < $limit);
		if ($links) {
			if ($cfg['pages']) {
				foreach ($links as $i => $link) {
					$page = ['url' => $link, 'fields' => ['URL' => $link], 'conf' => $gcfg, 'refer' => $url];
					foreach ($cfg['fields'] as $name => $fieldCfg) {
						if ($fieldCfg[0][0] != 'json') {
							$page['fields'][ $name ] = '';
						} else {
							$fieldCfg[0][]           = ['{$i}', $i];
							$page['fields'][ $name ] = $grabber->filter($data, $fieldCfg);
						}
					}
					$pages[] = $page;
				}
			} else {
				foreach ($links as $link) {
					$page    = ['url' => $link, 'fields' => ['URL' => $link], 'conf' => $gcfg, 'refer' => $url];
					$pages[] = $page;
				}
			}
		}
	}

	//去重
	private function unique($pages) {
		$links = [];
		$pages = array_filter($pages, function ($page) use (&$links) {
			if (isset($links[ $page['url'] ])) {
				return false;
			}
			$links[ $page['url'] ] = 1;

			return true;
		});
		unset($links);

		return $pages;
	}

	//包含
	private function includes($pages, $includes) {
		return array_filter($pages, function ($page) use ($includes) {
			foreach ($includes as $in) {
				if (strpos($page['url'], $in) !== false) {//只要有，就要它
					return true;
				}
			}

			return false;//默认不要
		});
	}

	//不包
	private function excludes($pages, $excludes) {

		return array_filter($pages, function ($page) use ($excludes) {

			foreach ($excludes as $ex) {
				if (strpos($page['url'], $ex) !== false) {//只要有，就不要它
					return false;
				}
			}

			return true;//默认要
		});
	}

	/**
	 * 验证采集规则.
	 *
	 * @param array $conf
	 *
	 * @return bool|string 验证成功返回true，否则返回错误信息.
	 */
	public static function validate($conf) {
		return false;
	}

	/**
	 * 解析列表页规则.
	 *
	 * @param $config
	 *
	 * @return array
	 */
	public static function parseListPageURL($config) {
		$lists = [];
		// 一个元素即为一个url.
		if (isset($config['list'])) {
			$lists += $config['list'];
		}

		if (isset($config['expression'])) {
			$expression = $config['expression'];
			//正则
			if (preg_match('#\{\{\s*0\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(0|[1-9]\d*)\s*\}\}#', $expression, $tags)) {
				list($all, $start, $end, $step, $pad) = $tags;
				$pad  = intval($pad);
				$nums = range(intval($start), intval($end), intval($step));
				foreach ($nums as $num) {
					if ($pad > 1) {
						$num = str_pad($num, $pad, '0', STR_PAD_LEFT);
					}
					$lists[] = str_replace($all, $num, $expression);
				}
			}
		}

		return $lists;
	}
}