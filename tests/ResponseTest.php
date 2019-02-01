<?php

namespace CurlClient\Tests;

use CurlClient\Client;
use CurlClient\Request;

/**
 * Tests for CurlClient\Response.
 * @covers \CurlClient\Response
 */
class ResponseTest extends BaseUnitTestCase
{

	public function testInstance()
	{
		$request = new Request();
		$response = $request->execute(URL_GET, Client::METHOD_GET);

			$this->assertInstanceOf('\CurlClient\Response', $response);
			$this->assertObjectHasAttribute('body', $response);
			$this->assertObjectHasAttribute('contentType', $response);
			$this->assertObjectHasAttribute('httpCode', $response);
			$this->assertEquals(200, $response->httpCode);
	}

	public function testIsSuccess()
	{
		// 200
		$request = new Request();
		$response = $request->execute(URL_GET_200, Client::METHOD_GET);

			$this->assertTrue($response->isSuccess());
			$this->assertEmpty($response->error);
			$this->assertTrue($response->errno === 0);
			$this->assertEquals(200, $response->httpCode);

		// 301 (redirect)
		$request = new Request();
		$response = $request->execute(URL_GET_301, Client::METHOD_GET);

			$this->assertTrue($response->isSuccess());
			$this->assertEmpty($response->error);
			$this->assertTrue($response->errno === 0);
			$this->assertEquals(200, $response->httpCode);

	}

	public function testIsError()
	{
		// 500
		$request = new Request();
		$response = $request->execute(URL_GET_500, Client::METHOD_GET);

			$this->assertTrue($response->isError());
			$this->assertEquals(500, $response->httpCode);

		// timeout
		$request = new Request();
		$request->setCurlOptions(CURLOPT_TIMEOUT_MS, 100);
		$response = $request->execute('http://192.134.24.11', Client::METHOD_GET);

			$this->assertTrue($response->isError());
			$this->assertEquals($response->errno, CURLE_OPERATION_TIMEDOUT);
			$this->assertNotEquals(200, $response->httpCode);
	}

	public function testParseHeaders()
	{
		$request = new Request();
		$response = $request->execute(URL_GET, Client::METHOD_GET, [], ['X-Foo' => 'Bom']);

			// response headers
			$this->assertInternalType('array', $response->headers);
			$this->assertArrayHasKey('Content-Type', $response->headers);

			// rqeuest headers
			$this->assertInternalType('array', $response->requestHeaders);
			$this->assertArrayHasKey('X-Foo', $response->requestHeaders);
			$this->assertEquals('Bom', $response->requestHeaders['X-Foo']);

	}

	public function testParse()
	{
		// RAW
		$request = new Request();
		$response = $request->execute(URL_GET, Client::METHOD_GET);
		
			$this->assertInternalType('string', $response->body);
			$this->assertInternalType('string', $response->data);
			$this->assertEquals($response->body, $response->data);
			
		// JSON
		$request = new Request();
		$request->expect('json');
		$response = $request->execute(URL_GET, Client::METHOD_GET);
		
			$this->assertInternalType('string', $response->body);
			$this->assertInternalType('array', $response->data);
			$this->assertNotEmpty($response->data);
	}

	public function testToArray()
	{
		// RAW
		$request = new Request();
		$response = $request->execute(URL_GET, Client::METHOD_GET);
		$data = $response->toArray();

			$this->assertInternalType('array', $data);
			$this->assertEquals($response->data, $data[0]);
	}

}
