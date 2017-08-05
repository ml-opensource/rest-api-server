<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;


/**
 * A ModelNode represents a model within the RelationTree. It stores it's own RelationDefinitions.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class ModelNode
{
	/**
	 * The nodes name. This will be the model's class.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Storage for @see RelationDefinition[]
	 *
	 * @var array
	 */
	protected $relations = [];

	/**
	 * ModelNode constructor.
	 *
	 * @param string $name - This should be the fully qualified class name.
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
	}

	/**
	 * Get the nodes name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the nodes relations
	 *
	 * @return RelationDefinition[]
	 */
	public function relations(): array
	{
		return $this->relations;
	}

	/**
	 * Adds a relation to the model node.
	 *
	 * @param array ...$args - The args to pass to the RelationDefinition.
	 *
	 * @return ModelNode
	 */
	public function relateTo(...$args): ModelNode
	{
		$definition = new RelationDefinition(...$args);

		$this->relations[$definition->getName()] = $definition;

		return $this;
	}
}
