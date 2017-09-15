<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;

class CompositeChainedRequest extends CompositeGuzzleRequest implements CompositeRequest
{
	/**
	 * @var \Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest
	 */
	private $next;

	private $reference_id;

	// @todo add reference IDs and replacement

	public function setNext(CompositeRequest $next): self
	{
		$this->next = $next;

		return $this;
	}

	public function next()
	{
		return $this->next;
	}

	public function setReferenceId(string $id): CompositeChainedRequest
	{
		$this->reference_id = $id;

		return $this;
	}

	public function referenceId(): string
	{
		return $this->reference_id;
	}

	public function hasNext(): bool
	{
		return ! is_null($this->next);
	}
}
