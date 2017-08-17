<?php

namespace Fuzz\ApiServer\Tests\Console\ModelGeneration;


use Fuzz\ApiServer\Console\ModelGeneration\RelationDefinition;
use Fuzz\ApiServer\Console\ModelGeneration\RelationStub;
use Fuzz\ApiServer\Tests\AppTestCase;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationStubTest extends AppTestCase
{

	public function testCanCreate()
	{
		/** @var \Mockery|RelationDefinition $mockDefinition */
		$definition   = new RelationDefinition($name = 'blahs', $relationship = HasMany::class, $relatedTo = 'Fuzz\BlahModel', $pone = 'pone', $ptwo = 'ptwo', $pthree = 'pthree');
		$relationStub = new RelationStub($definition);

		$this->assertInstanceOf(RelationStub::class, $relationStub);
		$this->assertDirectoryIsReadable($relationStub->getStubDir());
		$this->assertFileIsReadable($relationStub->getStubPath());
		$this->assertStringStartsWith('use', $relationStub->getUseStatement());
		$this->assertStringEndsWith(';', $relationStub->getUseStatement());
		$this->assertStringStartsWith('function', $relationStub->getFunctionDeclaration());
		$this->assertStringEndsWith('()', $relationStub->getFunctionDeclaration());
		$this->assertEquals(class_basename(HasMany::class), $relationStub->getStubName());
		$this->assertInternalType('string', (string) $relationStub);
	}

}