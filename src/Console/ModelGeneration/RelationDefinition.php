<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;


/**
 * A RelationDefinition holds the information needed for generating a relationship method on a model.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class RelationDefinition
{
	/**
	 * The relationship class name. This identifies the type of relationship it is.
	 *
	 * @var string - Can be BelongsTo, BelongsToMany, HasMany
	 */
	protected $relationship;

	/**
	 * The model this relation is for.
	 *
	 * @var string - Will be the model class as a string.
	 */
	protected $relatedTo;

	/**
	 * The relationship name. This is the relationship identifier.
	 * This will become the relations function name.
	 *
	 * @var string - The function name for the relation.
	 */
	protected $name;

	/**
	 * These are the extra parameters that are unique to each relationship.
	 *
	 * @var array - They are in the order to which they will be applied to the relationship function.
	 */
	protected $params;

	/**
	 * RelationDefinition constructor.
	 *
	 * @param string $name         - The relationship name. We will use this to check if the function is unique and should be generated.
	 * @param string $relationship - The relationship type. We only support three currently.
	 * @param string $relatedTo    - The model this relationship will connect to.
	 * @param array  ...$args      - The extra args, in the order they should be passed to the relationship function.
	 */
	public function __construct(string $name, string $relationship, string $relatedTo, ...$args)
	{
		$this->name         = $name;
		$this->relationship = $relationship;
		$this->relatedTo    = $relatedTo;
		$this->params       = $args;
	}

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the params.
	 *
	 * @return array
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * Get the relationship.
	 *
	 * @return string
	 */
	public function getRelationship(): string
	{
		return $this->relationship;
	}

	/**
	 * Get the related to model.
	 *
	 * @return string
	 */
	public function getRelatedTo(): string
	{
		return $this->relatedTo;
	}

	/**
	 * The class basename for the related to model.
	 *
	 * @return string
	 */
	public function getRelatedToBaseName(): string
	{
		return class_basename($this->relatedTo);
	}

	/**
	 * Return a new relation stub with a copy of this relation definition.
	 *
	 * @return RelationStub
	 */
	public function toStub(): RelationStub
	{
		return new RelationStub(clone $this);
	}
}
