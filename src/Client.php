<?php

namespace Curl;

use CurlClient\Response;

/**
 * cURL Client
 *
 * <code>
 * <pre>
 * <b>Basic usage:</b>
 *
 * $client = new CurlClient\Client();
 * $client->expect(Client::RESPONSE_JSON); // or 'json'
 * $client->curlOptions = [CURLOPT_REFERER => 'https://www.google.com'];
 * $response = $client->get('http://remote-host/api', ['id' => 10]);
 *
 * <b>Headers + cURL options:</b>
 *
 * $client = new CurlClient\Client();
 * $client->expect('json');
 * $response = $client->get('http://remote-host/api', ['id' => 10], [
 *     'X-Foo' => 'Bam',
 * ], [
 *     'CURLOPT_REFERER' => 'http://example-referer.com',
 * ]);
 *
 * <b>Add headers to request message:</b>
 *
 * // function
 * $client->setHeaders('Y-Foo', 'Hello');
 * // function (multiple)
 * $client->setHeaders(['X-Foo: Bom', 'Y-Foo' => 'Hello']);
 * // parameter
 * $client->headers = ['X-Foo: Bom', 'Y-Foo' => 'Hello'];
 * // reset
 * $client->setHeaders('X-Foo', NULL);
 *
 * <b>Upload a file (multipart/form-data)</b>
 *
 * $client = new CurlClient\Client();
 * $response = $client->post('http://remote-host/api', [
 *     '@file' => '/path-to-the-file',
 * ]);
 *
 * or
 *
 * $client = new CurlClient\Client();
 * $client->params = ['@file' => '/path-to-the-file'];
 * $response = $client->post('http://remote-host/api');
 *
 * <b>Get response body:</b>
 *
 * // raw
 * $body = $response->body;
 *
 * // parsed
 * $body = $response->parse();
 *
 * // serialized
 * $body = $response->serialize();
 *
 * <b>Expected body format:</b>
 *
 * $client = new CurlClient\Client();
 * $client->expectedType = Client::RESPONSE_JSON;
 * ...
 * or 
 * ...
 * $client->expect('json');
 * ...
 * $body = $response->body; // returns array
 * <pre>
 * </code>
 *
 * @property string $baseUrl
 * @property array $params
 * @property array $headers
 * @property array $curlOptions
 * @property string $userAgent
 * @property string $expectedType
 * @property bool $sendRawData
 * @version 0.1
 */

class Client
{

	/**
	 * @var string
	 */
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PATCH = 'PATCH';
	const METHOD_HEAD = 'HEAD';
	const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * @var string
	 */
	const RESPONSE_JSON = 'json';
	const RESPONSE_XML = 'xml';


	/**
	 * Set expected response type
	 * @var string
	 */
	public $expectedType;

	/**
	 * Send raw data to the server
	 * @var bool
	 */
	public $sendRawData = false;

	/**
	 * Request instance
	 * @var Request
	 */
	private $_request;

	/**
	 * Base URL for future requests
	 * @var string
	 */
	private $_baseUrl;

	/**
	 * @var bool
	 */
	private $_initialization = true;


	// -------------------------------------------------------------------------
	// -------------------------------------------------------------------------


	/**
	 * Class constructor
	 * @throws Exception
	 */
	public function __construct(array $options = [])
	{

		if (!extension_loaded('curl')) {
            throw new \ErrorException('The cURL extension is not available!');
        }

		// request
		$this->_request = new Request();

		// init
		$this->setOptions($options);
		
		// initialization completed
		$this->_initialization = true;

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
	 * Set Client options
	 * @param array $options
	 * @return Client
	 */
	public function setOptions(array $options)
	{
		foreach ($options as $key => $value) {
			if ($key && is_string($key)) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Returns request instance
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 * Set request instance
	 * @param Request $request
	 * @return Client
	 * @throws \Exception
	 */
	public function setRequest($request)
	{
		if ($request && $request instanceof Request) {
			$this->_request = $request;
		} else {
			throw new \Exception('Request must be instance of "Request" class.');
		}

		return $this;
	}

	/**
	 * Get base URL
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->_baseUrl;
	}

	/**
	 * Get base URL
	 * @param string $url
	 * @return Client
	 */
	public function setBaseUrl($url)
	{
		if ($url && is_string($url)) {
			$this->_baseUrl = trim($url);
		}

		return $this;
	}

	/**
	 * Returns request params
	 * @return string|array
	 */
	public function getParams()
	{
		return $this->_request->getParams();
	}

	/**
	 * Set parameters for all future requests
	 * @param string|array $params
	 * @return Client
	 * @throws \Exception
	 */
	public function setParams($params)
	{
		$this->_request->setParams($params);
		return $this;
	}

	/**
	 * Set parameter for all future requests
	 * @param string $key
	 * @param mixed $value
	 * @return Client
	 */
	public function setParam($key, $value = NULL)
	{
		$this->_request->setParam($key, $value);
		return $this;
	}

	/**
	 * Add request parameter
	 * @param string $key
	 * @param string $value
	 * @return Request
	 */
	public function addParam($key, $value = NULL)
	{
		$this->_request->addParam($key, $value);
		return $this;
	}

	/**
	 * Add request parameters
	 * @param string|array $params
	 * @return Request
	 */
	public function addParams($params)
	{
		$this->_request->addParams($params);
		return $this;
	}

	/**
	 * Returns request headers
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->_request->getHeaders();
	}

	/**
	 * Set headers for all future requests
	 * @param array $headers
	 * @return Client
	 */
	public function setHeaders(array $headers)
	{
		$this->_request->setHeaders($headers);
		return $this;
	}

	/**
	 * Returns cURL options
	 * @return array
	 * @see Request::getCurlOptions()
	 */
	public function getCurlOptions()
	{
		return $this->_request->getCurlOptions();
	}

	/**
	 * Set cURL options for all future requests
	 * @param array $options
	 * @return Client
	 */
	public function setCurlOptions(array $options)
	{
		$this->_request->setCurlOptions($options);	
		return $this;
	}

	/**
	 * Get user agent
	 * @return string
	 */
	public function getUserAgent()
	{
		return $this->_request->getUserAgent();
	}

	/**
	 * Set user agent
	 * @param string $value
	 * @return Client
	 */
	public function setUserAgent($value)
	{
		$this->_request->setUserAgent($value);
		return $this;
	}

	/**
	 * Set the maximum number of seconds to allow cURL functions to execute.
	 * @param int $timeout seconds to timeout the HTTP call
	 * @return Client component
	 */
	public function setTimeout($timeout)
	{
		if (is_numeric($timeout) && $timeout>=0) {
			if (defined('CURLOPT_TIMEOUT_MS')) {
				$this->_request->setCurlOptions(CURLOPT_TIMEOUT_MS, $timeout*1000);
			} else {
				$this->_request->setCurlOptions(CURLOPT_TIMEOUT, $timeout);
			}
		}

		return $this;
	}
	
	/**
	 * Get files
	 * @return \CURLFile[] list
	 */
	public function getFiles()
	{
		return $this->_request->getFiles();
	}

	/**
	 * Set files (remove previous set)
	 * @param array $files
	 * @return Request
	 */
	public function setFiles(array $files)
	{
		$this->_request->setFiles($files);
		return $this;
	}
	
	/**
	 * Add files
	 * @param string $file Path to the file which will be uploaded
	 * @param string $postName Name of the file to be used in the upload data
	 * @param string $mimeType Mimetype of the file
	 * @return Client
	 */
	public function addFile($file, $postName = NULL, $mimeType = NULL)
	{
		$this->_request->addFile($file, $postName, $mimeType);
		return $this;
	}

	/**
	 * Add files
	 * @param array $files
	 * @return Client
	 */
	public function addFiles(array $files)
	{
		$this->_request->addFiles($files);
		return $this;
	}

	/**
	 * Set file log path
	 * @param string $path
	 * @return Client
	 */
	public function setLog($path)
	{
		if (is_string($path) && trim($path)!=='') {
			$this->_options['log'] = trim($path);
		}

		return $this;
	}

	/**
	 * Expect response type
	 * @param string $responseType
	 * @return Client
	 */
	public function expect($responseType)
	{
		$this->expectedType = $responseType;
	}
	
	/**
	 * HTTP Method Get
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function get($url = NULL, $params = NULL, array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_GET, $params, $headers, $curlOptions);
	}

	/**
	 * HTTP Method Post
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function post($url, $params = NULL, array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_POST, $params, $headers, $curlOptions);
	}

	/**
	 * HTTP Method Put
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function put($url, $params = NULL, array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_PUT, $params, $headers, $curlOptions);
	}

	/**
	 * HTTP Method Patch
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function patch($url, $params = NULL, array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_PATCH, $params, $headers, $curlOptions);
	}

	/**
	 * HTTP Method Delete
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function delete($url, $params = NULL, array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_DELETE, $params, $headers, $curlOptions);
	}

	/**
	 * HTTP Method Head
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function head($url, $params = NULL, array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_HEAD, $params, $headers, $curlOptions);
	}

	/**
	 * HTTP Method Options
	 * @param string $url
	 * @param string|array $params Array of request options to apply.
	 * @param array $headers
	 * @param array $curlOptions
	 * @return mixed Response
	 */
	public function options($url, $params = [], array $headers = [], array $curlOptions = [])
	{
		return $this->execute($url, self::METHOD_OPTIONS, $params, $headers, $curlOptions);
	}

	/**
	 * Execute request
	 * @param string $url
	 * @param string $method
	 * @param string|array $params
	 * @param array $headers
	 * @param array $curlOptions
	 * @return Client
	 * @throws \Exception
	 */
	public function execute($url = NULL, $method = NULL, $params = [], array $headers = [], array $curlOptions = [])
	{
		// URL
		if (!empty($this->_baseUrl)) {
			$requestUrl = $this->_baseUrl . trim((string)$url);
		} else {
			$requestUrl = trim((string)$url);
		}

		// invalid URL provided
		if (empty($requestUrl)) {
			throw new Exception('Invalid URL provided.');
		}

		// Response expected type
		if (!empty($this->expectedType)) {
			$this->_request->expect($this->expectedType);
		}

		// User agent
		if (!empty($this->userAgent)) {
			$this->_request->userAgent = trim($this->userAgent);
		}

		// execute call
		$response = $this->_request->execute($requestUrl, $method, $params, $headers, $curlOptions);

		// after execute
		//$this->afterExecute();

		return $response;
	}

}
