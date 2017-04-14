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
		// Guess columns based on array keys
		$columns = array_keys($data[0]);
		$filename = Carbon::now()->toDateTimeString() . '_export.csv';

		$this->response->setContent($this->exportCSV($data, $columns));

		$this->addHeaders([
			'Content-Type'        => self::CONTENT_TYPE,
			'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
		]);

		return $this;
	}
}
