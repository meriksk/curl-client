<?php

namespace CurlClient\Handlers;
use CurlClient\Handlers\DefaultResponseHandler;

/**
 * RestJsonHandler class file.
 */
class JsonResponseHandler extends DefaultResponseHandler
{

	/**
	 * Parses response data
	 *
	 * @param mixed $data
	 * @param array $options
	 * @return mixed the value encoded in <i>json</i> in appropriate PHP type.
	 */
	public function parse($data, $options = NULL)
	{
		$output = NULL;
		if (is_string($data)) {
			$output = json_decode($this->stripBom($data), true);
		}
		
		return $output;
	}
	

	/**
	 * Converts response to an Array
	 * @param mixed $data
	 * @return array
	 */
	public function toArray($data)
	{
		return json_decode($this->stripBom($data), true);
	}

	/**
	 * Serializes data
	 * @param mixed $data
	 * @param int $options [optional] <p>
	 * Bitmask consisting of <b>JSON_HEX_QUOT</b>,
	 * <b>JSON_HEX_TAG</b>,
	 * <b>JSON_HEX_AMP</b>,
	 * <b>JSON_HEX_APOS</b>,
	 * <b>JSON_NUMERIC_CHECK</b>,
	 * <b>JSON_PRETTY_PRINT</b>,
	 * <b>JSON_UNESCAPED_SLASHES</b>,
	 * <b>JSON_FORCE_OBJECT</b>,
	 * <b>JSON_UNESCAPED_UNICODE</b>. The behaviour of these
	 * constants is described on
	 * the JSON constants page.
	 * </p>
	 * @return string
	 */
	public function serialize($data, $options = NULL)
	{
		$output = NULL;
		if ($options===NULL || !is_numeric($options)) {
			$options = JSON_NUMERIC_CHECK;
		}

		$arr = json_decode($this->stripBom($data), true);
		if ($arr!==NULL) {
			$output = json_encode($arr, $options);
		}

		return $output;
	}

}