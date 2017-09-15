<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeResponse;

class CompositeGuzzleResponse implements CompositeResponse
{
	/**
	 * @var string
	 */
	private $content;

	private $status_code;

	private $headers = [];

	public function getContent(): string
	{
		return $this->content;
	}

	public function setContent(string $content): CompositeResponse
	{
		$this->content = $content;

		return $this;
	}

	public function getStatusCode(): int
	{
		return $this->status_code;
	}

	public function setStatusCode(int $status_code): CompositeResponse
	{
		$this->status_code = $status_code;

		return $this;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setHeaders(array $headers): CompositeResponse
	{
		$this->headers = $headers;

		return $this;
	}
}
