<?php

namespace Fuzz\ApiServer\EntityCache;

use Illuminate\Support\Facades\Cache;

/**
 * Class EntityCache
 *
 * EntityCache is an abstraction around the decision making and implementation of a cache for arbitrary entities. The
 * entity is stored with PHP's serialize() and unserialize()'d before it is returned to the user of this class.
 *
 * Usage:
 *
 * ```
 * $menu_cache = new EntityCache(CacheConstants::menuCache($org, $store_location));
 *
 * if ($menu_cache->isCached()) {
 *        $cached_data = $menu_cache->read();
 *
 *        // build response
 *
 *        $menu_cache->applyCacheControlHeaders($response);
 *
 *        return $response;
 * }
 *
 * // Do work
 * $menu_cache->store($arrayed_menu, 5);
 * ```
 *
 * @package Fuzz\Utility
 */
class EntityCache
{
	/**
	 * Is this entity already cached?
	 *
	 * @var bool
	 */
	protected $is_cached = false;

	/**
	 * Cache key storage
	 *
	 * @var string
	 */
	protected $cache_key;

	/**
	 * Entity storage in serialized form
	 *
	 * @var string|null
	 */
	protected $cached_response_serialized;

	/**
	 * EntityCache constructor.
	 *
	 * @param string $key
	 */
	public function __construct(string $key)
	{
		$this->cache_key = $key;
	}

	/**
	 * Store the entity in the cache
	 *
	 * @param mixed $entity
	 * @param int   $cache_ttl_min
	 */
	public function store($entity, int $cache_ttl_min = 5)
	{
		$this->cached_response_serialized = serialize($entity);

		Cache::put($this->cache_key, $this->cached_response_serialized, $cache_ttl_min);
	}

	/**
	 * Determine if the entity is already cached
	 *
	 * @return bool
	 */
	public function isCached(): bool
	{
		return ! is_null($this->read());
	}

	/**
	 * Read the entity from the cache and return its unserialized form or null if not cached
	 *
	 * @return mixed|null
	 */
	public function read()
	{
		if (! is_null($this->cached_response_serialized)) {
			return unserialize($this->cached_response_serialized);
		}

		$this->cached_response_serialized = Cache::get($this->cache_key);

		return is_null($this->cached_response_serialized) ? null : unserialize($this->cached_response_serialized);
	}

	/**
	 * Get TTL for the entity
	 *
	 * @return int
	 */
	public function ttl(): int
	{
		$prefix = config('cache.prefix');

		// When calling TTL (Redis-specific) through the cache connection, Laravel does not prefix the cache
		// prefix so we need to add it if the key does not contain it.
		if (! str_contains($this->cache_key, $prefix)) {
			$key = sprintf("%s:%s", $prefix, $this->cache_key);
		}

		return Cache::connection()->ttl($key);
	}
}