<?php

namespace Fuzz\ApiServer\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResponseFactory
 *
 * The ResponseFactory is responsible for generating a response in a specific format based on some data
 *
 * @package Fuzz\ApiServer\Routing
 */
class ResponseFactory
{
	/**
	 * List of configured responders
	 *
	 * @var array
	 */
	private $responders;

	/**
	 * @var \Fuzz\ApiServer\Response\Responder
	 */
	private $responder;

	/**
	 * ResponseFactory constructor.
	 *
	 * @param array $responders
	 */
	public function __construct(array $responders)
	{
		$this->responders = $responders;
	}

	/**
	 * Set a response format
	 *
	 * @param string $format
	 *
	 * @return \Fuzz\ApiServer\Response\ResponseFactory
	 */
	public function setResponseFormat(string $format): ResponseFactory
	{
		if (! isset($this->responders[$format])) {
			throw new \InvalidArgumentException("$format is not a valid response type.");
		}

		$this->responder = new $this->responders[$format];

		return $this;
	}

	/**
	 * Generate a response
	 *
	 * @param array  $data
	 * @param int    $status_code
	 * @param array  $headers
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function makeResponse(array $data, int $status_code, array $headers): Response
	{
		if (is_null($this->responder)) {
			throw new \LogicException('No responder has been set.');
		}

		$this->responder->setData($data);
		$this->responder->setStatusCode($status_code);
		$this->responder->addHeaders($headers);

		return $this->responder->getResponse();
	}
}
