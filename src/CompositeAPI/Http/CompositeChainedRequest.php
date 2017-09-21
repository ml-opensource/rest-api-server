<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;

/**
 * Class CompositeChainedRequest
 *
 * CompositeChainedRequest wraps a request which is a link in a request chain. The chain is implemented as
 * a linked list.
 *
 * @package Fuzz\ApiServer\CompositeAPI\Http
 */
class CompositeChainedRequest extends CompositeGuzzleRequest implements CompositeRequest
{
	/**
	 * The next request in the chain
	 *
	 * @var \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	private $next;

	/**
	 * The reference ID for this request
	 *
	 * @var string
	 */
	private $reference_id;

	/**
	 * Set the next request in the chain
	 *
	 * @param \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest $next
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest
	 */
	public function setNext(CompositeRequest $next): self
	{
		$this->next = $next;

		return $this;
	}

	/**
	 * Get the next request in the chain
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	public function next()
	{
		return $this->next;
	}

	/**
	 * Determine if the request has a next request
	 *
	 * @return bool
	 */
	public function hasNext(): bool
	{
		return ! is_null($this->next);
	}

	/**
	 * Set the reference ID for the request
	 *
	 * @param string $id
	 *
	 * @return \Fuzz\ApiServer\CompositeAPI\Http\CompositeChainedRequest
	 */
	public function setReferenceId(string $id): CompositeChainedRequest
	{
		$this->reference_id = $id;

		return $this;
	}

	/**
	 * Access the reference ID of the request
	 *
	 * @return string
	 */
	public function referenceId(): string
	{
		if (is_null($this->reference_id)) {
			throw new \LogicException('No reference ID was set for the request.');
		}

		return $this->reference_id;
	}
}
