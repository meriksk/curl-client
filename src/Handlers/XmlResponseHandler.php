<?php

/**
 * RestXmlHandler class file.
 */
class XmlResponseHandler extends DefaultResponseHandler
{

	/**
	 *  @var string $namespace xml namespace to use with simple_load_string
	 */
	private $namespace;

	/**
	 * @var string
	 */
	private $rootNode = 'root';

	/**
	 * @var int $libxmlOpts see http://www.php.net/manual/en/libxml.constants.php
	 */
	private $libxmlOpts;

	/**
	 * @var boolean formatted output
	 */
	private $formatted = false;



	/**
	 * Class constructor
	 */
	public function __construct() {}


	/**
	 * Parses response data
	 * @param mixed $data
	 * @param array $options
	 * @return mixed
	 * @throws Exception
	 */
	public function parse($data, array $options = NULL)
	{
		$data = $this->stripBom($data);
		if (empty($data)) {
			return null;
		} else {
			$this->getOptions($options);
			$parsed = @simplexml_load_string($data, null, $this->libxmlOpts, $this->namespace);

			if ($parsed === false) {
				throw new \Exception("Unable to parse response as XML document.");
			}

			return $parsed;
		}
	}

	/**
	 * Get options
	 * @param array $options
	 */
	private function getOptions($options = NULL)
	{
		$this->rootNode = !empty($options['rootNode']) ? trim($options['rootNode']) : 'root';
		$this->formatted = (isset($options['formatted']) && $options['formatted']===true) ? true : false;
		$this->namespace = isset($options['namespace']) ? $options['namespace'] : '';
		$this->libxmlOpts = isset($options['libxmlOpts']) ? $options['libxmlOpts'] : 0;
	}

	/**
	 * @param mixed $data
	 * @param array $options
	 * @return string
	 * @throws Exception if unable to serialize
	 */
	public function serialize($data, array $options = NULL)
	{
		$this->getOptions($options);
		return $this->toXML($data, $this->rootNode);
	}

	/**
	 * The main function for converting to an XML document.
	 * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
	 *
	 * @param array $data
	 * @param string $rootNodeName - what you want the root node to be - defaultsto data.
	 * @param SimpleXMLElement $xml - should only be used recursively
	 * @return string XML
	 */
	public function toXML($data, $rootNodeName = 'ResultSet', &$xml=null) {

		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode')==1) {
			ini_set('zend.ze1_compatibility_mode', 0);
		}

		if (is_null($xml)) {
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
		}

		// loop through the data passed in.
		foreach ($data as $key => $value) {

			$numeric = false;

			// no numeric keys in our xml please!
			if (is_numeric($key)) {
				$numeric = 1;
				$key = $rootNodeName;
			}

			// delete any char not allowed in XML element names
			$key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value)) {
				$node = ($this->isAssoc($value) || $numeric) ? $xml->addChild($key) : $xml;

				// recrusive call.
				if ($numeric) {
					$key = 'anon';
				}

				$this->toXml($value, $key, $node);

			} else {

				// add single node.
				$xml->addChild($key, $this->formatValue($value));
			}
		}

		// formatted xml
		if ($this->formatted) {

			$doc = new DOMDocument('1.0');
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($xml->asXML());
			$doc->formatOutput = true;
			return $doc->saveXML();

		// unformatted xml
		} else {

			return $xml->asXML();

		}
	}


	/**
	 * Convert an XML document to a multi dimensional array
	 * Pass in an XML document (or SimpleXMLElement object) and this recrusively loops through and builds a representative array
	 *
	 * @param string $xml - XML document - can optionally be a SimpleXMLElement object
	 * @return array ARRAY
	 */
	public function toArray($xml)
	{
		if (is_string($xml)) {
			$xml = new SimpleXMLElement($xml);
		}

		$children = $xml->children();
		if (!$children) {
			return (string) $xml;
		}

		$arr = [];
		foreach ($children as $key => $node) {

			$node = $this->toArray($node);

			// support for 'anon' non-associative arrays
			if ($key === 'anon') {
				$key = count($arr);
			}

			// if the node is already set, put it into an array
			if (isset($arr[$key])) {
				if (!is_array($arr[$key]) || $arr[$key][0] == null) {
					$arr[$key] = [$arr[$key]];
				}

				$arr[$key][] = $node;
			} else {
				$arr[$key] = $node;
			}
		}
		return $arr;
	}

	/**
	 * determine if a variable is an associative array
	 * @param array $array
	 * @return bollean
	 */
	public function isAssoc($array)
	{
		return (is_array($array) && (0 !== count(array_diff_key($array, array_keys(array_keys($array))))));
	}

	/**
	 * Format XML value
	 * @param mixed $value
	 * @return mixed
	 */
	private function formatValue($value)
	{
		if ($value===true || $value===false) {
			return ($value===true) ? 'true' : 'false';
		}

		if (is_null($value)) {
			return 'null';
		}

		// default
		return htmlentities($value);
	}

}