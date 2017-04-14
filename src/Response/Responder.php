<?php

namespace Fuzz\ApiServer\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Interface Responder
 *
 * A responder accepts data in the form of an array and outputs a response
 *
 * @package Fuzz\ApiServer\Response
 */
interface Responder
{
	/**
	 * Set data for the response
	 *
	 * @param string|array $data
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function setData($data): Responder;

	/**
	 * Set the status code for the response
	 *
	 * @param int $status_code
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function setStatusCode(int $status_code): Responder;

	/**
	 * Set headers for the response
	 *
	 * @param array $headers
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function addHeaders(array $headers): Responder;

	/**
	 * Create the response object
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse(): Response;
}
