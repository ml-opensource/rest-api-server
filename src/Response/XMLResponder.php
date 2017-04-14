<?php

namespace Fuzz\ApiServer\Response;

use Illuminate\Http\Response;
use InvalidArgumentException;
use SimpleXMLElement;

class XMLResponder extends BaseResponder implements Responder
{
	/**
	 * Content type for this response
	 *
	 * @const string
	 */
	const CONTENT_TYPE = 'application/xml';

	/**
	 * JsonResponder constructor.
	 */
	public function __construct()
	{
		$this->response = new Response;
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
		$xml = new SimpleXMLElement('<root/>');
		array_walk_recursive($data, [$xml, 'addChild']);

		$response_data = $xml->asXML();

		if (! $response_data) {
			throw new InvalidArgumentException('Could not serialize data to XML.');
		}

		$this->response->setContent(rtrim($response_data, "\n"));
		$this->addHeaders([
			'Content-Type' => self::CONTENT_TYPE,
		]);

		return $this;
	}
}
