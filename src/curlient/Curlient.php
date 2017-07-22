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
 * Class Curlient
 * @package curlient
 * @property string   $host        上一次访问的页面的主机
 * @property string   $proxy       使用的代理
 * @property string   $referer     引用
 * @property string   $url         当前正在引用的url
 * @property string   $encoding    上一次的页面的编码
 * @property bool     $ready       是否可以获取内容
 * @property string   $content     获取到的内容
 * @property string   $agent       使用的代理
 * @property resource $curl        使用中的curl资源
 * @property array    $options     curl选项.
 * @property array    $cookie      用户自定义cookie
 * @property array    $header      用户定义header
 */
class Curlient {
	protected static $cookies = [];
	//随机Agent
	protected static $agents = [
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.109 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:54.0) Gecko/20100101 Firefox/54.0',
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:30.0) Gecko/20100101 Firefox/54.0',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Gecko/20100101 Firefox/54.0',
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.109 Safari/537.36'
	];
	//默认请求头
	protected static $headers = [
		'Accept'          => '*/*',
		'Accept-Language' => 'zh-CN,en;q=0.8,zh;q=0.6',
		'Cache-Control'   => 'no-cache',
		'Accept-Encoding' => 'gzip, deflate',
		'DNT'             => '1',
		'Pragma'          => 'no-cache',
		'Connection'      => 'keep-alive'
	];

	public $error     = '';
	public $errorCode = 0;

	protected $curl         = null;
	protected $timeout      = 30;
	protected $proxy        = '';
	protected $referer      = '';
	protected $url          = '';
	protected $agent        = '';
	protected $host         = '';
	protected $encoding     = '';
	protected $content      = '';
	protected $options      = [];
	protected $cookie       = [];
	protected $header       = [];
	protected $isSubRequest = false;//批量请求中的一个子请求.
	private   $ready        = false;//是否可以调用.

	public function __construct($timeout = 30, $proxy = '') {
		$this->timeout = intval($timeout) ? intval($timeout) : 30;
		set_time_limit($this->timeout + 60);
		$this->proxy = $proxy;
		$this->agent = self::$agents[ array_rand(self::$agents) ];
		$this->initCurl();
	}

	// 关闭curl资源.
	public function __destruct() {
		if ($this->curl) {
			@curl_close($this->curl);
		}
	}

	/**
	 * 使用当前配置生成一个新的Client.
	 *
	 * @return \curlient\Curlient
	 */
	public function spawn() {
		$client          = new Curlient($this->timeout, $this->proxy);
		$client->agent   = $this->agent;
		$client->referer = $this->referer;
		if ($this->options) {
			$client->setup($this->options);
		}
		$client->cookie = $this->cookie;
		$client->header = $this->header;

		return $client;
	}

	/**
	 * 设置curl.
	 *
	 * @param array $options
	 *
	 * @return \curlient\Curlient
	 */
	public function setup($options = []) {
		if ($options) {
			if (isset($options[ CURLOPT_COOKIE ])) {
				$this->cookie = array_merge($this->cookie, $options[ CURLOPT_COOKIE ]);
				unset($options[ CURLOPT_COOKIE ]);
			}
			if (isset($options[ CURLOPT_HTTPHEADER ])) {
				$this->header = array_merge($this->header, $options[ CURLOPT_HTTPHEADER ]);
				unset($options[ CURLOPT_HTTPHEADER ]);
			}
			$this->options = array_merge($this->options, $options);
			curl_setopt_array($this->curl, $options);
		}

		return $this;
	}

	/**
	 * 设置cookie.
	 *
	 * @param array $cookies
	 *
	 * @return \curlient\Curlient
	 */
	public function cookie($cookies) {
		if ($cookies) {
			$this->cookie = array_merge($this->cookie, $cookies);
		}

		return $this;
	}

	/**
	 * 设置header.
	 *
	 * @param $headers
	 *
	 * @return \curlient\Curlient
	 */
	public function header($headers) {
		if ($headers) {
			$this->header = array_merge($this->header, $headers);
		}

		return $this;
	}

	public function referer($referer) {
		$this->referer = $referer;
	}

	/**
	 * 指定IP出去.
	 *
	 * @param string $ip IP地址.
	 *
	 * @return \curlient\Curlient
	 */
	public function useIP($ip) {
		if ($ip) {
			$this->setup([CURLOPT_INTERFACE => $ip]);
		}

		return $this;
	}

	/**
	 * 设置代理.
	 *
	 * @param null|string $proxy 为null时禁用代理.
	 *
	 * @return \curlient\Curlient
	 */
	public function useProxy($proxy = null) {
		if ($proxy) {
			$proxy = [CURLOPT_PROXY => $proxy];
		} else {
			$proxy = [CURLOPT_PROXY => null];
		}

		return $this->setup($proxy);
	}

	/**
	 * 指定网卡名.
	 *
	 * @param string $name 网卡名.
	 *
	 * @return \curlient\Curlient
	 */
	public function useAdapter($name) {
		if ($name) {
			$this->setup([CURLOPT_INTERFACE => $name]);
		}

		return $this;
	}

	/**
	 * 取网页内容.
	 *
	 * @param string $encoding
	 *
	 * @return string
	 */
	public function text($encoding = null) {
		$rst = $this->__toString();
		if ($rst) {
			if (!$encoding) {
				$encoding = $this->encoding;
			}
			if ($encoding && $encoding != 'UTF-8') {
				$rst = @mb_convert_encoding($rst, 'UTF-8', $encoding);
			}

			return $rst;
		}

		return '';
	}

	/**
	 * 取网页返回关联数组.
	 *
	 * @param null|string $encoding
	 *
	 * @return array|null
	 */
	public function json($encoding = null) {
		$rst = $this->text($encoding);
		if ($rst) {
			return @json_decode($rst, true);
		}

		return null;
	}

	/**
	 * 取原生内容.
	 *
	 * @return string
	 */
	public function __toString() {
		if ($this->ready) {
			$this->ready = false;
			if ($this->header) {
				$headers = $this->header;
				array_walk($headers, function (&$v, $k) {
					if (!is_numeric($k)) {
						$v = "$k: $v";
					}
				});
				curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
			}
			$rst = false;
			if (!$this->isSubRequest) {
				$rst = curl_exec($this->curl);
			}
			if ($rst === false) {
				$this->error     = curl_error($this->curl);
				$this->errorCode = curl_errno($this->curl);
				$rst             = '';
			} else {
				$code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
				if ($code != '200') {
					$this->error     = '[' . $code . ']';
					$this->errorCode = $code;
					$rst             = '';
				}
			}
			$this->content = $rst;
		}

		return $this->content;
	}

	/**
	 * 取cookies.
	 * @return array
	 */
	public function getCookies() {
		if ($this->host) {
			$cookie = isset(self::$cookies[ $this->host ]) ? self::$cookies[ $this->host ] : [];
		}

		return isset($cookie) ? array_merge($this->cookie, $cookie) : $this->cookie;
	}

	/**
	 * 魔术方法取值
	 *
	 * @param string $name
	 *
	 * @return null|mixed
	 */
	public function __get($name) {
		if (isset($this->{$name})) {
			return $this->{$name};
		}

		return null;
	}

	/**
	 * 请求.
	 *
	 * @param string $url        URL
	 * @param string $method     请求方法.
	 * @param array  $postFields POST字段.
	 *
	 * @return \curlient\Curlient
	 */
	public function request($url, $method = 'get', $postFields = []) {
		if (!$this->curl || !$url) {
			return $this;
		}
		$this->content = '';
		unset($this->header['X-Requested-With']);
		$this->host = parse_url($url, PHP_URL_HOST);
		$hosts      = explode('.', $this->host);
		if (count($hosts) > 2) {
			$this->host = implode('.', array_slice($hosts, 1));
		}
		// 自定义cookie
		if ($this->cookie) {
			$cookie = $this->cookie;
		}
		// 从上一次请求读取的cookie
		if ($this->host && isset(self::$cookies[ $this->host ])) {
			$cookie = isset($cookie) ? array_merge(self::$cookies[ $this->host ], $cookie) : self::$cookies[ $this->host ];
		}
		// 设置cookie
		if (isset($cookie)) {
			$cookie = $this->ary_kv_concat($cookie);
			curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);
		}
		//自动设置referer
		if ($this->referer) {
			curl_setopt($this->curl, CURLOPT_REFERER, $this->referer);
		}
		//清空PostData
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, '');
		//设置请求方法
		if ($method == 'get') {
			curl_setopt($this->curl, CURLOPT_HTTPGET, 1);
			curl_setopt($this->curl, CURLOPT_PORT, 0);
		} else {
			curl_setopt($this->curl, CURLOPT_HTTPGET, 0);
			curl_setopt($this->curl, CURLOPT_PORT, 1);
			//提交字段
			if ($postFields) {
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);
			}
		}
		curl_setopt($this->curl, CURLOPT_URL, $this->buildURL($url));
		$this->referer = $url;
		$this->ready   = true;
		$this->url     = $url;

		return $this;
	}

	/**
	 * 批量请求.
	 *
	 * @param array               $urls      请求URL数组.
	 * @param null|\Closure|array $onSuccess 请求成功回调，参数为client实例.
	 *                                       也可以是数组，对应相应的回调：
	 *                                       onSuccess => 请求成功回调，参数为client实例.
	 *                                       onFail    => 请求失败回调，参数为client实例.
	 *                                       onStart   => 请求开始回调，参数为client实例.
	 *                                       onFinish  => 所有请求完成回调，三个参数：请求成功client数组，请求失败client数组，未发起请求client数组.
	 *
	 * @return array 请求结果（关联数组）.
	 *               <p>
	 *                  0=>[],//请求成功的Client
	 *                  1=>[],//请求失败的Client
	 *                  2=>[] //未发起请求的Client,因onStart返回了非真值.
	 *               </p>
	 *
	 */
	public function requests($urls, $onSuccess = null) {
		$result   = [0 => [], 1 => [], 2 => []];
		$onFinish = $onStart = $onFail = null;
		if (is_array($onSuccess)) {
			extract($onSuccess);
		}
		$mh = @curl_multi_init();
		if (!$mh) {//初始化失败
			if (is_callable($onFinish)) {
				call_user_func_array($onFinish, $result);
			}

			return $result;
		}
		$requests    = [];
		$onStartCb   = is_callable($onStart);
		$onSuccessCb = is_callable($onSuccess);
		$onFailCb    = is_callable($onFail);
		foreach ($urls as $i => $url) {
			$client               = $this->spawn();
			$client->isSubRequest = true;
			$client->request($url);
			$contiue = true;
			if ($onStartCb) {
				$contiue = call_user_func_array($onStart, [$client]);
			}
			if ($contiue) {
				$requests[ $i ] = $client;
				curl_multi_add_handle($mh, $client->curl);
			} else {
				$result [2] [ $i ] = $client;
			}
		}
		if ($requests) {
			$active = 0;
			do {
				curl_multi_exec($mh, $active);
				if ($active > 0) {
					usleep(50);
				}
			} while ($active > 0);
			/**@var \curlient\Curlient $h */
			foreach ($requests as $i => $h) {
				$rtn             = curl_multi_getcontent($h->curl);
				$h->ready        = false;
				$h->isSubRequest = false;
				if ($rtn === false) {
					$h->content   = '';
					$h->error     = curl_error($h->curl);
					$h->errorCode = curl_errno($h->curl);
					if ($onFailCb) {
						call_user_func_array($onFail, [$h]);
					}
				} else {
					$h->content = $rtn;
					$code       = intval(curl_getinfo($h->curl, CURLINFO_HTTP_CODE));
					if ($code != 200) {
						$h->error     = '[' . $code . ']';
						$h->errorCode = $code;
						if ($onFailCb) {
							call_user_func_array($onFail, [$h]);
						}
						$result [1] [ $i ] = $h;
					} else {
						if ($onSuccessCb) {
							call_user_func_array($onSuccess, [$h]);
						}
						$result [0] [ $i ] = $h;
					}
				}

				curl_multi_remove_handle($mh, $h->curl);
			}
		}

		@curl_multi_close($mh);

		if (is_callable($onFinish)) {
			call_user_func_array($onFinish, $result);
		}

		return $result;
	}

	/**
	 * ajax方式请求.
	 *
	 * @return \curlient\Curlient
	 */
	public function ajax() {
		$this->header['X-Requested-With'] = 'XMLHttpRequest';

		return $this;
	}

	/**
	 * 根据$base生成合法的url.
	 *
	 * @param string $base
	 * @param array  $urls
	 */
	public static function goodURL($base, &$urls) {
		if (!isset($urls[0]['url']) || !preg_match('#^(ht|f)tps?://.+#', $base)) {
			return;
		}

		$info = parse_url($base);
		$path = '/';
		if (isset($info['path'])) {
			$path = rtrim($info['path'], '/');
			if ($path) {
				$path = substr($path, 0, 1 + strrpos($path, '/'));
			} else {
				$path = '/';
			}
		}
		// auth
		$auth = '';
		if (isset($info['user'])) {
			$auth = $info['user'];
			if (isset($info['password'])) {
				$auth .= ':' . $$info['password'] . '@';
			}
		}
		// base url
		$base = $info['scheme'] . '://' . $auth . $info['host'] . (isset($info['port']) ? ':' . $info['port'] : '');
		$path = $base . $path;
		array_walk($urls, function (&$url) use ($info, $base, $path) {
			$url['url'] = @trim($url['url']);
			if (preg_match('#^(?P<lt>(ht|f)tps?://|//|/)?.*#i', $url['url'], $ms)) {
				if (isset($ms['lt'])) {
					switch ($ms['lt']) {
						case 'http://':
						case 'https://':
						case 'ftp://':
						case 'ftps://':
							break;
						case '//':
							$url['url'] = $info['scheme'] . ':' . $url['url'];
							break;
						case  '/':
							$url['url'] = $base . $url['url'];
							break;
						default:
							$url['url'] = $path . $url['url'];
					}
				} else {
					$url['url'] = $path . $url['url'];
				}
			}
		});
	}

	/**
	 *
	 * @param $gcfg
	 *
	 * @return \curlient\Curlient
	 */
	public static function build($gcfg = []) {
		extract($gcfg);
		$client = new Curlient(isset($timeout) ? $timeout : 30, isset($proxy) ? $proxy : '');
		if (isset($ip)) {
			$client->useIP($ip);
		}
		if (isset($cookie) && $cookie) {
			$client->cookie($cookie);
		}
		if (isset($header) && $header) {
			$client->header($header);
		}
		if (isset($refer)) {
			$client->referer($refer);
		}

		return $client;
	}

	/**
	 * 解析响应头.
	 *
	 * @param resource $curl
	 * @param string   $header 响应头数据.
	 *
	 * @return int
	 */
	protected function parseHeaders($curl, $header) {
		$len    = strlen($header);
		$header = explode(':', $header, 2);

		if (count($header) < 2) { // ignore invalid headers
			return $len;
		}
		$name = trim($header[0]);
		if ($name == 'Set-Cookie') {
			$this->parseCookie(trim($header[1]));
		} else if ($name == 'Content-Type' && preg_match('#(.+;\s+)?charset=(.+)$#', $header[1], $charset)) {
			$this->encoding = trim(strtoupper(isset($charset[2]) ? $charset[2] : $charset[1]));
		}

		return $len;
	}

	/**
	 * 使用cookie值做为参数构建url.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function buildURL($url) {
		$cookies = $this->getCookies();

		return preg_replace_callback('#\{(\$[^\}]+)\}#', function ($m) use ($cookies) {
			if (isset($cookies[ $m[1] ])) {
				return $cookies[ $m[1] ];
			} else {
				return '';
			}
		}, $url);
	}

	/**
	 * 初始化curl.
	 * @throws \Exception when curl_init returns false.
	 */
	private function initCurl() {
		$this->curl = @curl_init();
		if (!$this->curl) {
			throw_exception('Cannot initialize curl.');
		}

		curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_TCP_NODELAY, true);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->curl, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($this->curl, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($this->curl, CURLOPT_HEADER, false);
		curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);

		//		if (version_compare(phpversion(), '7.0.6', '>')) {
		//			curl_setopt($this->curl, CURLOPT_TCP_FASTOPEN, true);
		//		}

		if ($this->proxy) {
			curl_setopt($this->curl, CURLOPT_PROXY, $this->proxy);
		}

		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 15);

		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);

		curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, [$this, 'parseHeaders']);
	}

	/**
	 * 解析cookie.
	 *
	 * @param string $cookie
	 */
	private function parseCookie($cookie) {
		$cookies = explode(',', $cookie);
		if ($cookies) {
			foreach ($cookies as $cookie) {
				$cookie = trim($cookie);
				if (preg_match('#^([^;]+?)=([^;]+)(;.*)?#', $cookie, $cok)) {
					self::$cookies[ $this->host ][ $cok[1] ] = $cok[2];
				}
			}
		}
	}

	/**
	 * 合并cookie string.
	 *
	 * @param array $ary
	 *
	 * @return string
	 */
	private function ary_kv_concat(array $ary) {
		if (empty ($ary)) {
			return '';
		}
		$tmp_ary = [];

		foreach ($ary as $name => $val) {
			$name       = trim($name);
			$tmp_ary [] = $name . '=' . $val;
		}

		return implode('; ', $tmp_ary);
	}
}