<?php

namespace CurlClient\Tests;

use CurlClient\Client;
use CurlClient\Tests\BaseUnitTestCase;

/**
 * Tests for CurlClient\Client.
 * Run:
 *
 * vendor/bin/phpunit --colors=always tests/ClientTest
 * vendor/bin/phpunit --colors=always --filter "/testSetOptions$/" tests/ClientTest
 *
 * @covers \CurlClient\Client
 */
class ClientTest extends BaseUnitTestCase
{

    public function testInstance()
    {
		$client = new Client();
		$this->assertInstanceOf('\CurlClient\Client', $client);
    }

    public function testSetOptions()
    {
		$client = new Client([
			'baseUrl' => URL_BASE,
			'userAgent' => 'TestUserAgent',
			'expectedType' => Client::RESPONSE_JSON,
			'timeout' => 2000,
			'params' => 'year=2019',
			'headers' => ['X-Foo' => 'Bom'],
			'curlOptions' => [CURLOPT_VERBOSE => 1],
			'files' => [
				$this->getTestFilePath(),
				['/Users/merik/www/static/out.png', 'custom_postname'],
			]
		]);

		$this->assertInstanceOf('\CurlClient\Client', $client);
		$this->assertInternalType('array', $client->params);
		$this->assertInternalType('array', $client->headers);
		$this->assertInternalType('array', $client->curlOptions);
		$this->assertEquals(URL_BASE, $client->baseUrl);
		$this->assertEquals('TestUserAgent', $client->userAgent);
		$this->assertEquals(Client::RESPONSE_JSON, $client->expectedType);
		$this->assertArrayHasKey('year', $client->params);
		$this->assertArrayHasKey('file1', $client->params);
		$this->assertArrayHasKey('custom_postname', $client->params);
		$this->assertArrayHasKey('X-Foo', $client->headers);
		$this->assertArrayHasKey(CURLOPT_VERBOSE, $client->curlOptions);
		$this->assertInstanceOf('\CURLFile', $client->params['file1']);
		$this->assertInstanceOf('\CURLFile', $client->params['custom_postname']);
		$this->assertEquals(2019, $client->params['year']);
    }

	public function testSetBaseUrl()
	{
		$client = new Client();

		// property
		$client->baseUrl = URL_GET;
		$this->assertEquals(URL_GET, $client->baseUrl);

		// method
		$client->setBaseUrl(URL_BASE);
		$this->assertEquals(URL_BASE, $client->baseUrl);
	}

    public function testSetParams()
    {
		$client = new Client();
		$client->setParams(['year' => 2018]);
		$this->assertInternalType('array', $client->params);
		$this->assertArrayHasKey('year', $client->params);
		$this->assertEquals(2018, $client->params['year']);
    }

    public function testSetParam()
    {
		$client = new Client();
		$client->setParam('year', 2018);
		$this->assertInternalType('array', $client->params);
		$this->assertArrayHasKey('year', $client->params);
		$this->assertEquals(2018, $client->params['year']);
    }

    public function testSetHeader()
    {
		$client = new Client();
		$client->setHeader('X-Foo: Bom');
		$this->assertInternalType('array', $client->headers);
		$this->assertArrayHasKey('X-Foo', $client->headers);
		$this->assertEquals('Bom', $client->headers['X-Foo']);
    }

	public function testSetHeaders()
    {
		$client = new Client();
		$client->setHeaders(['X-Foo: Bom', 'Y-Foo' => 'Hello']);
		$this->assertInternalType('array', $client->headers);
		$this->assertArrayHasKey('X-Foo', $client->headers);
		$this->assertArrayHasKey('Y-Foo', $client->headers);
		$this->assertEquals('Bom', $client->headers['X-Foo']);
		$this->assertEquals('Hello', $client->headers['Y-Foo']);
    }

    public function testAddHeader()
    {
		$client = new Client();
		$client->addHeader('X-Foo', 'Bom');
		$client->addHeader('Y-Foo: Hello');
		$this->assertInternalType('array', $client->headers);
		$this->assertArrayHasKey('X-Foo', $client->headers);
		$this->assertArrayHasKey('Y-Foo', $client->headers);
		$this->assertEquals('Bom', $client->headers['X-Foo']);
		$this->assertEquals('Hello', $client->headers['Y-Foo']);
	}

    public function testAddHeaders()
    {
		$client = new Client();
		$client->addHeaders(['X-Foo: Bom', 'Y-Foo' => 'Hello']);
		$client->addHeaders(['Z-Foo: Hi']);
		$this->assertInternalType('array', $client->headers);
		$this->assertArrayHasKey('X-Foo', $client->headers);
		$this->assertArrayHasKey('Y-Foo', $client->headers);
		$this->assertArrayHasKey('Z-Foo', $client->headers);
		$this->assertEquals('Bom', $client->headers['X-Foo']);
		$this->assertEquals('Hello', $client->headers['Y-Foo']);
		$this->assertEquals('Hi', $client->headers['Z-Foo']);
	}

    public function testSetCurlOptions()
    {
		$client = new Client();
		$client->setCurlOptions([CURLOPT_VERBOSE => 1, CURLOPT_REFERER => URL_BASE]);
		$this->assertInternalType('array', $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_VERBOSE, $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_REFERER, $client->curlOptions);
		$this->assertEquals(1, $client->curlOptions[CURLOPT_VERBOSE]);
		$this->assertEquals(URL_BASE, $client->curlOptions[CURLOPT_REFERER]);
    }

    public function testSetCurlOption()
    {
		$client = new Client();
		$client->setCurlOption(CURLOPT_REFERER, URL_BASE);
		$this->assertTrue(is_array($client->curlOptions));
		$this->assertArrayHasKey(CURLOPT_REFERER, $client->curlOptions);
		$this->assertEquals(URL_BASE, $client->curlOptions[CURLOPT_REFERER]);
    }

    public function testAddCurlOptions()
    {
		$client = new Client();
		$client->addCurlOptions([CURLOPT_VERBOSE => 1, CURLOPT_REFERER => URL_BASE]);
		$client->addCurlOptions([CURLOPT_USERAGENT => 'Mozilla']);
		$this->assertInternalType('array', $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_VERBOSE, $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_REFERER, $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_USERAGENT, $client->curlOptions);
		$this->assertEquals(1, $client->curlOptions[CURLOPT_VERBOSE]);
		$this->assertEquals(URL_BASE, $client->curlOptions[CURLOPT_REFERER]);
		$this->assertEquals('Mozilla', $client->curlOptions[CURLOPT_USERAGENT]);
    }

    public function testAddCurlOption()
    {
		$client = new Client();
		$client->addCurlOption(CURLOPT_VERBOSE, 1);
		$client->addCurlOption(CURLOPT_REFERER, URL_BASE);
		$client->addCurlOption(CURLOPT_USERAGENT, 'Mozilla');
		$this->assertInternalType('array', $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_VERBOSE, $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_REFERER, $client->curlOptions);
		$this->assertArrayHasKey(CURLOPT_USERAGENT, $client->curlOptions);
		$this->assertEquals(1, $client->curlOptions[CURLOPT_VERBOSE]);
		$this->assertEquals(URL_BASE, $client->curlOptions[CURLOPT_REFERER]);
		$this->assertEquals('Mozilla', $client->curlOptions[CURLOPT_USERAGENT]);
    }

	public function testBasicAuth()
	{
		$client = new Client();
		$client->setBasicAuth('username', 'pwd');
		$this->assertEquals('username:pwd', $client->curlOptions[CURLOPT_USERPWD]);
	}

	public function testSetTimeout()
	{
		$client = new Client();
		$client->setTimeout(3.1);

		if (defined('CURLOPT_TIMEOUT_MS')) {
			$this->assertEquals(3100, $client->curlOptions[CURLOPT_TIMEOUT_MS]);
		} else {
			$this->assertEquals(3, $client->curlOptions[CURLOPT_TIMEOUT]);
		}
	}

	public function testAddFiles()
	{
		// test: params + headers + options
		$client = new Client();

		$client->addFile('/Users/merik/www/static/out.png');
		$client->addFiles([
			'/Users/merik/www/static/out.png',
			['/Users/merik/www/static/out.png', 'logo'],
		]);

		$client = $client->request;

		$this->assertArrayHasKey('file1', $client->params);
		$this->assertArrayHasKey('file2', $client->params);
		$this->assertArrayHasKey('file3', $client->params);
		$this->assertInstanceOf('CURLFile', $client->params['file1']);
		$this->assertInstanceOf('CURLFile', $client->params['file2']);
		$this->assertInstanceOf('CURLFile', $client->params['file3']);
		$this->assertEquals('file1', $client->params['file1']->postname);
		$this->assertEquals('file2', $client->params['file2']->postname);
		$this->assertEquals('logo', $client->params['file3']->postname);
	}

	public function testExecute()
	{

		// test: params + headers + options
		$client = new Client();

		// set client options
		$client->expectedType = Client::RESPONSE_JSON;
		$client->userAgent = 'CustomUserAgent';
		$client->params = ['year' => 2018, 'month' => 11];
		$client->headers = ['X-Foo' => 'Bom'];
		$client->setHeader('Content-Type', 'application/json');
		$client->curlOptions = [CURLOPT_REFERER => 'http://www.example.org'];
		$client->setCurlOption(CURLOPT_HTTPHEADER, 'Content-Type: application/xml');

		// execute
		$response = $client->execute(URL_GET, Client::METHOD_GET, [
			'month' => 12,
		], [
			'Y-Foo' => 'Hi',
		]);

		// request
		$request = $client->request;
		$this->assertEquals(URL_GET . '?year=2018&month=12', $request->url);
		$this->assertEquals(Client::METHOD_GET, $request->method);
		$this->assertEquals('CustomUserAgent', $request->userAgent);
		$this->assertInternalType('array', $request->params);
		$this->assertEquals(2018, $request->params['year']);
		$this->assertEquals(12, $request->params['month']);
		$this->assertArrayHasKey('Content-Type', $request->headers);
		$this->assertArrayHasKey('X-Foo', $request->headers);
		$this->assertArrayHasKey('Y-Foo', $request->headers);
		$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_HTTPHEADER, $request->curlOptions);

		// response
		$this->assertInstanceOf('\CurlClient\Response', $response);
		$this->assertTrue($response->isSuccess());
		$this->assertInternalType('array', $response->body);
		$this->assertArrayHasKey('args', $response->body);
		$this->assertArrayHasKey('month', $response->body['args']);
		$this->assertEquals(12, $response->body['args']['month']);
	}

	public function testGet()
	{
		$client = new Client(['baseUrl' => URL_BASE]);
		$params = ['param' => 'foo'];
		$headers = ['X-Foo: Bom'];
		$curlOptions = [CURLOPT_REFERER => 'https://www.example.org'];

		$response = $client->get('/get', $params, $headers, $curlOptions);
		$request = $client->request;

		$this->assertEquals(URL_BASE, $client->baseUrl);
		$this->assertEquals(Client::METHOD_GET, $request->method);
		$this->assertEquals(URL_BASE . '/get?param=foo', $request->url);
		$this->assertInternalType('array', $request->params);
		$this->assertArrayHasKey('param', $request->params);
		$this->assertInternalType('array', $request->headers);
		$this->assertArrayHasKey('X-Foo', $request->headers);
		$this->assertInternalType('array', $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);
	}

	public function testPost()
	{
		$client = new Client();
		$params = ['username' => 'foo'];
		$headers = ['X-Foo: Bom'];

		$response = $client->post(URL_POST, $params, $headers);
		$request = $client->request;

		$this->assertEquals(Client::METHOD_POST, $request->method);
		$this->assertEquals(URL_BASE . '/post', $request->url);
		$this->assertInternalType('array', $request->params);
		$this->assertArrayHasKey('username', $request->params);
		$this->assertArrayHasKey('X-Foo', $request->headers);
	}


}