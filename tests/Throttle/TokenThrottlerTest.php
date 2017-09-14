<?php

namespace Fuzz\ApiServer\Tests\Throttle;

use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Throttling\TokenThrottler;
use Fuzz\HttpException\TooManyRequestsHttpException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use LucaDegasperi\OAuth2Server\Authorizer;
use Mockery;
use Symfony\Component\HttpFoundation\HeaderBag;

class TokenThrottlerTest extends AppTestCase
{
	public function testItThrowsTooManyRequestsExceptionIfItAtLimit()
	{
		$throttler     = new TokenThrottler;
		$request       = Mockery::mock(Request::class);
		$request_headers   = Mockery::mock(HeaderBag::class);
		$request->headers  = $request_headers;
		$closure       = function (Request $request) {
			$this->fail('Should not be called.');
		};
		$max_attempts  = 1;
		$decay_minutes = 1;

		$this->app->bind('oauth2-server.authorizer', function () {
			$authorizer = Mockery::mock(Authorizer::class);

			$authorizer->shouldReceive('getAccessToken')->once()->andReturn('FooBarToken');

			return $authorizer;
		});

		$request_headers->shouldReceive('get')->with('Authorization', null)->once()->andReturn('Bearer FooBarToken');

		Redis::shouldReceive('get')->once()->with('throttle:' . hash('sha256', 'token:Bearer FooBarToken'))
			->andReturn(1); // At rate limit

		$this->expectException(TooManyRequestsHttpException::class);
		$response = $throttler->handle($request, $closure, $max_attempts, $decay_minutes);
	}

	public function testItIncrementsAndAddsHeadersIfNotAtRateLimit()
	{
		$throttler         = new TokenThrottler;
		$request           = Mockery::mock(Request::class);
		$response          = Mockery::mock(Response::class);
		$headers           = Mockery::mock(HeaderBag::class);
		$request_headers   = Mockery::mock(HeaderBag::class);
		$request->headers  = $request_headers;
		$response->headers = $headers;
		$closure           = function (Request $request) use ($response) {
			return $response;
		};

		$max_attempts  = 3;
		$decay_minutes = 1;

		$request_headers->shouldReceive('get')->with('Authorization', null)->once()->andReturn('Bearer FooBarToken');

		Redis::shouldReceive('get')->once()->with('throttle:' . hash('sha256', 'token:Bearer FooBarToken'))
			->andReturn(1); // Not at rate limit
		Redis::shouldReceive('incr')->once()->andReturn(2);
		$headers->shouldReceive('add')->once()->with([
			'X-RateLimit-Limit'     => 3,
			'X-RateLimit-Remaining' => 1,
		]);

		$response = $throttler->handle($request, $closure, $max_attempts, $decay_minutes);
		$this->assertSame($response, $response);
	}

	public function testAssertThrottleThrowsTooManyRequestsExceptionIfAtRateLimit()
	{
		$max_attempts  = 1;
		$decay_minutes = 1;
		$access_token  = 'FooBarToken';

		Redis::shouldReceive('get')->once()->with('throttle:' . hash('sha256', 'token:FooBarToken'))
			->andReturn(1); // At rate limit

		$this->expectException(TooManyRequestsHttpException::class);
		$this->expectExceptionMessage('Too Many Requests.');
		TokenThrottler::assertThrottle($access_token, $max_attempts, $decay_minutes);
	}

	public function testAssertThrottleReturnsAnArrayOfHeadersIfNotAtRateLimit()
	{
		$max_attempts  = 3;
		$decay_minutes = 1;
		$access_token  = 'FooBarToken';

		Redis::shouldReceive('get')->once()->with('throttle:' . hash('sha256', 'token:FooBarToken'))
			->andReturn(1); // Not at rate limit
		Redis::shouldReceive('incr')->once()->andReturn(2);

		$headers = TokenThrottler::assertThrottle($access_token, $max_attempts, $decay_minutes);
		$this->assertSame([
			'X-RateLimit-Limit'     => $max_attempts,
			'X-RateLimit-Remaining' => 1,
		], $headers);
	}
}