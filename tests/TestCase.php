<?php

namespace Fuzz\ApiServer\Tests;

use Mockery;
use PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase
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
