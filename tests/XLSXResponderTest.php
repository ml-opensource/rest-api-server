<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Response\XLSXResponder;

class XLSXResponderTest extends AppTestCase
{
    public function testNewXLSResponder()
    {
        $filename = 'blah';
        $sheetname = 'blahsheet';
        $creator = 'some blah creator';
        $company = 'some blah company';
        $description = 'some blah description';
        $sheetHeaders = ['one', 'two', 'three'];
        $strictNullComparison = false;

        $responder = new XLSXResponder();

        $responder->setFilename($filename)
            ->setSheetHeaders($sheetHeaders)
            ->setSheetname($sheetname)
            ->setCreator($creator)
            ->setCompany($company)
            ->setDescription($description)
            ->setStrictNullComparison($strictNullComparison);

        $this->assertSame($filename, $responder->getFilename());
        $this->assertSame($sheetname, $responder->getSheetname());
        $this->assertSame($creator, $responder->getCreator());
        $this->assertSame($company, $responder->getCompany());
        $this->assertSame($description, $responder->getDescription());
        $this->assertSame($sheetHeaders, $responder->getSheetHeaders());
        $this->assertSame($strictNullComparison, $responder->getStrictNullComparision());
    }

    public function testItCanSetData()
    {
        $responder = new XLSXResponder();
        $responder->setData(['key' => 'data']);

        $this->assertSame(XLSXResponder::CONTENT_TYPE, $responder->getResponse()->headers->get('Content-Type'));
        $this->assertSame('attachment; filename="export.xlsx"', $responder->getResponse()->headers->get('Content-Disposition'));
        $this->assertSame('Mon, 26 Jul 1997 05:00:00 GMT', $responder->getResponse()->headers->get('Expires'));
    }
}
