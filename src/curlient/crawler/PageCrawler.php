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
use curlient\filter\QueryFilter;
use curlient\IFieldFilter;
use curlient\StringFilter;
use Symfony\Component\DomCrawler\Crawler;

class PageCrawler {
	private $ip;
	public  $error = '';
	public  $pages = [];

	public function __construct($ip = null) {
		$this->ip = $ip;
	}

	/**
	 * 验证采集规则.
	 *
	 * @param array $conf
	 *
	 * @return bool|string 验证成功返回true，否则返回错误信息.
	 */
	public static function validate($conf) {
		if (!isset($conf['url'])) {
			return _tr('url is missed@crawler');
		}
		if (!isset($conf['page'])) {
			return _tr('page is missed@crawler');
		}
		if (!isset($conf['page']['fields'])) {
			return _tr('page fields is missed@crawler');
		}

		return true;
	}

	//抓取这一页
	public function crawl($conf) {
		$gcfg   = $conf['conf'];//全局配置，包括cookie和header
		$url    = $conf['url'];//要抓取的页面URL
		$cfg    = $conf['page'];//页面规则
		$refer  = isset($conf['refer']) ? $conf['refer'] : '';//列表页URL
		$fields = isset($conf['fields']) ? $conf['fields'] : [];//从列表页来的字段
		// 开始抓取
		if ($url && $cfg['fields']) {
			$gcfg['ip']    = $this->ip;
			$gcfg['refer'] = $refer;
			$client        = Curlient::build($gcfg);
			$content       = $this->getContent($url, $client, $cfg, $gcfg);
			if ($content) {//解析内容
				if (isset($cfg['json'])) {
					$content = @json_decode($content);
				} else if (isset($cfg['dom'])) {
					$content = new Crawler($content);
				}
				$this->parseFields($content, $cfg, $fields);
				$this->getMoreContent($client, $content, $cfg, $fields, $url, $gcfg);
			} else {
				$this->error = '[' . $client->errorCode . ']' . $client->error;

				return false;
			}
		} else {
			$this->error = 'no fields configured';

			return false;
		}
		$fields['抓取时间'] = time();
		$rst            = ['url' => $url, 'fields' => $fields];

		return $rst;
	}

	/**
	 * 解析字段.
	 *
	 * @param string|array $data
	 * @param array        $cfg
	 * @param array        $fields
	 */
	private function parseFields($data, $cfg, &$fields) {
		$grabber = StringFilter::getInstance();
		foreach ($cfg['fields'] as $name => $conf) {
			$fields[ $name ] = $grabber->filter($data, $conf);
		}
	}

	// 抓取更多页
	private function getMoreContent(Curlient $client, $content, $cfg, &$fields, $url, $gcfg) {
		$concat = isset($cfg['concat']) ? $cfg['concat'] : '';
		if (!$concat || !isset($fields[ $concat ])) {//未指定连接字段或连接字段不存在.
			return;
		}

		$concatStr = isset($cfg['concatStr']) ? $cfg['concatStr'] : '[:page:]';
		$grabber   = StringFilter::getInstance();
		if (isset($gcfg['interval'])) {
			$interval = intval($gcfg['interval']);
		} else {
			$interval = 0;
		}
		$links = [$url];
		while ($link = $this->getNextPageURL($content, $cfg, $url)) {
			if (in_array($link, $links)) {
				break;//一旦发现有重复，就退出不采集了。
			}
			$content = $this->getContent($link, $client, $cfg, $gcfg);
			if (!$content) {
				break;//采不到值了退出不采了
			}
			if (isset($cfg['json'])) {
				$content = @json_decode($content);
			} else if (isset($cfg['dom'])) {
				$content = new Crawler($content);
			}
			$val = $grabber->filter($content, $cfg['fields'][ $concat ]);
			if (!$val) {//采不到值了退出不采了
				break;
			}
			$fields[ $concat ] .= $concatStr . $val;
			$links []          = $link;
			if ($interval > 0) {
				sleep($interval);
			}
		}
		$this->pages = $links;
	}

	/**
	 * 取下一页链接
	 *
	 * @param array|string $content
	 * @param array        $cfg
	 * @param string       $url
	 *
	 * @return array|mixed|string
	 */
	private function getNextPageURL($content, $cfg, $url) {
		static $pages = false, $i = 0;
		if ($pages !== false) {//全部列出模式时使用
			return isset($pages[ $i ]) ? $pages[ $i++ ] : '';
		}

		$link = '';
		if (isset($cfg['pager']) && $cfg['pager']) {
			$pager = $cfg['pager'];
			$type  = isset($pager['type']) ? $pager['type'] : 'list';
			if (is_string($content) && isset($pager['wrapper'])) {
				list($start, $end) = $pager['wrapper'];
				$content = StringFilter::sub($content, $start, $end);
			}
			if (isset($cfg['json'])) {
				$link = $this->getPages(new JsonFilter(), $pager, $content, $pages, $type, $i, $url);
			} else if (isset($cfg['dom'])) {
				$link = $this->getPages(new QueryFilter(), $pager, $content, $pages, $type, $i, $url);
			} else {//从正文取分页
				$filterCfg = $pager['pages'];
				$filter    = new ConcatFilter();
				if ($type == 'list') {
					$filterCfg[] = true;//匹配全部
					$links       = $filter->filter($content, $filterCfg);
					if ($links) {
						$pages = [];
						foreach ($links as $link) {
							$tlink[0]['url'] = $link[0];
							Curlient::goodURL($url, $tlink);
							$tu = $tlink[0]['url'];
							if ($tu != $url && !in_array($tu, $pages)) {
								$pages[] = $tu;
							}
						}
						$link = $pages[ $i++ ];
					}
				} else {//下一页模式
					$link = $filter->filter($content, $filterCfg);
					if ($link) {//取到分页
						$tlink[0]['url'] = $link;
						Curlient::goodURL($url, $tlink);
						if ($tlink[0]['url'] != $url) {
							$link = $tlink[0]['url'];
						} else {
							$link = '';
						}
					}
				}
			}
		}

		return $link;
	}

	/**
	 * 抓取内容
	 *
	 * @param string             $url
	 * @param \curlient\Curlient $client
	 * @param array              $cfg
	 * @param array              $gcfg
	 *
	 * @return array|null|string
	 */
	private function getContent($url, Curlient $client, $cfg, $gcfg) {

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

		if (isset($cfg['json']) && $cfg['json']) {
			$content = $client->json(isset($encoding) ? $encoding : null);
		} else {
			$content = $client->text(isset($encoding) ? $encoding : null);
		}

		return $content;
	}

	private function getPages(IFieldFilter $filter, $pager, $content, &$pages, $type, &$i, $url) {
		$filterCfg = $pager['pages'];
		$limit     = isset($pager['limit']) ? intval($pager['limit']) : 100;
		if ($type == 'list') {//全部列出模式
			$pages = [];
			$j     = 0;
			$li    = count($filterCfg);
			do {
				$filterCfg[ $li ] = ['{$i}', $j];
				$link             = $filter->filter($content, $filterCfg);
				if (!$link) {
					break;
				}
				$tlink[0]['url'] = $link;
				Curlient::goodURL($url, $tlink);
				$tu = $tlink[0]['url'];
				if ($tu != $url && !in_array($tu, $pages)) {
					$pages[] = $tu;
				}
				$j++;
			} while ($j < $limit);

			return $pages ? $pages[ $i++ ] : null;
		} else {//下一页模式
			$link = $filter->filter($content, $filterCfg);
			if ($link) {//取到分页
				$tlink[0]['url'] = $link;
				Curlient::goodURL($url, $tlink);
				if ($tlink[0]['url'] != $url) {
					$link = $tlink[0]['url'];
				} else {
					$link = '';
				}
			}

			return $link;
		}
	}
}