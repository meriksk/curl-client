<?php

namespace CurlClient\Handlers;

/**
 * DefaultResponseHandler class file
 */
class DefaultResponseHandler
{

	/**
	 * Parses response data
	 * @param mixed $data
	 * @param array $options
	 * @return mixed
	 */
	function parse($data, $options = NULL)
	{
		return $data;
	}
	
	/**
	 * Converts response to an Array
	 * @param mixed $data
	 * @return array
	 */
	function toArray($data)
	{
		return is_array($data) ? $data : [$data];
	}

	/**
	 * Serializes data
	 * @param mixed $data
	 * @param array $options
	 * @return string
	 */
	function serialize($data, $options = NULL)
	{
		return $data;
	}

	/**
	 * Strop BOM
	 * @param string $body
	 * @return string
	 */
	protected function stripBom($body)
	{
		// UTF-8
		if (substr($body,0,3) === "\xef\xbb\xbf") {
			$body = substr($body,3);
		// UTF-32
		} else if (substr($body,0,4) === "\xff\xfe\x00\x00" || substr($body,0,4) === "\x00\x00\xfe\xff") {
			$body = substr($body,4);
		// UTF-16
		} else if (substr($body,0,2) === "\xff\xfe" || substr($body,0,2) === "\xfe\xff" ) {
			$body = substr($body,2);
		}

		return $body;
	}

}