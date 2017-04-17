<?php

namespace Fuzz\ApiServer\Response;

use Carbon\Carbon;
use InvalidArgumentException;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use SimpleXMLElement;

/**
 * Class XLSResponder
 *
 *
 * @package Fuzz\ApiServer\Response
 */
class XLSResponder extends BaseResponder implements Responder
{
    /**
     * Content type for this response
     *
     * @const string
     */
    const CONTENT_TYPE = 'application/vnd.ms-excel; charset=UTF-8';

    /**
     * The excel file writer.
     *
     * @var LaravelExcelWriter|\PHPExcel
     */
    protected $excel;

    /**
     * Filename
     *
     * @var null|string
     */
    protected $filename;

    /**
     * Sheet name.
     *
     * @var null|string
     */
    protected $sheetname;

    /**
     * Creator
     *
     * @var null|string
     */
    protected $creator;

    /**
     * Company
     *
     * @var null|string
     */
    protected $company;

    /**
     * Description
     *
     * @var null|string
     */
    protected $description;

    /**
     * The header row.
     *
     * @var array
     */
    protected $sheetHeaders;

    /**
     * File format.
     *
     * @var string
     */
    protected $format = 'xls';

    /**
     * To compare null using strict operator.
     *
     * @var bool
     */
    protected $strictNullComparison = true;


    /**
     * XLSResponder constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->filename = null;
        $this->sheetname = null;
        $this->creator = null;
        $this->company = null;
        $this->description = null;
        $this->excel = app('excel')->create('export');
    }

    /**
     * Set the excel writer.
     *
     * @param LaravelExcelWriter $excel
     *
     * @return $this
     */
    public function setExcel(LaravelExcelWriter $excel)
    {
        $this->excel = $excel;

        return $this;
    }

    /**
     * Get the excel writer.
     *
     * @return LaravelExcelWriter
     */
    public function getExcel(): LaravelExcelWriter
    {
        return $this->excel;
    }

    /**
     * Set the filename.
     *
     * @param string $filename
     *
     * @return $this
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get the filename.
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Set the sheet name.
     *
     * @param string $sheetname
     *
     * @return $this
     */
    public function setSheetname(string $sheetname)
    {
        $this->sheetname = $sheetname;

        return $this;
    }

    /**
     * Get the sheet name.
     *
     * @return string
     */
    public function getSheetName(): string
    {
        return $this->sheetname;
    }

    /**
     * Set the creator of the file.
     *
     * @param $creator
     *
     * @return $this
     */
    public function setCreator(string $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get the file creator.
     *
     * @return string
     */
    public function getCreator(): string
    {
        return $this->creator;
    }

    /**
     * Set the company name for the file.
     *
     * @param string $company
     *
     * @return $this
     */
    public function setCompany(string $company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the files company name.
     *
     * @return string
     */
    public function getCompany(): string
    {
        return $this->company;
    }

    /**
     * Set the sheet headers.
     *
     * @param $sheetHeaders
     *
     * @return $this
     */
    public function setSheetHeaders(array $sheetHeaders)
    {
        $this->sheetHeaders = $sheetHeaders;

        return $this;
    }

    /**
     * Get the sheet headers.
     *
     * @return array
     */
    public function getSheetHeaders(): array
    {
        return $this->sheetHeaders;
    }

    /**
     * Set the description.
     *
     * @param $description
     *
     * @return $this
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set the strict null comparision value.
     *
     * @param $strictNullComparision
     *
     * @return $this
     */
    public function setStrictNullComparison(bool $strictNullComparision)
    {
        $this->strictNullComparison = $strictNullComparision;

        return $this;
    }

    /**
     * Get if strict null comparision value.
     *
     * @return bool
     */
    public function getStrictNullComparision(): bool
    {
        return $this->strictNullComparison;
    }

    /**
     * Set data for the response.
     *
     * @param array $data
     *
     * @return \Fuzz\ApiServer\Response\Responder
     */
    public function setData($data): Responder
    {
        $this->excel->setFileName($this->filename ?? $this->excel->getFileName());
        $this->excel->setCreator($this->creator ?? $this->excel->getProperties()->getCreator());
        $this->excel->setCompany($this->company ?? $this->excel->getProperties()->getCompany());
        $this->excel->setDescription($this->description ?? $this->excel->getProperties()->getDescription());

        $response_data = $this->excel->sheet($this->sheetname ?? $this->excel->getFileName(), function ($sheet) use ($data) {

            // If headers are set for the sheet, we
            if ($this->sheetHeaders) {
                $sheet->fromArray($data, null, 'A1', $this->strictNullComparison, false);
                $sheet->prependRow(1, $this->sheetHeaders);
            } else {
                $sheet->fromArray($data, null, 'A1', $this->strictNullComparison, true);
            }

        })->string($this->format);

        if (!$response_data) {
            throw new InvalidArgumentException('Could not serialize data to XML.');
        }

        $this->response->setContent($response_data);
        $this->addHeaders([
            'Content-Type' => static::CONTENT_TYPE,
            'Content-Disposition' => 'attachment; filename="' . $this->excel->filename . '.' . $this->excel->ext . '"',
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT', // Date in the past
            'Last-Modified' => Carbon::now()->format('D, d M Y H:i:s'),
            'Cache-Control' => 'cache, must-revalidate',
            'Pragma' => 'public',
        ]);

        return $this;
    }
}
