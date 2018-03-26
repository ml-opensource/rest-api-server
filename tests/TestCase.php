<?php

namespace Fuzz\ApiServer\Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
	/**
	 * Tear down tests
	 *
	 * @throws \Throwable
	 */
	public function tearDown()
	{
		if (class_exists('Mockery')) {
			parent::verifyMockObjects();

			if ($container = Mockery::getContainer()) {
				$this->addToAssertionCount($container->mockery_getExpectationCount());
			}

			Mockery::close();
		}

		parent::tearDown();
	}
}
