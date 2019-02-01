<?php

namespace CurlClient\Tests;

use CurlClient\Client;
use CurlClient\Request;

/**
 * Tests for CurlClient\Request.
 * @covers \CurlClient\Request
 */
class RquestTest extends BaseUnitTestCase
{

	public function testInstance()
	{
		$request = new \CurlClient\Request();
		$this->assertInstanceOf('\CurlClient\Request', $request);
	}

    public function testSetOptions()
    {
		$request = new \CurlClient\Request([
			'url' => URL_GET,
			'method' => Client::METHOD_GET,
			'expectedType' => Client::RESPONSE_JSON,
			'params' => 'year=2019',
			'headers' => [
				'X-Foo: Bom',
			],
			'curlOptions' => [
				CURLOPT_REFERER => 'https://www.example.org',
			],
			'files' => [
				'/Users/merik/www/static/out.png',
				['/Users/merik/www/static/out.png', 'custom_postname'],
			],
		]);

		// add params
		$request->addParam('month', 11);

		$this->assertInstanceOf('\CurlClient\Request', $request);
		$this->assertInternalType('array', $request->params);
		$this->assertInternalType('array', $request->headers);
		$this->assertInternalType('array', $request->curlOptions);
		$this->assertEquals(URL_GET, $request->url);
		$this->assertEquals(Client::METHOD_GET, $request->method);
		$this->assertEquals('LuceonCurlClient', $request->userAgent);
		$this->assertEquals(Client::RESPONSE_JSON, $request->expectedType);
		$this->assertArrayHasKey('year', $request->params);
		$this->assertArrayHasKey('month', $request->params);
		$this->assertArrayHasKey('file1', $request->params);
		$this->assertArrayHasKey('custom_postname', $request->params);
		$this->assertArrayHasKey('X-Foo', $request->headers);
		$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);
		$this->assertEquals(2019, $request->params['year']);
		$this->assertEquals(11, $request->params['month']);
		$this->assertInstanceOf('\CURLFile', $request->params['file1']);
		$this->assertInstanceOf('\CURLFile', $request->params['custom_postname']);

		// set new parameters
		$request->method = Client::METHOD_POST;
		$request->params = ['param' => 1];
		$request->headers = ['Y-Foo' => 1];

		$this->assertEquals(Client::METHOD_POST, $request->method);
		$this->assertInternalType('array', $request->params);
		$this->assertInternalType('array', $request->headers);
		$this->assertArrayHasKey('param', $request->params);
		$this->assertArrayHasKey('X-Foo', $request->headers);
		$this->assertArrayHasKey('Y-Foo', $request->headers);
    }

	public function testSetUrl()
	{
		$request = new Request();

		$request->setUrl(URL_BASE);
		$this->assertEquals(URL_BASE, $request->url);

		$request->url = URL_GET;
		$this->assertEquals(URL_GET, $request->url);
	}

	public function testMethod()
	{
		$request = new Request();

		$request->setMethod(Client::METHOD_POST);
		$this->assertEquals(Client::METHOD_POST, $request->method);

		$request->method = Client::METHOD_GET;
		$this->assertEquals(Client::METHOD_GET, $request->method);
	}

	public function testSetParam()
	{
		$request = new Request();

		// string params (initial request params will be of type "string")
		$request->setParam('?year=2018&day=3');

			$this->assertInternalType('string', $request->params);
			$this->assertEquals('year=2018&day=3', $request->params);

		// add/modify/remove params (our request params should be of type "string")
		$request->setParam('year', 2019); // modify
		$request->setParam('month', 12); // add
		$request->setParam('', 5); // invalid
		$request->setParam('day', NULL); // remove
		$request->setParam('auth=1'); // string

			$this->assertInternalType('string', $request->params);
			$this->assertEquals('year=2019&month=12&auth=1', $request->params);

		// reset params
		$request->params = [];

		// named params (initial request params will be of type "array")
		$request->setParam('year', 2019);

			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('year', $request->params);

		// add/modify/remove params (our request params should be of type "array")
		$request->setParam('year', 2020); // modify
		$request->setParam('month', 2); // add
		$request->setParam('day=3'); // add
		$request->setParam('', 5); // invalid
		$request->setParam('day', NULL); // remove
		$request->setParam('auth=1'); // add

			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('year', $request->params);
			$this->assertArrayHasKey('month', $request->params);
			$this->assertArrayHasKey('auth', $request->params);
			$this->assertArrayNotHasKey('day', $request->params);

		// reset
		$request->params = [];

		// parameter - add/modify/remove
		$request->param = ['year', 2019]; // add
		$request->param = ['month', 10]; // add
		$request->param = ['day', 3]; // add
		$request->param = 'month=12&day=2&auth=1'; // modify
		$request->param = [0, 3]; // invalid
		$request->param = ['auth', NULL]; // remove

			$this->assertInternalType('array', $request->params);
			$this->assertCount(4, $request->params);
			$this->assertArrayHasKey('year', $request->params);
			$this->assertArrayHasKey('month', $request->params);
			$this->assertArrayHasKey('day', $request->params);
			$this->assertArrayHasKey('0', $request->params);
			$this->assertArrayNotHasKey('auth', $request->params);
			$this->assertEquals(2019, $request->params['year']);
			$this->assertEquals(12, $request->params['month']);
			$this->assertEquals(2, $request->params['day']);

		// add file parameter (@path)
		$request->setParam('logo', '@' . $this->getTestFilePath());

			$this->assertArrayHasKey('logo', $request->params);
			$this->assertInstanceOf('CURLFile', $request->params['logo']);

		// reset
		$request->params = [];

		// adding file to request will convert "string type" parameters to "array type" parameters
		$request->setParam('year=2019');
			$this->assertInternalType('string', $request->params);
		$request->setParam('logo', '@' . $this->getTestFilePath());
			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('year', $request->params);
			$this->assertArrayHasKey('logo', $request->params);
	}

	public function testSetParams()
	{
		// init
		$request = new Request();

		// string params (initial request params will be of type "string")
		$request->setParams('?year=2018&month=12');
		$request->setParams('day=1');
		$request->setParams(['day' => NULL, 'username=john', 'auth']);

			$this->assertInternalType('string', $request->params);
			$this->assertEquals('year=2018&month=12&username=john&1=auth', $request->params);

		// reset
		$request->params = [];

			$this->assertNull($request->params);

		// array params (initial request params will be of type "array")
		$request->setParams(['year' => 2019, 'month' => 2, 'username=john', 'auth']);
		$request->setParams('day=1');
		$request->setParams(['day' => 3, 'auth' => NULL]);

			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('month', $request->params);
			$this->assertArrayHasKey('day', $request->params);
			$this->assertArrayHasKey('username', $request->params);
			$this->assertArrayNotHasKey('auth', $request->params);
			$this->assertEquals(2019, $request->params['year']);
			$this->assertEquals(2, $request->params['month']);
			$this->assertEquals(3, $request->params['day']);

		// override - mixed
		$request->setParams([
			'date' => '?date=2018',
			'year' => 2018,
			'message=hello',
			'logo' => '@' . $this->getTestFilePath(),
			'@' . $this->getTestFilePath(),
		]);

			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('date', $request->params);
			$this->assertArrayHasKey('year', $request->params);
			$this->assertArrayHasKey('message', $request->params);
			$this->assertArrayHasKey('logo', $request->params);
			$this->assertArrayHasKey('file2', $request->params);
			$this->assertEquals('?date=2018', $request->params['date']);
			$this->assertEquals(2018, $request->params['year']);
			$this->assertEquals('hello', $request->params['message']);
			$this->assertInstanceOf('CURLFile', $request->params['logo']);
			$this->assertInstanceOf('CURLFile', $request->params['file2']);
	}

    public function testSetHeaders()
    {
		$request = new Request();

		// function (single)
		$request->setHeaders('X-Foo', 'Bom');
		$request->setHeaders('Y-Foo', 'Hello');
		$request->setHeaders('Z-Foo', 'Hi');
		$request->setHeaders('Z-Foo', NULL);

			$this->assertInternalType('array', $request->headers);
			$this->assertArrayHasKey('X-Foo', $request->headers);
			$this->assertArrayHasKey('Y-Foo', $request->headers);
			$this->assertArrayNotHasKey('Z-Foo', $request->headers);
			$this->assertEquals('Bom', $request->headers['X-Foo']);
			$this->assertEquals('Hello', $request->headers['Y-Foo']);

		// reset
		$request->headers = [];

			$this->assertInternalType('array', $request->headers);
			$this->assertEmpty($request->curlOptions);

		// function (multiple)
		$request->setHeaders([
			'X-Foo' => 'Bom',
			'Y-Foo: Hello',
			'Z-Foo: Test',
			'Z-Foo' => NULL,
			'X-Invalid=1',
		]);

			$this->assertInternalType('array', $request->headers);
			$this->assertArrayHasKey('X-Foo', $request->headers);
			$this->assertArrayHasKey('Y-Foo', $request->headers);
			$this->assertArrayNotHasKey('Z-Foo', $request->headers);
			$this->assertArrayNotHasKey('X-Invalid', $request->headers);
			$this->assertEquals('Bom', $request->headers['X-Foo']);
			$this->assertEquals('Hello', $request->headers['Y-Foo']);

		// reset
		$request->headers = [];

		// parameter (single)
		$request->headers = ['X-Foo: Boo'];
		$request->headers = ['Y-Foo' => 'Hello'];
		$request->headers = ['Z-Foo' => NULL];

			$this->assertInternalType('array', $request->headers);
			$this->assertArrayHasKey('X-Foo', $request->headers);
			$this->assertArrayNotHasKey('Z-Foo', $request->headers);

		// reset
		$request->headers = [];

		// parameter (multiple)
		$request->headers = [
			'X-Foo: Boo',
			'Y-Foo' => 'Hello',
			'Z-Foo' => NULL,
			'X-Invalid',
			'Y-Invalid' => '',
		];

			$this->assertInternalType('array', $request->headers);
			$this->assertArrayHasKey('X-Foo', $request->headers);
			$this->assertArrayNotHasKey('Z-Foo', $request->headers);
			$this->assertArrayNotHasKey('X-Invalid', $request->headers);
			$this->assertArrayNotHasKey('Y-Invalid', $request->headers);

		// modify/remove
		$request->headers = [
			'X-Foo: BoBoo',
			'Y-Foo' => NULL,
		];

			$this->assertInternalType('array', $request->headers);
			$this->assertArrayNotHasKey($request->headers);
			$this->assertEquals('BoBoo', $request->headers['X-Foo']);
    }

    public function testSetCurlOptions()
    {
		$request = new Request();

		// function (parameter)
		$request->setCurlOptions(CURLOPT_REFERER, 'Test');
		$request->setCurlOptions(CURLOPT_PORT, 88);
		$request->setCurlOptions(CURLOPT_MAXFILESIZE, 1024);
		$request->setCurlOptions(CURLOPT_MAXFILESIZE, NULL);

			$this->assertInternalType('array', $request->curlOptions);
			$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);
			$this->assertArrayHasKey(CURLOPT_PORT, $request->curlOptions);
			$this->assertArrayNotHasKey(CURLOPT_MAXFILESIZE, $request->curlOptions);
			$this->assertEquals('Test', $request->curlOptions[CURLOPT_REFERER]);
			$this->assertEquals(88, $request->curlOptions[CURLOPT_PORT]);

		// reset
		$request->curlOptions = [];

			$this->assertInternalType('array', $request->curlOptions);
			$this->assertEmpty($request->curlOptions);

		// function (array)
		$request->setCurlOptions([
			CURLOPT_REFERER => 'Test',
			CURLOPT_PORT => 88,
			CURLOPT_MAXFILESIZE => NULL,
		]);

			$this->assertInternalType('array', $request->curlOptions);
			$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);
			$this->assertArrayHasKey(CURLOPT_PORT, $request->curlOptions);
			$this->assertArrayNotHasKey(CURLOPT_MAXFILESIZE, $request->curlOptions);
			$this->assertEquals('Test', $request->curlOptions[CURLOPT_REFERER]);
			$this->assertEquals(88, $request->curlOptions[CURLOPT_PORT]);

		// parameter (single)
		$request->curlOptions = [];
		$request->curlOptions = [CURLOPT_PORT, 88];
		$request->curlOptions = [CURLOPT_MAXFILESIZE, 1024];
		$request->curlOptions = [CURLOPT_MAXFILESIZE, NULL];

			$this->assertInternalType('array', $request->curlOptions);
			$this->assertArrayHasKey(CURLOPT_PORT, $request->curlOptions);
			$this->assertArrayNotHasKey(CURLOPT_MAXFILESIZE, $request->curlOptions);
			$this->assertEquals(88, $request->curlOptions[CURLOPT_PORT]);

		// parameter (multiple)
		$request->curlOptions = [
			CURLOPT_REFERER => 'Test',
			CURLOPT_PORT => 88,
			CURLOPT_MAXFILESIZE => NULL,
		];

			$this->assertInternalType('array', $request->curlOptions);
			$this->assertArrayHasKey(CURLOPT_PORT, $request->curlOptions);
			$this->assertArrayNotHasKey(CURLOPT_MAXFILESIZE, $request->curlOptions);
			$this->assertEquals(88, $request->curlOptions[CURLOPT_PORT]);
    }

	public function testSetFiles()
	{
		$request = new Request();
		$testFile = $this->getTestFilePath();
		
		// function
		$request->setFiles([
			$testFile,
			'logo' => $testFile,
			[$testFile],
			[$testFile, 'image'],
			new \CURLFile($this->getTestFilePath()),
			new \CURLFile($this->getTestFilePath(), NULL, 'image1'),
			'image2' => new \CURLFile($testFile, NULL, 'image_2'),
			'image3' => new \CURLFile($testFile, NULL, 'image_3'),
			'invalid' => '/tmp/invalid.txt',
		]);

			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('file1', $request->params);
			$this->assertArrayHasKey('logo', $request->params);
			$this->assertArrayHasKey('file3', $request->params);
			$this->assertArrayHasKey('image', $request->params);
			$this->assertArrayHasKey('file5', $request->params);
			$this->assertArrayHasKey('image1', $request->params);
			$this->assertArrayHasKey('image2', $request->params);
			$this->assertArrayHasKey('image3', $request->params);
			$this->assertArrayNotHasKey('invalid', $request->params);
			//$this->assertInstanceOf('CURLFile', $request->params['file1']);
			//$this->assertInstanceOf('CURLFile', $request->params['file2']);
			//$this->assertInstanceOf('CURLFile', $request->params['file3']);

		// remove
		$request->setFiles(['logo' => NULL]);
	
			$this->assertArrayNotHasKey('logo', $request->params);
			
		// reset
		$request->params = [];
			
		// parameter
		$request->files = [
			$testFile,
			'logo' => $testFile,
		];
		
			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('file1', $request->params);
			$this->assertArrayHasKey('logo', $request->params);
	}

	public function testAddFile()
	{
		$request = new Request();
		$testFile = $this->getTestFilePath();
		
		// function
		$request->addFile($testFile);
		$request->addFile($testFile, 'image1');
		$request->addFile($testFile, 'image2', 'image/gif');
		$request->addFile([$testFile, 'image3']);
		$request->addFile([$testFile, 'image4', 'image/gif']);
		$request->addFile(new \CURLFile($testFile));
		$request->addFile(new \CURLFile($testFile, 'image/gif'));
		$request->addFile(new \CURLFile($testFile, 'image/gif', 'image5'));
		$request->addFile('/tmp/invalid.txt', 'image6');
		
			$this->assertInternalType('array', $request->params);
			$this->assertArrayHasKey('file1', $request->params);
			$this->assertArrayHasKey('image1', $request->params);
			$this->assertArrayHasKey('image2', $request->params);
			$this->assertArrayHasKey('image3', $request->params);
			$this->assertArrayHasKey('image4', $request->params);
			$this->assertArrayHasKey('file6', $request->params);
			$this->assertArrayHasKey('file7', $request->params);
			$this->assertArrayHasKey('image5', $request->params);
			$this->assertArrayNotHasKey('image6', $request->params);
			$this->assertInstanceOf('\CURLFile', $request->params['file1']);
			$this->assertInstanceOf('\CURLFile', $request->params['image3']);
			$this->assertEmpty($request->params['file1']->mime);
			$this->assertEmpty($request->params['image1']->mime);
			$this->assertEquals('file1', $request->params['file1']->postname);
			$this->assertEquals('image1', $request->params['image1']->postname);
			$this->assertEquals('image5', $request->params['image5']->postname);
			$this->assertEquals('file7', $request->params['file7']->postname);
			$this->assertEquals('image/gif', $request->params['image2']->mime);
			$this->assertEquals('image/gif', $request->params['image4']->mime);
			$this->assertEquals('image/gif', $request->params['file7']->mime);
			$this->assertEquals('image/gif', $request->params['image5']->mime);
	}

	public function testSetBasicAuth()
	{
		// method
		$request = new Request();
		$request->setBasicAuth('username', 'password');

		$this->assertArrayHasKey(CURLOPT_HTTPAUTH, $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_USERPWD, $request->curlOptions);
		$this->assertEquals(CURLAUTH_BASIC, $request->curlOptions[CURLOPT_HTTPAUTH]);
		$this->assertEquals('username:password', $request->curlOptions[CURLOPT_USERPWD]);

		// attributes
		$request = new Request();
		$request->basicAuth = ['username', 'password'];

		$this->assertArrayHasKey(CURLOPT_HTTPAUTH, $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_USERPWD, $request->curlOptions);
		$this->assertEquals(CURLAUTH_BASIC, $request->curlOptions[CURLOPT_HTTPAUTH]);
		$this->assertEquals('username:password', $request->curlOptions[CURLOPT_USERPWD]);
	}

	public function testSetDigestAuthentication()
	{
		// method
		$request = new Request();
		$request->setDigestAuthentication('username', 'password');

		$this->assertArrayHasKey(CURLOPT_HTTPAUTH, $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_USERPWD, $request->curlOptions);
		$this->assertEquals(CURLAUTH_DIGEST, $request->curlOptions[CURLOPT_HTTPAUTH]);
		$this->assertEquals('username:password', $request->curlOptions[CURLOPT_USERPWD]);

		// attributes
		$request = new Request();
		$request->digestAuthentication = ['username', 'password'];

		$this->assertArrayHasKey(CURLOPT_HTTPAUTH, $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_USERPWD, $request->curlOptions);
		$this->assertEquals(CURLAUTH_DIGEST, $request->curlOptions[CURLOPT_HTTPAUTH]);
		$this->assertEquals('username:password', $request->curlOptions[CURLOPT_USERPWD]);
	}

	public function testExecute()
	{

		// test: params + headers + options
		$request = new Request();

		// set default options
		$request->userAgent = 'UserAgent1';
		$request->params = ['year' => 2018, 'day' => 1, 'cache' => 0];
		$request->headers = ['Content-Type' => 'application/json', 'X-Foo: Bom'];
		$request->curlOptions = [CURLOPT_REFERER => 'http://www.example.org', CURLOPT_PASSWORD => 'pwd'];

		$this->assertInstanceOf('\CurlClient\Request', $request);
		$this->assertArrayHasKey('year', $request->params);
		$this->assertArrayHasKey('day', $request->params);
		$this->assertArrayHasKey('cache', $request->params);
		$this->assertArrayHasKey('Content-Type', $request->headers);
		$this->assertArrayHasKey('X-Foo', $request->headers);
		$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);

		// pass new options to the call
		$response = $request->execute(URL_GET, Client::METHOD_GET, [
			'year' => 2019,
			'month' => 12,
			'day' => NULL,
			'name=john',
			'query' => 'size=12'
		], [
			'X-Foo' => 'Hello',
			'Y-Foo: World',
		], [
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_PASSWORD => NULL,
		]);

		$this->assertInstanceOf('CurlClient\Response', $response);
		$this->assertArrayHasKey('year', $request->params);
		$this->assertArrayHasKey('month', $request->params);
		$this->assertArrayHasKey('name', $request->params);
		$this->assertArrayHasKey('query', $request->params);
		$this->assertArrayNotHasKey('day', $request->params);
		$this->assertEquals(URL_GET.'?year=2019&cache=0&month=12&name=john&query=size%3D12', $request->getUrl());
		$this->assertArrayHasKey('Content-Type', $request->headers);
		$this->assertArrayHasKey('X-Foo', $request->headers);
		$this->assertArrayHasKey('Y-Foo', $request->headers);
		$this->assertArrayHasKey(CURLOPT_REFERER, $request->curlOptions);
		$this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $request->curlOptions);
		$this->assertArrayNotHasKey(CURLOPT_PASSWORD, $request->curlOptions);

		// check response
		$body = $response->body;
		$arr = json_decode($body, true);

		if ($arr===NULL) {
			$this->fail('Failed to decode response JSON.');
		} else {
			$this->assertArrayHasKey('month', $arr['args']);
			$this->assertArrayHasKey('year', $arr['args']);
			$this->assertArrayHasKey('headers', $arr);
			$this->assertEquals('http://www.example.org', $arr['headers']['Referer']);
			$this->assertEquals('application/json', $arr['headers']['Content-Type']);
			$this->assertEquals('Hello', $arr['headers']['X-Foo']);
		}
	}

	public function testGet()
	{
		$request = new Request();
		$response = $request->execute(URL_GET, Client::METHOD_GET, ['name=john']);

		$this->assertInstanceOf('\CurlClient\Response', $response);
		$this->assertEquals(Client::METHOD_GET, $request->method);
		$this->assertEquals(URL_GET.'?name=john', $request->getUrl());
	}

	public function testPost()
	{
		$request = new Request();

		// key-value
		$request->setParams(['year' => 2019, 'month' => 10]);
		$response = $request->execute(URL_GET, Client::METHOD_POST, [
			'name=john',
			'cache' => 1,
		]);

		$this->assertInstanceOf('\CurlClient\Response', $response);
		$this->assertArrayHasKey('year', $request->params);
		$this->assertArrayHasKey('month', $request->params);
		$this->assertArrayHasKey('name', $request->params);
		$this->assertArrayHasKey('cache', $request->params);
	}

}
