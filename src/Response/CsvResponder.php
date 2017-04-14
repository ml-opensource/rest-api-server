<?php

namespace Fuzz\ApiServer\Response;

use Carbon\Carbon;
use Fuzz\ApiServer\Utility\ExportsCSV;
use Illuminate\Http\Response;

class CsvResponder extends BaseResponder implements Responder
{
	use ExportsCSV;

	/**
	 * Content type for CSVs
	 *
	 * @const string
	 */
	const CONTENT_TYPE = 'text/csv';

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
		// Use the data wrapper if it exists
		$data = isset($data['data']) ? $data['data'] : $data;

		// If this is not a collection of items (numerically indexed array), coerce it to a collection of one
		$data = isset($data[0]) ? $data : [$data];

		// Guess columns based on array keys
		$columns  = array_keys($data[0]);
		$filename = Carbon::now()->toDateTimeString() . '_export.csv';

		$this->response->setContent($this->exportCSV($data, $columns));

		$this->addHeaders([
			'Content-Type'        => self::CONTENT_TYPE,
			'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
		]);

		return $this;
	}
}
