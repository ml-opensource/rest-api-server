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
		$this->toXML($xml, $data);

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

	/**
	 * Parse a PHP array to XML
	 *
	 * @param \SimpleXMLElement $xml
	 * @param array             $data
	 */
	private function toXML(SimpleXMLElement $xml, array $data)
	{
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$new_object = $xml->addChild($key);
				$this->toXML($new_object, $value);
			} else {
				$xml->addChild($key, $value);
			}
		}
	}
}
