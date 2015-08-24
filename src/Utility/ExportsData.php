<?php

namespace Fuzz\ApiServer\Utility;

trait ExportsData
{
	/**
	 * Map export content types to supported formats
	 *
	 * @var array
	 */
	protected $content_types = [
		'csv' => 'text/csv'
	];

	/**
	 * Export data as a file
	 *
	 * @param string      $format
	 * @param array       $data
	 * @param array       $column_mappings
	 * @param null|string $filename
	 * @return mixed
	 */
	public function export($format, array $data, array $column_mappings, $filename = null)
	{
		$method = camel_case('build_' . $format);
		$output = $this->$method($data, $column_mappings);

		$headers = [
			'Content-Type'        => $this->content_types[$format],
			'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
		];

		return $this->getResponder()->send(rtrim($output, "\n"), 200, $headers, false);
	}

	/**
	 * Build the CSV string and support dot nested column mappings
	 *
	 * @param array $data
	 * @param array $column_mappings
	 * @return string
	 */
	protected function buildCsv(array $data, array $column_mappings)
	{
		$output = $this->buildCsvHeaders($column_mappings);
		foreach ($data as $row) {
			$row_data = [];

			// Map row data to columns
			foreach ($column_mappings as $column => $header) {
				$path = explode('.', $column);

				// Accept dot nested notations
				if (count($path) > 1) {
					$location = $row;

					foreach ($path as $step) {
						$location = $location[$step];
					}

					$row_data[$header] = $location;
				} else {
					$row_data[$header] = $row[$column];
				}
			}

			$output .= implode(',', $row_data) . PHP_EOL;
		}

		return $output;
	}

	/**
	 * Build the first row of a CSV
	 *
	 * @param array $column_mappings
	 * @return string
	 */
	protected function buildCsvHeaders($column_mappings)
	{
		return implode(',', $column_mappings) . PHP_EOL;
	}
}
