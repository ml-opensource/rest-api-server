<?php

namespace Fuzz\ApiServer\Tests\Throttle;

use Carbon\Carbon;
use Closure;
use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Throttling\BaseThrottler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Predis\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\HeaderBag;

class BaseThrottlerTest extends AppTestCase
{
	public function testItReturnsKeyAsHash()
	{
		$throttler = new FooThrottler;

		$prefix = 'foo';
		$attributes = [$prefix, 'bar', 'baz'];

		$expect = 'throttle:' . hash('sha256', "$prefix:bar:baz");

		$this->assertSame($expect, $throttler->getKey($attributes));
	}

	public function testItAddsHeadersToResponse()
	{
		$response = Mockery::mock(Response::class);
		$headers = Mockery::mock(HeaderBag::class);
		$response->headers = $headers;

		$throttler = new FooThrottler;

		$headers->shouldReceive('add')->once()->with([
			'X-RateLimit-Limit' => 4,
			'X-RateLimit-Remaining' => 2,
			'Retry-After' => 9,
			'X-RateLimit-Reset' => Carbon::now()->getTimestamp() + 9,
		]);
		$this->assertSame($response, $throttler->addHeaders($response, 4, 2, 9));
	}

	public function testItCreatesRateLimitedResponse()
	{
		$throttler = new FooThrottler;

		$throttler->setMaxAttempts(6);

		Cache::shouldReceive('get')->with('someKey')->once()->andReturn(6);

		$response = $throttler->getResponse('someKey');
		$this->assertTrue($response instanceof JsonResponse);
		$this->assertSame(429, $response->getStatusCode());
		$this->assertSame(['error' => 'too_many_requests', 'error_description' => 'Too Many Requests.'], json_decode($response->getContent(), true));
	}

	public function testItIncrementsWithExpiration()
	{
		$throttler = new FooThrottler;

		$throttler->setMaxAttempts(6);

		Cache::shouldReceive('increment')->once()->with('foo')->andReturn(11);

		$this->assertSame(11, $throttler->increment('foo'));
	}

	public function testItSetsDecayInMinutes()
	{
		$throttler = new FooThrottler;
		$throttler->setDecay(1);
		$this->assertSame(1, $throttler->getDecayMinutes());
		$this->assertSame(60, $throttler->getDecaySeconds());
	}

	public function testItSetsAndGetsMaxAttempts()
	{
		$throttler = new FooThrottler;
		$throttler->setMaxAttempts(100);
		$this->assertSame(100, $throttler->getMaxAttempts());
	}

	public function testItGetsAttemptsLeftAndThenLoadsThemFromLocalProperty()
	{
		$throttler = new FooThrottler;
		$throttler->setMaxAttempts(100);

		Cache::shouldReceive('get')->once()->with('foo')->andReturn(3);
		$this->assertSame(97, $throttler->getAttemptsLeft('foo'));
		$this->assertSame(97, $throttler->getAttemptsLeft('foo')); // Check twice
	}

	public function testItReturnsMaxAttemptsIfNoKeyExistsInRedis()
	{
		$throttler = new FooThrottler;
		$throttler->setMaxAttempts(100);

		Cache::shouldReceive('get')->once()->with('foo')->andReturn(null);

		Cache::shouldReceive('put')->with('foo', 0, 1)->once();

		$this->assertSame(100, $throttler->getAttemptsLeft('foo'));
		$this->assertSame(100, $throttler->getAttemptsLeft('foo')); // Check twice
	}

	public function testItDeterminesIfIsAtLimit()
	{
		$throttler = new FooThrottler;
		$throttler->setMaxAttempts(3);

		Cache::shouldReceive('get')->with('foo')->once()->andReturn(0);

		$this->assertSame(false, $throttler->isAtLimit('foo'));

		Cache::shouldReceive('increment')->once()->andReturn(1);
		Cache::shouldReceive('put')->with('foo', 1, 1)->once();
		$throttler->increment('foo');
		$this->assertSame(false, $throttler->isAtLimit('foo'));

		Cache::shouldReceive('increment')->once()->andReturn(2);
		$throttler->increment('foo');
		$this->assertSame(false, $throttler->isAtLimit('foo'));

		Cache::shouldReceive('increment')->once()->andReturn(3);
		$throttler->increment('foo');
		$this->assertSame(true, $throttler->isAtLimit('foo'));

		Cache::shouldReceive('increment')->once()->andReturn(4);
		$throttler->increment('foo');
		$this->assertSame(true, $throttler->isAtLimit('foo'));
	}
}

class FooThrottler extends BaseThrottler
{

}