<?php

namespace Tests\Utility;

use Fuzz\ApiServer\Tests\AppTestCase;
use Fuzz\ApiServer\Utility\UUID;

class UUIDTest extends AppTestCase
{
	public function testItCanGenerateUUID()
	{
		$this->assertSame(1, preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', UUID::generate()));
		$this->assertSame(1, preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', UUID::generate()));
		$this->assertSame(1, preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', UUID::generate()));
		$this->assertSame(1, preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', UUID::generate()));
		$this->assertSame(1, preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', UUID::generate()));
	}
}
