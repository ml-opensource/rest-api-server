<?php

namespace Fuzz\ApiServer\CompositeAPI\Http;

use Fuzz\ApiServer\CompositeAPI\Contracts\CompositeRequest;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

class CompositeGuzzleRequest implements CompositeRequest
{
	/**
	 * @var string
	 */
	private $content;

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * @var array
	 */
	private $headers;

	/**
	 * @var string
	 */
	private $method = self::HTTP_GET;

	public function setContent(string $content): CompositeRequest
	{
		$this->content = $content;

		return $this;
	}

	public function setHeaders(array $headers): CompositeRequest
	{
		$this->headers = $headers;

		return $this;
	}

	public function hasContent(): bool
	{
		return ! is_null($this->content);
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setMethod(string $method): CompositeRequest
	{
		$this->method = $method;

		return $this;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function setURI(string $uri): CompositeRequest
	{
		$this->uri = $uri;

		return $this;
	}

	public function getURI(): string
	{
		return $this->uri;
	}
}
