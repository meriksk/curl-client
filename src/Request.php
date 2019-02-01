<?php

namespace CurlClient;

use CurlClient\Client;

/**
 * Request class file.
 *
 * @property string $url
 * @property string $method
 * @property array $params
 * @property array $headers
 * @property array $curlOptions
 * @property string $userAgent
 * @property string $expectedType
 * @property array $files
 */
class Request
{

	/**
	 * Request URL
	 * @var string
	 */
	private $_url;

	/**
	 * Request method (GET, POST, ...)
	 * @var string
	 */
	private $_method;

	/**
	 * Request params
	 * @var array
	 */
	private $_params;

	/**
	 * Request headers
	 * @var array
	 */
	private $_headers = [];

	/**
	 * cURL options
	 * @var array
	 */
	private $_curlOptions = [];

	/**
	 * User agent
	 * @var string
	 */
	private $_userAgent = 'LuceonCurlClient';

	/**
	 * Expected response type
	 * @var string
	 */
	private $_expectedType;

	private $_beforeExecute;

	/**
	 * Request default options
	 * @var array
	 */
	private $_defaultOptions = [
		'method' => Client::METHOD_GET,
		'curlOptions' => [
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLINFO_HEADER_OUT => 1,
			CURLOPT_AUTOREFERER => 1,
		],
	];


	/**
	 * Class constructor
	 */
	public function __construct(array $options = [])
	{
		$this->setOptions($options);
	}

	/**
	 * Returns the value of a component property
     * @param string $name the property name
     * @return mixed the property value or the value of a behavior's property
	 * @throws \Exception
	 */
	public function __get($name)
	{
		$getter = 'get'.$name;
		if (method_exists($this,$getter)) { return $this->$getter(); }
		throw new \Exception('Property "'. get_class($this) .'.'. $name .'" is not defined.');
	}

    /**
     * Sets the value of a component property.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws \Exception if the property is not defined
     * @see __get()
     */
	public function __set($name, $value)
	{
		$setter = 'set'.$name;
		if (method_exists($this, $setter)) { return $this->$setter($value); }
		throw new \Exception('Property "'. get_class($this) .'.'. $name .'" is not defined.');
	}

	/**
	 * Checks if a property is set, i.e. defined and not null.
     * @param string $name the property name or the event name
     * @return bool whether the named property is set
     * @see http://php.net/manual/en/function.isset.php
	 */
	public function __isset($name)
	{
		$getter = 'get'.$name;
		if (method_exists($this,$getter)) { return $this->$getter()!==NULL; }
		return false;
	}

	/**
	 * Sets a component property to be null.
     * @param string $name the property name
     * @throws \Exception if the property is read only.
     * @see http://php.net/manual/en/function.unset.php
	 */
	public function __unset($name)
	{
		$setter = 'set'.$name;
		if (method_exists($this,$setter)) { 
			$this->$setter(null); 
		} elseif (method_exists($this,'get'.$name)) {
			throw new \Exception('Property "'. get_class($this) .'.'. $name .'" is read only.');
		}
	}

	/**
	 * Set Request options
	 * @param array $options
	 * @return Request
	 */
	public function setOptions(array $options)
	{
		if (!empty($options)) {
			foreach ($options as $key => $value) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get request URL
	 * @return string
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	 * Set request URL
	 * @param string $url
	 * @param string $baseUrl
	 * @return Request
	 * @throws \Exception
	 */
	public function setUrl($url)
	{
		if (is_string($url)) {
			$this->_url = trim($url);
		} else {
			$this->_url = NULL;
		}

		return $this;
	}

	/**
	 * Get request method
	 * @return string
	 */
	public function getMethod()
	{
		return $this->_method;
	}

	/**
	 * Set request URL
	 * @param string $method
	 * @return Request
	 */
	public function setMethod($method)
	{
		if (!empty($method) && is_string($method)) {
			$this->_method = strtoupper(trim($method));
		} else {
			$this->_method = Client::METHOD_GET;
		}

		return $this;
	}

	/**
	 * Returns request params
	 * @return string|array
	 */
	public function getParams()
	{
		return $this->_params;
	}

	/**
	 * Set request params
	 * @param string|array $params
	 * @return Request
	 * @throws \Exception
	 */
	public function setParams($params)
	{
		// reset parameters
		if ($params === NULL || (is_array($params) && empty($params))) {
			$this->_params = NULL;
			return $this;
		}

		if (is_string($params)) {
			$this->setParam($params);

		} elseif (is_array($params)) {
			foreach ($params as $key => $value) {
				$this->setParam($key, $value);
			}
		}

		return $this;
	}

	/**
	 * Set request parameter
	 * @param string $key
	 * @param mixed $value
	 * @return Request
	 */
	public function setParam($key, $value = -999)
	{

		// array support
		if (is_array($key) && count($key)===2 && array_key_exists(0, $key) && array_key_exists(1, $key)) {
			$value = $key[1];
			$key = $key[0];
		}

		// invalid method call
		if (is_string($key) && empty($key)) {
			return $this;
		}

		// prepare structure
		if ($this->_params === NULL) {

			// $request->setParam('year=2019');
			if (!empty($key) && is_string($key) && !is_numeric($key) && $value===-999) {
				$this->_params = '';
			// $request->setParam('year', 2019);
			} else {
				$this->_params = [];
			}
		}

		// reset
		// $request->setParam('year', NULL)
		if ($value === NULL) {
			if (is_string($this->_params)) {
				$p = []; parse_str($this->_params, $p);
				unset($p[$key]);
				$this->_params = http_build_query($p);
			} else {
				unset($this->_params[$key]);
			}

		// $request->setParam('year=2019')
		} elseif ($value === -999) {

			if ($key instanceof \CURLFile) {
				$this->addFile($key);
			} elseif (strpos($key, '@')===0) {
				$this->addFile(substr($key, 1));
			} else {
				if (is_string($this->_params)) {

					if (strpos($key, '=') !== false) {
						$o = []; $p = [];
						parse_str($this->_params, $o);
						parse_str(trim($key, '&?'), $p);
						$p = array_merge($o, $p);
						$this->_params = http_build_query($p);
					} else {
						$p = $this->_params . '&' . trim((string)$key, '&?');
						$this->_params = trim($p, '&?');
					}
				} else {
					$p = []; parse_str($key, $p);
					$this->_params = array_merge($this->_params, $p);
				}
			}

		// $request->setParam('year', 2019)
		} else {

			if ($value instanceof \CURLFile) {
				$this->addFile($value, $key);
			} elseif (strpos($value, '@')===0) {
				$this->addFile(substr($value, 1), $key);
			} else {
				if (is_string($this->_params)) {
					if (is_numeric($key) && strpos($value, '=') !== false) {
						$o = []; $p = [];
						parse_str($this->_params, $o);
						parse_str(trim($value, '&?'), $p);
						$p = array_merge($o, $p);
						$this->_params = http_build_query($p);
					} else {
						$p = []; parse_str($this->_params, $p);
						$p[(string)$key] = $value;
						$this->_params = http_build_query($p);
					}
				} else {
					if (is_numeric($key) && strpos($value, '=') !== false) {
						$p = []; parse_str(trim($value, '&?'), $p);
						$this->_params = array_merge($this->_params, $p);
					} else {
						$this->_params[(string)$key] = $value;
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Returns request headers
	 * @return array
	 */
	public function getHeaders($format = FALSE)
	{
		if ($format===true) {
			$arr = [];
			foreach ($this->_headers as $key => $value) { $arr[] = $key . ': ' . trim((string)$value); }
			return $arr;
		} else {
			return $this->_headers;
		}
	}

	/**
	 * Set request headers
	 * @param string|array $headers
	 * @param mixed $value
	 * @return Request
	 */
	public function setHeaders($headers, $value = NULL)
	{
		// reset
		if ($headers === NULL || (is_array($headers) && empty($headers))) {
			$this->_headers = [];
			return $this;
		}

		$list = [];

		// check data
		if (!is_array($this->_headers)) { $this->_headers = []; }

		// $request->setHeaders('header', 'value');
		if (is_string($headers) && !is_numeric($headers)) {
			$list[$headers] = $value;
		// $request->setHeaders(['X-Foo: Bom', 'Y-Foo' => Hello', ...]);
		} elseif (is_array($headers)) {
			$list = $headers;
		}

		// process data
		foreach ($list as $key => $value) {
			$k = NULL; $v = NULL;

			if (is_numeric($key) && is_string($value)) {
				$pos = strpos($value, ':');
				if ($pos!==false) {
					$k = trim(substr($value, 0, $pos));
					$v = ltrim(substr($value, $pos+1, strlen($value)));
				}
			} else {
				$k = trim($key);
				$pos = strpos($value, ':');

				if ($pos!==false) {
					$k = trim(substr($value, 0, $pos));
					$v = ltrim(substr($value, $pos+1, strlen($key)));
				} else {
					$v = $value;
				}
			}

			if ($k) {
				if ($v === NULL) {
					unset($this->_headers[$k]);
				} elseif (!empty($v)) {
					$this->_headers[$k] = $v;
				}
			}
		}

		return $this;
	}

	/**
	 * Returns cURL options
	 * @return array
	 */
	public function getCurlOptions()
	{
		return $this->_curlOptions;
	}

	/**
	 * Set CURL multiple options
	 * @param array $options
	 * @param mixed $value
	 * @return Request component
	 */
	public function setCurlOptions($options, $value = NULL)
	{
		// reset
		if ($options === NULL || (is_array($options) && empty($options))) {

			$this->_curlOptions = [];

		// $request->setCurlOptions(OPTION, VALUE);
		} elseif (is_int($options)) {
			if ($value === NULL) {
				unset($this->_curlOptions[$options]);
			} else {
				$this->_curlOptions[$options] = $value;
			}

		// $request->setCurlOptions([OPTION, VALUE]);
		} else if (is_array($options) && count($options)===2 && !empty($options[0]) && is_int($options[0])) {
			if ($options[1] === NULL) {
				unset($this->_curlOptions[$options[0]]);
			} else {
				$this->_curlOptions[$options[0]] = $options[1];
			}

		// $request->setCurlOptions([OPTION1 => VALUE, OPTION2 => VALUE, ...]);
		} else {
			foreach ($options as $option => $value) {
				if ($option && is_int($option)) {
					if ($value === NULL) {
						unset($this->_curlOptions[$option]);
					} else {
						$this->_curlOptions[$option] = $value;
					}
				}
			}
		}

		return $this;
	}

	/**
	 * Get user agent
	 * @return string
	 */
	public function getUserAgent()
	{
		return $this->_userAgent;
	}

	/**
	 * Set user agent
	 * @param string $value
	 * @return Client
	 */
	public function setUserAgent($value)
	{
		if ($value && is_string($value)) {
			$this->_userAgent = trim($value);
		}

		return $this;
	}

	/**
	 * Get user agent
	 * @return string
	 */
	public function getExpectedType()
	{
		return $this->_expectedType;
	}

	/**
	 * Set user agent
	 * @param string $type
	 * @return Client
	 */
	public function setExpectedType($type)
	{
		if ($type && is_string($type)) {
			$this->_expectedType = trim($type);
		}

		return $this;
	}

	/**
	 * Get files
	 * @return \CURLFile[] list
	 */
	public function getFiles()
	{
		$files = [];
		if (!empty($this->_params) && is_array($this->_params)) {
			foreach ($this->_params as $key => $param) {
				if (is_object($param) && $param instanceof \CURLFile) {
					$files[$key] = $param;
				}
			}
		}

		return $files;
	}

	/**
	 * Set files (override previously set)
	 * @param array $files
	 * @return Request
	 */
	public function setFiles(array $files)
	{
		if (!empty($files)) {
			foreach ($files as $key => $file) {
				$this->addFile($file, $key);
			}
		}

		return $this;
	}

	/**
	 * Upload a file
	 * @param string $file Path to the file which will be uploaded
	 * @param string $postname Name of the file to be used in the upload data
	 * @param string $mimeType Mimetype of the file
	 * @return Request
	 */
	public function addFile($file, $postname = NULL, $mimeType = NULL)
	{
		$curlFile = NULL;
		$fileCount = count($this->getFiles());
		$nextPostName = 'file' . ($fileCount+1);

		if (!empty($postname) && !is_numeric($postname)) {
			$postname = trim($postname);
		} else {
			if ($file instanceof \CURLFile && !empty($file->postname)) {
				$postname = $file->postname;
			}

			if (empty($postname) || is_numeric($postname)) {
				$postname = $nextPostName;
			}
		}

		// remove
		if (!empty($postname) && $file===NULL) {
			$this->_params[$postname] = NULL;
			unset($this->_params[$postname]);
			return $this;
		}

		// CURLFile
		if ($file instanceof \CURLFile) {

			$curlFile = $file;
			$curlFile->postname = $postname;

		// Array
		} elseif (is_array($file)) {

			$path = !empty($file[0]) ? trim($file[0]) : NULL;
			if ($path && is_file($path)) {

				$curlFile = new \CURLFile($path);

				if (!empty($file[1]) && is_string($file[1])) {
					$postname = trim($file[1]);
				} else {
					$postname = $nextPostName;
				}

				$curlFile->postname = $postname;

				if (!empty($file[2]) && is_string($file[2])) {
					$mimeType = trim($file[2]);
				}
			}

		// String - path
		} elseif (is_string($file) && is_file($file)) {

			$curlFile = new \CURLFile($file);
			$curlFile->postname = $postname;

		}

		// add paraeter
		if ($curlFile) {

			// check params type (convert to an array if needed)
			if (!empty($this->_params) && is_string($this->_params)) {
				parse_str($this->_params, $this->_params);
			}

			if (!empty($mimeType)) {
				$curlFile->mime = $mimeType;
			}

			$this->_params[$postname] = $curlFile;
		}

		return $this;
	}

	/**
	 * Set Basic Authentication
	 * @param string $username
	 * @param string $password
	 * @return Request
	 */
	public function setBasicAuth($username, $password = '')
	{
		if (is_array($username) && array_key_exists(0, $username) && array_key_exists(1, $username)) {
			$this->setCurlOptions(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			$this->setCurlOptions(CURLOPT_USERPWD, $username[0] . ':' . $username[1]);
		} else {
			$this->setCurlOptions(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			$this->setCurlOptions(CURLOPT_USERPWD, $username . ':' . $password);
		}
		return $this;
	}

    /**
     * Set Digest Authentication
     * @param string $username
     * @param string $password
	 * @return Request
     */
    public function setDigestAuthentication($username, $password = '')
    {
		if (is_array($username) && array_key_exists(0, $username) && array_key_exists(1, $username)) {
			$this->setCurlOptions(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			$this->setCurlOptions(CURLOPT_USERPWD, $username[0] . ':' . $username[1]);
		} else {
			$this->setCurlOptions(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			$this->setCurlOptions(CURLOPT_USERPWD, $username . ':' . $password);
		}

		return $this;
    }

	/**
	 * Set expected response type
	 * @param string $type
	 * @return Request
	 */
	public function expect($type) 
	{
		$this->_expectedType = strtolower($type);
	}
	
	/**
	 * Execute request
	 * @param string $url
	 * @param string $method
	 * @param string|array $params
	 * @param array $headers
	 * @param array $curlOptions
	 * @return Request
	 * @throws \Exception
	 */
	public function execute($url, $method, $params = NULL, array $headers = NULL, array $curlOptions = NULL)
	{

		$this->setUrl($url);
		$this->setMethod($method);

		// check URL
		if (empty($this->_url)) {
			throw new \Exception('Invalid URL provided.');
		}

		// Initialize a cURL session
		$ch = curl_init();
		if (!$ch) {
			throw new \Exception('cURL session initialization failed.');
		}

		// default cURL options
		$this->setCurlOptions($this->_defaultOptions['curlOptions']);
		
		// user agent
		$this->setCurlOptions(CURLOPT_USERAGENT, $this->getUserAgent());

		// method arguments
		if (!empty($params)) { $this->setParams($params); }
		if (!empty($headers)) { $this->setHeaders($headers); }
		if (!empty($curlOptions)) { $this->setCurlOptions($curlOptions); }

		// ###
		// GET request
		// ###
		if ($this->_method === Client::METHOD_GET) {

			// add params
			if (!empty($this->_params)) {
				$this->_url .= (strpos($this->_url, '?')!==false ? '&' : '?') . $this->formatQuery();
			}

		// ###
		// POST request
		// ###
		} elseif ($this->_method === Client::METHOD_POST) {

			$this->setCurlOptions(CURLOPT_POST, 1);

			// urlencoded string like ‘para1=val1&para2=val2&…’
			// or as an array with the field name as key and field data as value.
			// If value is an array, the Content-Type header will be set to multipart/form-data.
			// https://www.brandonchecketts.com/archives/array-versus-string-in-curlopt_postfields

			//
			// multipart/form-data
			//
			if (is_array($this->_params)) {

				// headers didn't need to be set, cURL automatically sets headers
				// (like content-type: multipart/form-data; content-length...)
				// when you pass an array into CURLOPT_POSTFIELDS.
				$this->setCurlOptions(CURLOPT_POSTFIELDS, $this->_params);

			//
			// application/x-www-form-urlencoded
			//
			} else {

				$this->setCurlOptions(CURLOPT_POSTFIELDS, $this->_params);
				$this->setHeaders('Content-Type', 'application/x-www-form-urlencoded');
				$this->setHeaders('Content-Length', strlen($this->_params));

			}

		// ###
		// OTHERS
		// ###
		} else {

			$this->setHeaders('Content-Type', 'application/x-www-form-urlencoded');
			$this->setCurlOptions(CURLOPT_CUSTOMREQUEST, $this->_method);
			$this->setCurlOptions(CURLOPT_POSTFIELDS, $this->formatQuery());
			
			if ($this->_method  === Client::METHOD_HEAD) {
				$this->setCurlOptions(CURLOPT_NOBODY, true);
			}
		}
		
		// URL
		$this->setCurlOptions(CURLOPT_URL, $this->_url);
		
		// ---------------------------------------------------------------------
		
		// Set multiple options for a cURL transfer
		if (!empty($this->_curlOptions)) {
			curl_setopt_array($ch, $this->_curlOptions);
		}
		
		// headers
		if (!empty($this->_headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders(true));
		}

		// before execute
		if ($this->_beforeExecute && is_callable($this->_beforeExecute)) {
			call_user_func_array($this->_beforeExecute, [$this]);
		}
		
		// Execute
		$data = curl_exec($ch);

		// Response
		$response = new Response($ch, $data, $this->expectedType);

		// Close handle
		curl_close($ch);
		
		// ---------------------------------------------------------------------

		// log call
		if (1===2 && $this->_options['log'] === true) {
			$log = $this->requestMethod . ' > ' . $requestUrl . "\n"
				. '<strong>request:</strong>' . "\n"
 				. json_encode($this->requestOptions['params']) . "\n"
				. '<strong>response:</strong>' . "\n"
				. $this->response->body;

			//Yii::log($log, CLogger::LEVEL_INFO, 'Client');
		}

		return $response;
	}

	/**
	 * Format query
	 * @return string
	 */
	private function formatQuery()
	{
		$query = '';
		if (!empty($this->_params)) {
			if (is_array($this->_params)) {
				$query = http_build_query($this->_params, '', '&');
			} else {
				$query = ltrim((string)$this->_params, '?&');
			}
		}

		return $query;
	}


















    /**
     * Set Proxy
     * Set an HTTP proxy to tunnel requests through.
     * @param $proxy The HTTP proxy to tunnel requests through. May include port number.
     * @param $port The port number of the proxy to connect to. This port number can also be set in $proxy.
     * @param $username The username to use for the connection to the proxy.
     * @param $password The password to use for the connection to the proxy.
	 * @return Request
     */
    public function setProxy($proxy, $port = NULL, $username = NULL, $password = NULL)
    {
        $this->setCurlOptions(CURLOPT_PROXY, $proxy);

        if (is_numeric($port) && $port>0) {
            $this->setCurlOptions(CURLOPT_PROXYPORT, $port);
        }

        if ($username !== null && $password !== null) {
            $this->setCurlOptions(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        }

		return $this;
    }

}
