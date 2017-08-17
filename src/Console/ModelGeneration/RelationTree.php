<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;


/**
 * The RelationTree holds the mapping of models to their relationships, as derived by the @RelationMapper.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class RelationTree
{
	/**
	 * The storage for @ModelNode[]'s
	 *
	 * @var array
	 */
	protected $nodes = [];

	/**
	 * Gets the nodes.
	 *
	 * @return array
	 */
	public function nodes()
	{
		return $this->nodes;
	}

	/**
	 * Add a @ModelNode into the tree. It will only be added if the ModelNode
	 * does not already exist. If you want to replace a ModelNode @see replace()
	 *
	 * @param ModelNode $node
	 *
	 * @return $this
	 */
	public function add(ModelNode $node)
	{
		if (! $this->has($node)) {
			$this->nodes[$node->getName()] = $node;
		}

		return $this;
	}

	/**
	 * Will replace a ModelNode with a new one. If it does not exist, it will simply be added.
	 *
	 * @param ModelNode $node
	 *
	 * @return RelationTree
	 */
	public function replace(ModelNode $node): RelationTree
	{
		$this->nodes[$node->getName()] = $node;

		return $this;
	}

	/**
	 * Create a new ModelNode from a string and add it to the tree.
	 *
	 * @param string $model
	 *
	 * @return ModelNode
	 */
	public function createNode(string $model): ModelNode
	{
		$node = new ModelNode($model);
		$this->add($node);

		return $node;
	}

	/**
	 * Checks to see if the ModelNode exists in the tree.
	 *
	 * @param ModelNode $node
	 *
	 * @return bool
	 */
	public function has(ModelNode $node): bool
	{
		return $this->nodes[$node->getName()] ?? false;
	}

	/**
	 * Get a particular ModelNode from the tree.
	 *
	 * @param ModelNode $node
	 *
	 * @return ModelNode|null
	 */
	public function get(ModelNode $node)
	{
		return $this->nodes[$node->getName()] ?? null;
	}

	/**
	 * Find a ModelNode in the tree by name.
	 *
	 * @param string $model - The string name for the ModelNode.
	 *
	 * @return null|ModelNode
	 */
	public function find(string $model)
	{
		return $this->get(new ModelNode($model));
	}

	/**
	 * This will either find a ModelNode by it's name, or create a new node.
	 *
	 * @param string $model
	 *
	 * @return ModelNode
	 */
	public function model(string $model): ModelNode
	{
		return $this->find($model) ?? $this->createNode($model);
	}

	/**
	 * Pass it an array of names and it will return all the ModelNodes it was able to find.
	 *
	 * @param array $models
	 *
	 * @return ModelNode[]
	 */
	public function only(array $models = null): array
	{
		return array_intersect_key($this->nodes(), array_flip($models));
	}
}
