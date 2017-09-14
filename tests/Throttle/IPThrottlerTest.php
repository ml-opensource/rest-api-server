<?php

namespace Fuzz\ApiServer\Tests\Throttle;

use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Throttling\IPThrottler;
use Fuzz\HttpException\TooManyRequestsHttpException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Symfony\Component\HttpFoundation\HeaderBag;

class IPThrottlerTest extends AppTestCase
{
	public function testItThrowsTooManyRequestsExceptionIfItAtLimit()
	{
		$throttler     = new IPThrottler;
		$request       = Mockery::mock(Request::class);
		$closure       = function (Request $request) {
			$this->fail('Should not be called.');
		};
		$max_attempts  = 1;
		$decay_minutes = 1;

		$request->shouldReceive('ip')->once()->andReturn('127.0.0.1');
		$request->shouldReceive('getRequestUri')->once()->andReturn('foo/bar/baz');
		$request->shouldReceive('method')->once()->andReturn('post');
		Redis::shouldReceive('get')->once()->with('throttle:' . hash('sha256', 'ip:foo/bar/baz:post:127.0.0.1'))
			->andReturn(1); // At rate limit

		$this->expectException(TooManyRequestsHttpException::class);
		$response = $throttler->handle($request, $closure, $max_attempts, $decay_minutes);
	}

	public function testItIncrementsAndAddsHeadersIfNotAtRateLimit()
	{
		$throttler         = new IPThrottler;
		$request           = Mockery::mock(Request::class);
		$response          = Mockery::mock(Response::class);
		$headers           = Mockery::mock(HeaderBag::class);
		$response->headers = $headers;
		$closure           = function (Request $request) use ($response) {
			return $response;
		};

		$max_attempts  = 3;
		$decay_minutes = 1;

		$request->shouldReceive('ip')->once()->andReturn('127.0.0.1');
		$request->shouldReceive('getRequestUri')->once()->andReturn('foo/bar/baz');
		$request->shouldReceive('method')->once()->andReturn('post');
		Redis::shouldReceive('get')->once()->with('throttle:' . hash('sha256', 'ip:foo/bar/baz:post:127.0.0.1'))
			->andReturn(1); // Not at rate limit
		Redis::shouldReceive('incr')->once()->andReturn(2);
		$headers->shouldReceive('add')->once()->with([
			'X-RateLimit-Limit'     => 3,
			'X-RateLimit-Remaining' => 1,
		]);

		$response = $throttler->handle($request, $closure, $max_attempts, $decay_minutes);
		$this->assertSame($response, $response);
	}
}