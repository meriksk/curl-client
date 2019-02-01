<?php

namespace CurlClient\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base class for unit tests.
 */
abstract class BaseUnitTestCase extends TestCase
{
	
    protected function setUp()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('The cURL extension is not available!');
        }
    }
    
	public function getFilePath($filename)
	{
		return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $filename;
	}
    
	public function getTestFilePath($prefix = NULL)
	{
		return $this->getFilePath('file_500b.txt');
	}

}