<?php

namespace Fuzz\ApiServer\Throttling\Commands;

use Predis\Command\ScriptCommand;

class TokenBucketCommand extends ScriptCommand
{
	/**
	 * Specifies the number of arguments that should be considered as keys.
	 *
	 * The default behaviour for the base class is to return 0 to indicate that
	 * all the elements of the arguments array should be considered as keys, but
	 * subclasses can enforce a static number of keys.
	 *
	 * @return int
	 */
	protected function getKeysCount()
	{
		return 2; // Tokens and Timestamps
	}

	/**
	 * Gets the body of a Lua script.
	 *
	 * A token bucket
	 *
	 * @see https://gist.github.com/ptarjan/e38f45f2dfe601419ca3af937fff574d
	 *
	 * @return string
	 */
	public function getScript()
	{
		// The gist of TokenBucket is that in Redis we store 2 keys, one for the # of tokens available for our key
		// and one for the last time the bucket for this key was refreshed. Each time an action occurs we run:
		// 1. Is this a new key? If so, set number of tokens to max and last refreshed to 0
		// 2. Is this bucket at or beyond the time period to refresh? If so, refill it with the max
		// 3. Decrement the number of tokens by $requested (usually 1)
		// 4. Return allowed state and remaining tokens
		return <<<LUA
local tokens_key = KEYS[1]
local timestamp_key = KEYS[2]

local rate = tonumber(ARGV[1])
local capacity = tonumber(ARGV[2])
local now = tonumber(ARGV[3])
local requested = tonumber(ARGV[4])

local fill_time = capacity/rate
local ttl = math.floor(fill_time*2)

local last_tokens = tonumber(redis.call("get", tokens_key))
if last_tokens == nil then
  last_tokens = capacity
end

local last_refreshed = tonumber(redis.call("get", timestamp_key))
if last_refreshed == nil then
  last_refreshed = 0
end

local delta = math.max(0, now-last_refreshed)
local filled_tokens = math.min(capacity, last_tokens+(delta*rate))
local allowed = filled_tokens >= requested
local new_tokens = filled_tokens
if allowed then
  new_tokens = filled_tokens - requested
end

redis.call("setex", tokens_key, ttl, new_tokens)
redis.call("setex", timestamp_key, ttl, now)

return { allowed, new_tokens }

LUA;

	}
}