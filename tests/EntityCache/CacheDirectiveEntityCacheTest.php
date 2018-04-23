<?php

namespace Tests\API\Utility;

use Fuzz\ApiServer\EntityCache\CacheDirectiveEntityCache;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Predis\Connection\Aggregate\RedisCluster;
use Symfony\Component\HttpFoundation\HeaderBag;

class CacheDirectiveEntityCacheTest extends AppTestCase
{
	public function testItCorrectlyLoadsRequestCacheControlHeader()
	{
		$req = Mockery::mock(Request::class);

		/**
		 * Both directive dictate no cache pull
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-cache, must-revalidate');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$this->assertFalse($ec->canGetFromCache());
		$this->assertTrue($ec->canStore());

		/**
		 * One directive dictates no cache pull
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-cache');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$this->assertFalse($ec->canGetFromCache());
		$this->assertTrue($ec->canStore());

		/**
		 * One directive dictates no cache pull
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('must-revalidate');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$this->assertFalse($ec->canGetFromCache());
		$this->assertTrue($ec->canStore());

		/**
		 * No directives dictate cache pull
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$this->assertTrue($ec->canGetFromCache());
		$this->assertTrue($ec->canStore());

		/**
		 * Can't store
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$this->assertTrue($ec->canGetFromCache());
		$this->assertFalse($ec->canStore());

		/**
		 * Can't store or pull
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-cache, no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$this->assertFalse($ec->canGetFromCache());
		$this->assertFalse($ec->canStore());
	}

	public function testItCanStoreEntityInCache()
	{
		$req = Mockery::mock(Request::class);

		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$entity = [
			'foo' => 'bar',
		];

		Cache::shouldReceive('put')->with('foo', serialize($entity), 5)->once();
		$ec->store($entity, 5);
	}

	public function testItDoesNotStoreInCacheIfNoStore()
	{
		$req = Mockery::mock(Request::class);

		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$entity = [
			'foo' => 'bar',
		];

		Cache::shouldReceive('put')->never();
		$ec->store($entity, 5);
	}

	public function testItCanDetermineIfIsCached()
	{
		$req = Mockery::mock(Request::class);

		/**
		 * Positive
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$entity = [
			'foo' => 'bar',
		];

		Cache::shouldReceive('get')->with('foo')->once()->andReturn(serialize($entity));
		$this->assertTrue($ec->isCached());

		/**
		 * Negative
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$entity = [
			'foo' => 'bar',
		];

		Cache::shouldReceive('get')->with('foo')->once()->andReturn(null);
		$this->assertFalse($ec->isCached());
	}

	public function testItCanReadFromCache()
	{
		$req = Mockery::mock(Request::class);

		/**
		 * Positive
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$entity = [
			'foo' => 'bar',
		];

		Cache::shouldReceive('get')->with('foo')->once()->andReturn(serialize($entity));
		$this->assertSame($entity, $ec->read());

		/**
		 * Negative
		 */
		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('no-store');

		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$entity = [
			'foo' => 'bar',
		];

		Cache::shouldReceive('get')->with('foo')->once()->andReturn(null);
		$this->assertNull($ec->read());
	}

	public function testItAppliesCacheControlHeadersToResponse()
	{
		$req = Mockery::mock(Request::class);

		$req->shouldReceive('header')->with('cache-control')->once()->andReturn('');
		$ec = new CacheDirectiveEntityCache($req, 'foo');

		$conn_mock = Mockery::mock(RedisCluster::class);

		Cache::shouldReceive('connection')->once()->andReturn($conn_mock);
		$conn_mock->shouldReceive('ttl')->with('laravel_cache:foo')->once()->andReturn(55);

		$response = Mockery::mock(Response::class);
		$headers = Mockery::mock(HeaderBag::class);
		$response->headers = $headers;

		$response->shouldReceive('setEtag')->with('d41d8cd98f00b204e9800998ecf8427e')->once();

		$headers->shouldReceive('set')->with('Cache-Control', 'max-age=55, private');

		$ec->applyCacheControlHeaders($response);
	}
}
