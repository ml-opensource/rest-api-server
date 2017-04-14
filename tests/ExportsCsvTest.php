<?php

namespace Fuzz\ApiServer\Tests;

use Fuzz\ApiServer\Utility\ExportsCSV;

class ExportsCsvTest extends TestCase
{
	private $trait;

	public function setUp()
	{
		parent::setUp();

		$this->trait = new class
		{
			use ExportsCSV;
		};
	}

	public function testItBuildsCsvHeaders()
	{
		$this->assertSame('foo,bar,baz', $this->trait->buildCSVHeaders(['foo', 'bar', 'baz']));
	}

	public function testItBuildsCsv()
	{
		$data = [
			[
				'foo' => 1,
				'bar' => 'baz',
			],
			[
				'foo' => 10,
				'bar' => 'bat',
			],
			[
				'foo' => 100,
				'bar' => 'bag',
			],
		];

		$this->assertSame("foo,bar\n\"1\",\"baz\"\n\"10\",\"bat\"\n\"100\",\"bag\"", $this->trait->exportCSV($data, ['foo', 'bar',]));
	}
}
