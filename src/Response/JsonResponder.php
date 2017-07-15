<?php

namespace Fuzz\ApiServer\Response;

use Illuminate\Http\JsonResponse;

class JsonResponder extends BaseResponder implements Responder
{
	/**
	 * JsonResponder constructor.
	 */
	public function __construct()
	{
		$this->response = new JsonResponse;
	}

	/**
	 * Set data for the response
	 *
	 * @param array $data
	 *
	 * @return \Fuzz\ApiServer\Response\Responder
	 */
	public function setData($data): Responder
	{
		$this->response->setData($data);

		return $this;
	}
}
