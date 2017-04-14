<?php

namespace Fuzz\ApiServer\Response;

use Symfony\Component\HttpFoundation\Response;

class BaseResponder implements Responder
{
	/**
	 * Response storage
	 *
	 * @var \Symfony\Component\HttpFoundation\Response
	 */
	protected $response;

	/**
	 * BaseResponder constructor.
	 */
	public function __construct()
	{
		$this->response = new Response;
	}

	/**
	 * Set the status code for the response
	 *
	 * @param int $status_code
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function setStatusCode(int $status_code): Responder
	{
		$this->response->setStatusCode($status_code);

		return $this;
	}

	/**
	 * Set headers for the response
	 *
	 * @param array $headers
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function addHeaders(array $headers): Responder
	{
		$this->response->headers->add($headers);

		return $this;
	}

	/**
	 * Create the response object
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse(): Response
	{
		return $this->response;
	}

	/**
	 * Set data for the response
	 *
	 * @param string|array $data
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function setData($data): Responder
	{
		$this->response->setContent($data);

		return $this;
	}
}
