<?php

namespace Fuzz\ApiServer\Tests\Logging;

use Fuzz\ApiServer\RequestTrace\Facades\RequestTracer;
use Fuzz\ApiServer\RequestTrace\Middleware\RequestTraceMiddleware;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class RequestTraceMiddlewareTest extends AppTestCase
{
	public function testItReadsAwsTraceHeader()
	{
		$middleware = new RequestTraceMiddleware;
		$closure = function () {
			$response = Mockery::mock(Response::class);
			$response_headers = Mockery::mock(HeaderBag::class);
			$response->headers = $response_headers;

			$response_headers->shouldReceive('set')->with('X-Request-Id', 'fooId')->once();

			return $response;
		};

		$request = Mockery::mock(Request::class);
		$headers = Mockery::mock(HeaderBag::class);
		$request->headers = $headers;

		$headers->shouldReceive('has')->with('X-Amzn-Trace-Id')->once()->andReturn(true);
		$request->shouldReceive('header')->with('X-Amzn-Trace-Id')->once()->andReturn('fooId');

		$middleware->handle($request, $closure);

		$this->assertSame('fooId', RequestTracer::getRequestId());
	}

	public function testItReadsFuzzTraceHeader()
	{
		$middleware = new RequestTraceMiddleware;
		$closure = function () {
			$response = Mockery::mock(Response::class);
			$response_headers = Mockery::mock(HeaderBag::class);
			$response->headers = $response_headers;

			$response_headers->shouldReceive('set')->with('X-Request-Id', 'fuzzFooId')->once();

			return $response;
		};

		$request = Mockery::mock(Request::class);
		$headers = Mockery::mock(HeaderBag::class);
		$request->headers = $headers;

		$headers->shouldReceive('has')->with('X-Amzn-Trace-Id')->once()->andReturn(false);
		$headers->shouldReceive('has')->with('X-Fz-Trace-Id')->once()->andReturn(true);
		$request->shouldReceive('header')->with('X-Fz-Trace-Id')->once()->andReturn('fuzzFooId');

		$middleware->handle($request, $closure);

		$this->assertSame('fuzzFooId', RequestTracer::getRequestId());
	}

	public function testItSetUUIDIfNoTraceHeadersSet()
	{
		$middleware = new RequestTraceMiddleware;
		$closure = function () {
			$response = Mockery::mock(Response::class);
			$response_headers = Mockery::mock(HeaderBag::class);
			$response->headers = $response_headers;

			$response_headers->shouldReceive('set')->with('X-Request-Id', Mockery::on(function (string $id) {
				return true;
			}))->once();

			return $response;
		};

		$request = Mockery::mock(Request::class);
		$headers = Mockery::mock(HeaderBag::class);
		$request->headers = $headers;

		$headers->shouldReceive('has')->with('X-Amzn-Trace-Id')->once()->andReturn(false);
		$headers->shouldReceive('has')->with('X-Fz-Trace-Id')->once()->andReturn(false);

		$middleware->handle($request, $closure);

		$this->assertTrue(is_string(RequestTracer::getRequestId()));
	}
}