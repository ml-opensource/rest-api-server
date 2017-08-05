<?php

namespace Fuzz\ApiServer\Tests\Console\ModelGeneration;


use Fuzz\ApiServer\Console\ModelGeneration\RelationDefinition;
use Fuzz\ApiServer\Console\ModelGeneration\RelationStub;
use Fuzz\ApiServer\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationDefinitionTest extends TestCase
{
	public function testRelationDefinitionCanStart()
	{
		$definition = new RelationDefinition($name='blahs', $relationship=HasMany::class, $relatedTo='Fuzz\BlahModel', $pone='pone', $ptwo='ptwo', $pthree='pthree');

		$this->assertInstanceOf(RelationDefinition::class, $definition);
		$this->assertEquals($name, $definition->getName());
		$this->assertEquals($relationship, $definition->getRelationship());
		$this->assertEquals($relatedTo, $definition->getRelatedTo());
		$this->assertEquals([$pone, $ptwo, $pthree], $definition->getParams());
		$this->assertEquals('BlahModel', $definition->getRelatedToBaseName());
		$this->assertInstanceOf(RelationStub::class, $definition->toStub());
	}
}