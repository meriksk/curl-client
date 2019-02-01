<?php

namespace Curl;

use CurlClient\Client;

/**
 * Response class file.
 *
 * @property mixed $data Response data
 * @property mixed $body Response body (raw)
 * @property array $headers Response headers
 * @property mixed $info Information regarding a specific transfer
 * @property array $requestHeaders Request headers
 */
class Response
{

	/**
	 * Response body
	 * @var mixed
	 */
	public $body;

	/**
	 * Response HTTP code
	 * @var int
	 */
	public $httpCode = 0;

	/**
	 * Response content type
	 * @var string
	 */
	public $contentType;

	/**
	 * A string containing the last error for the current session
	 * @var string|null
	 */
	public $error = NULL;

	/**
	 * The error number or 0 (zero) if no error occurred
	 * @var int
	 */
	public $errno = 0;

	/**
	 * Total size of all headers received
	 * @var int
	 */
	public $headerSize;

	/**
	 * Total transaction time in seconds for last transfer
	 * @var float
	 */
	public $requestSize;

	/**
	 * Total transaction time in seconds for last transfer
	 * @var float
	 */
	public $totalTime;

	/**
	 * Number of redirects, with the CURLOPT_FOLLOWLOCATION option enabled
	 * @var int
	 */
	public $redirectCount = 0;


	private $_handler;
	private $_info;
	private $_headers = [];
	private $_requestHeaders = [];


	/**
	 * Class constructor
	 * @param Resource $curl A cURL handle returned by curl_init().
	 * @param mixed $data <b>result</b> on success or <b>FALSE</b> on failure.
	 * @param string $expectedType
	 */
	public function __construct($curl, $data = NULL, $expectedType = NULL)
	{

		if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

		if ($curl && is_resource($curl)) {

			// curl handle
			$this->_info = curl_getinfo($curl);

			// Response body
			$headerSize = $this->getInfo('header_size');
			$this->body = substr($data, $headerSize);

			// fix for curl bug
			$this->httpCode = $this->getInfo('http_code');

			// Return a string containing the last error for the current session
			$this->error = curl_error($curl);

			// Return the last error number
			$this->errno = curl_errno($curl);

			// response headers
			if ($headerSize > 0) {

				$this->_headers = $this->parseResponseHeaders($data);

				// set public attributes
				if (preg_match('@([\w/+]+)(;\s+charset=(\S+))?@i', $this->getInfo('content_type'), $m)) {
					if (isset($m[1])) {
						$this->contentType = trim($m[1]);
					}
				}
			}

			// request headers
			if (!empty($this->_info['request_header'])) {
				$this->_requestHeaders = $this->parseRequestHeaders($this->_info['request_header']);
			}

			$this->headerSize = (float)$this->getInfo('header_size');
			$this->requestSize = (float)$this->getInfo('request_size');
			$this->totalTime = (float)$this->getInfo('total_time');
			$this->redirectCount = (int)$this->getInfo('redirect_count');
			
			// init response handler
			$this->initHandler($expectedType);

		}
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
	 * Returns <b>TRUE<b> if success, <b>FALSE</b> otherwise
	 * @return bool
	 */
	public function isSuccess()
	{
		return !$this->isError();
	}

	/**
	 * Returns <b>TRUE<b> if success, <b>FALSE</b> otherwise
	 * @return bool
	 */
	public function isError()
	{
		return $this->errno>0 || $this->httpCode<200 || $this->httpCode>=400;
	}

	/**
	 * Returns response body
	 * @return mixed
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 *  Get cURL request info
	 * @param int $name
	 * @return mixed
	 */
	public function getInfo($name = NULL)
	{
		return $name!==NULL ? (array_key_exists($name, $this->_info) ? $this->_info[$name] : NULL) : $this->_info;
	}

	/**
	 * Returns response headers
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
	 * Returns request headers
	 * @return array
	 */
	public function getRequestHeaders($format = FALSE)
	{
		if ($format===true) {
			$arr = [];
			foreach ($this->_requestHeaders as $key => $value) { $arr[] = $key . ': ' . trim((string)$value); }
			return $arr;
		} else {
			return $this->_requestHeaders;
		}
	}

	/**
	 * Returns Content-Type: of the requested document.
	 * NULL indicates server did not send valid Content-Type: header
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}

	/**
	 * Return a string containing the last error for the current session
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Return the last error number
	 * @return int
	 */
	public function getErrno()
	{
		return $this->errno;
	}

	/**
	 * Returns total size of all headers received
	 * @return int
	 */
	public function getHeaderSize()
	{
		return $this->headerSize;
	}

	/**
	 * Returns request size
	 * @return int
	 */
	public function getRequestSize()
	{
		return $this->requestSize;
	}

	/**
	 * Returns total transaction time in seconds for last transfer
	 * @return int
	 */
	public function getTotalTime()
	{
		return $this->totalTime;
	}

	/**
	 * Returns number of redirects, with the CURLOPT_FOLLOWLOCATION option enabled
	 * @return int
	 */
	public function getRedirectCount()
	{
		return $this->redirectCount;
	}

	/**
	 * Parses response data
	 * @param array $options
	 * @return mixed
	 * @throws Exception
	 */
	public function getData($options = NULL)
	{
		if ($this->_handler) {
			return $this->_handler->parse($this->body, $options);
		} else {
			return NULL;
		}
	}

	/**
	 * Serialize data with corresponding handler
	 * @param mixed $options
	 * @return mixed serialized data
	 */
	public function serialize($options = NULL)
	{
		if ($this->_handler) {
			return $this->_handler->serialize($this->body, $options);
		} else {
			return $data;
		}
	}

	/**
	 * Converts response to an Array
	 * @param mixed $data
	 * @return array
	 */
	public function toArray()
	{
		if ($this->_handler) {
			return $this->_handler->toArray($this->body);
		} else {
			return [];
		}
	}

	/**
	 * Initialize data handler
	 * @param string $type Expected response type
	 * @return void
	 */
	private function initHandler($type)
	{
		$handler = NULL;
		if (!empty($type) && is_string($type)) {
			$type = strtolower(trim($type));
		}

		$namespace = __NAMESPACE__ . '\Handlers';
		switch ($type) {
			case 'application/json':
			case Client::RESPONSE_JSON:
				$handler = "$namespace\\JsonResponseHandler";
				break;
			case 'text/xml':
			case 'application/xml':
			case Client::RESPONSE_XML:
				$handler = "$namespace\\XmlResponseHandler";
				break;
			default:
				$handler = "$namespace\\DefaultResponseHandler";
		}

		// set handler
		$this->_handler = new $handler;
	}

	/**
	 * Get and parse headers from CURL response
	 * @param string cURL response data
	 * @return int
	 */
	private function parseResponseHeaders($data)
	{
		$headers = [];
		if ($data) {
			$lines = explode("\r\n\r\n", $data, 2);
			$headersText = '';

			if ($lines[0]==='HTTP/1.1 100 Continue' && isset($lines[1])) {
				$lines = explode("\r\n\r\n", $lines[1], 2);
				$headersText = trim($lines[0]);
			} else {
				$headersText = trim($lines[0]);
			}

			if ($headersText) {
				$lines = explode("\r\n", $headersText);
				foreach ($lines as $i => $line) {
					if ($i === 0) {
						if (empty($this->httpCode)) {
							$p = explode(' ', $line);
							$this->httpCode = intval($p[1]);
						}
					} else {
						list ($key, $value) = explode(': ', $line);
						$headers[$key] = trim($value);
					}
				}
			}
		}

		return $headers;
	}

	/**
	 * Get and parse headers from CURL request
	 * @param string cURL request data
	 * @return int
	 */
	private function parseRequestHeaders($data)
	{
		$headers = [];
		if ($data) {
			$lines = explode("\r\n\r\n", $data, 2);
			$headersText = trim($lines[0]);

			if ($headersText) {
				$lines = explode("\r\n", $headersText);

				foreach ($lines as $i => $line) {
					if ($i > 0) {
						list ($key, $value) = explode(': ', $line);
						$headers[$key] = trim($value);
					}
				}
			}
		}

		return $headers;
	}
}