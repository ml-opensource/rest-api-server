<?php

namespace Fuzz\ApiServer\EntityCache;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CacheDirectiveEntityCache
 *
 * EntityCache is an abstraction around the decision making and implementation of a cache for arbitrary entities. The
 * entity is stored with PHP's serialize() and unserialize()'d before it is returned to the user of this class.
 *
 * Usage:
 *
 * ```
 * $menu_cache = new CacheDirectiveEntityCache($request, CacheConstants::menuCache($org, $store_location));
 *
 * if ($menu_cache->canGetFromCache() && $menu_cache->isCached()) {
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
class CacheDirectiveEntityCache extends EntityCache
{
	/**
	 * Header name for response cache
	 *
	 * @const string
	 */
	const HEADER_NAME = 'X-Entity-Cache';

	/**
	 * Should this request revalidate?
	 *
	 * @var bool
	 */
	protected $must_revalidate = false;

	/**
	 * Can this response be stored?
	 *
	 * @var bool
	 */
	protected $no_store = false;

	/**
	 * Should this skip the cache?
	 *
	 * @var bool
	 */
	protected $no_cache = false;

	/**
	 * Request storage
	 *
	 * @var \Illuminate\Http\Request
	 */
	private $request;

	/**
	 * CacheDirectiveEntityCache constructor.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param string                   $key
	 */
	public function __construct(?Request $request, string $key)
	{
		parent::__construct($key);

		$this->request = $request;

		$this->loadCacheControlHeaders();
	}

	/**
	 * Store the entity in the cache
	 *
	 * @param mixed $entity
	 * @param int   $cache_ttl_seconds
	 */
	public function store($entity, int $cache_ttl_seconds = 300)
	{
		// Don't store when no-store
		if (! $this->canStore()) {
			return;
		}

		parent::store($entity, $cache_ttl_seconds);
	}

	/**
	 * Check to see if we can return a cached value.
	 *
	 * @return bool
	 */
	public function canGetFromCache(): bool
	{
		return ! $this->no_cache && ! $this->must_revalidate;
	}

	/**
	 * Apply cache control headers to the response
	 *
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 */
	public function applyCacheControlHeaders(Response $response)
	{
		$ttl = $this->ttl();

		$response->headers->set('Cache-Control', "max-age=$ttl, private");
		$response->setEtag($this->getEtag());
	}

	/**
	 * Create an ETag for this entity
	 *
	 * @return string
	 */
	public function getEtag(): string
	{
		return md5($this->cached_response_serialized);
	}

	/**
	 * Check to see if this can be stored
	 *
	 * @return bool
	 */
	public function canStore()
	{
		return ! $this->no_store;
	}

	/**
	 * Load cache control headers from the request and set them on the response.
	 */
	protected function loadCacheControlHeaders()
	{
		$cache_control = $this->request->header('cache-control');

		if ($cache_control || $cache_control === '') {
			$cache_directives = array_map('trim', explode(',', $cache_control));

			foreach ($cache_directives as $directive) {
				switch ($directive) {
					case 'private':
						$this->no_store = true;
						$this->no_cache = true;

						break;
					case 'no-store':
						$this->no_store = true;

						break;
					case 'no-cache':
						$this->no_cache = true;

						break;
					case 'must-revalidate':
						$this->must_revalidate = true;

						break;
					default:
						break;
				}
			}
		}
	}
}
