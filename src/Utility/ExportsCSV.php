<?php

namespace Fuzz\ApiServer\Utility;

trait ExportsCSV
{
	/**
	 * Build the CSV string and support dot nested column mappings
	 *
	 * @param array $data
	 * @param array $column_mappings
	 * @return string
	 */
	public function exportCSV(array $data, array $column_mappings): string
	{
		$output = $this->buildCSVHeaders($column_mappings) . PHP_EOL;
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

					$row_data[] = '"' . str_replace('"', '""', $location) . '"';
				} else {
					$row_data[] = '"' . str_replace('"', '""', $row[$header]) . '"';
				}
			}

			$output .= implode(',', $row_data) . PHP_EOL;
		}

		return rtrim($output, "\n");
	}

	/**
	 * Build the first row of a CSV
	 *
	 * @param array $column_mappings
	 * @return string
	 */
	public function buildCSVHeaders($column_mappings): string
	{
		return implode(',', $column_mappings);
	}
}
