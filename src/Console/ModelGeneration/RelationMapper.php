<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;


/**
 * The RelationMapper is responsible for traversing the database with a schema manager and building a complete RelationTree.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class RelationMapper
{
	/**
	 * Storage for models.
	 *
	 * @var array
	 */
	protected $models;

	/**
	 * The SchemaManager for the database.
	 *
	 * @var AbstractSchemaManager
	 */
	protected $schemaManager;

	/**
	 * A collection that maps Tables to their corresponding Models.
	 *
	 * @var Collection
	 */
	protected $tableMap;

	/**
	 * The RelationTree.
	 *
	 * @var RelationTree
	 */
	protected $relationTree;

	/**
	 * RelationMapper constructor.
	 *
	 * @param AbstractSchemaManager $schemaManager
	 * @param array                 $models
	 * @param RelationTree|null     $relationTree
	 */
	public function __construct(AbstractSchemaManager $schemaManager, array $models, RelationTree $relationTree = null)
	{
		$this->schemaManager = $schemaManager;
		$this->models        = $models;
		$this->relationTree  = $relationTree ?? new RelationTree();
	}

	/**
	 * Runs through the database building the relation tree.
	 *
	 * @return RelationTree
	 */
	public function map()
	{
		$this->tableMap = $this->getTableToModelCollection($this->models);

		foreach ($this->schemaManager->listTables() as $table) {
			$this->addRelationsFor($table, $this->relationTree);
		}

		return $this->relationTree;
	}

	/**
	 * Adds relations for a particular table.
	 *
	 * @param Table        $table
	 * @param RelationTree $relationTree
	 */
	public function addRelationsFor(Table $table, RelationTree $relationTree)
	{
		if ($this->hasManyToManyRelations($table)) {
			$this->addManyToManyRelations($relationTree, $table);
		}

		if ($this->hasOneToManyRelations($table)) {
			$this->addOneToManyRelations($relationTree, $table);
		}
	}

	/**
	 * Loops through the foreign keys on a table and adds both sides of many to many relations to the tree.
	 *
	 * @param RelationTree $relationTree
	 * @param Table        $table
	 */
	public function addManyToManyRelations(RelationTree $relationTree, Table $table)
	{
		$fks = $table->getForeignKeys();

		// Loop through each foreign key.
		foreach ($table->getForeignKeys() as $fkOne) {

			// At each loop we will take out the foreignKey from our copy.
			unset($fks[$fkOne->getName()]);

			// Then we loop through all the remaining copied fks so we can make pairs.
			foreach ($fks as $fkTwo) {
				if ($this->tableMap->has($fkOne->getForeignTableName()) && $this->tableMap->has($fkTwo->getForeignTableName())) {
					$relationTree->model($this->belongsToManyModel($fkOne))
						->relateTo(...$this->belongsToManyDefinition($fkOne, $fkTwo));
					$relationTree->model($this->belongsToManyModel($fkTwo))
						->relateTo(...$this->belongsToManyDefinition($fkTwo, $fkOne));
				}
			}
		}
	}

	/**
	 * Loops through the foreign keys on a table and adds one to many, and many to one relations to the tree.
	 *
	 * @param RelationTree $relationTree
	 * @param Table        $table
	 */
	public function addOneToManyRelations(RelationTree $relationTree, Table $table)
	{
		foreach ($table->getForeignKeys() as $fk) {
			if ($this->tableMap->has($fk->getLocalTableName()) && $this->tableMap->has($fk->getForeignTableName())) {
				$relationTree->model($this->belongsToModel($fk))->relateTo(...$this->belongsToDefinition($fk));
				$relationTree->model($this->hasManyModel($fk))->relateTo(...$this->hasManyDefinition($fk));
			}
		}
	}

	/**
	 * Creates an array that represents a relationship definition for a BelongsTo.
	 *
	 * @param ForeignKeyConstraint $foreignKey
	 *
	 * @return array
	 */
	public function belongsToDefinition(ForeignKeyConstraint $foreignKey)
	{
		$relatedTo      = $this->hasManyModel($foreignKey);
		$foreignColumns = $foreignKey->getLocalColumns();
		$function       = $this->generateFunctionName($relatedTo, false);


		return [
			$function, BelongsTo::class, $relatedTo, reset($foreignColumns), (new $relatedTo())->getKeyName(),
			'__FUNCTION__',
		];
	}

	/**
	 * Creates an array that represents a relationship definition for a BelongsToMany.
	 *
	 * @param ForeignKeyConstraint $fkOne
	 * @param ForeignKeyConstraint $fkTwo
	 *
	 * @return array
	 */
	public function belongsToManyDefinition(ForeignKeyConstraint $fkOne, ForeignKeyConstraint $fkTwo)
	{
		$relatedTo      = $this->belongsToManyModel($fkTwo);
		$foreignColumns = $fkOne->getLocalColumns();
		$relatedColumns = $fkTwo->getLocalColumns();
		$function       = $this->generateFunctionName($relatedTo);

		return [
			$function, BelongsToMany::class, $relatedTo, $fkOne->getLocalTableName(), reset($foreignColumns),
			reset($relatedColumns), '__FUNCTION__',
		];
	}

	/**
	 * Creates an array that represents a relationship definition for a HasMany.
	 *
	 * @param ForeignKeyConstraint $foreignKey
	 *
	 * @return array
	 */
	public function hasManyDefinition(ForeignKeyConstraint $foreignKey): array
	{
		$relatedTo      = $this->belongsToModel($foreignKey);
		$foreignColumns = $foreignKey->getLocalColumns();
		$function       = $this->generateFunctionName($relatedTo);

		return [$function, HasMany::class, $relatedTo, reset($foreignColumns), (new $relatedTo())->getKeyName()];
	}

	/**
	 * Comes up with the function name that will be used for the relationship definition.
	 *
	 * @Note: This method has a one off fix for classes that have the name OAuth.
	 * The double capital causes the string snake mehtod to produce undesired results.
	 *
	 * @param string $relatedTo
	 * @param bool   $plural
	 *
	 * @return string
	 */
	public function generateFunctionName(string $relatedTo, $plural = true): string
	{
		// This is a fix for classes named OAuth to prevent it from converting to o_auth.
		$name = Str::snake(str_replace('OAuth', 'Oauth', class_basename($relatedTo)));

		return ($plural) ? Str::plural($name) : Str::singular($name);
	}

	/**
	 * Finds the model with the hasMany relationship side from the foreignKey.
	 *
	 * @param ForeignKeyConstraint $foreignKey
	 *
	 * @return mixed
	 */
	public function hasManyModel(ForeignKeyConstraint $foreignKey)
	{
		return $this->tableMap->get($foreignKey->getForeignTableName());
	}

	/**
	 * Finds the model with the belongsTo relationship side from the foreignKey.
	 *
	 * @param ForeignKeyConstraint $foreignKey
	 *
	 * @return mixed
	 */
	public function belongsToModel(ForeignKeyConstraint $foreignKey)
	{
		return $this->tableMap->get($foreignKey->getLocalTableName());
	}

	/**
	 * Finds the model with the belongsToMany relationship side from the foreignKey.
	 *
	 * @param ForeignKeyConstraint $foreignKey
	 *
	 * @return mixed
	 */
	public function belongsToManyModel(ForeignKeyConstraint $foreignKey)
	{
		return $this->tableMap->get($foreignKey->getForeignTableName());
	}

	/**
	 * Determines if the table facilitates many to many relationships.
	 * This one is a little tricky because it's not inherently obvious what determines a join table.
	 *
	 * This is what passes for a join table:
	 * 1) First it MUST have more than one foreign key.
	 *
	 * 2a) If there is no model mapped to this table, we assume this must be a join table.
	 *      OR
	 * 2b) If the table has fewer than two columns that are not either 'created_at',
	 *      'updated_at', the primary key, or any foreign keys.
	 *
	 * Again, #1 must be true and either 2a or 2b for the table to be considered a join table.
	 *
	 * @param Table $table - The table that will be analyzed.
	 *
	 * @return bool
	 */
	public function hasManyToManyRelations(Table $table)
	{
		// First we check if it has multiple foreign keys on the table.
		if (count($table->getForeignKeys()) > 1) {

			// If this table is not mapped to a model, then we assume it's a many to many table.
			if (! array_has($this->tableMap, $table->getName())) {
				return true;
			}

			// Even if a model does map to this table, we still determine it's
			// a join table when there is no significant amount of data being
			// stored except for the joining of models.
			$dateColumns = ['created_at', 'updated_at'];
			$fkCols      = [];
			foreach ($table->getForeignKeys() as $fk) {
				/* @var $fk ForeignKeyConstraint */
				$fkCols = array_merge($fkCols, $fk->getColumns());
			}

			$columnsThatMatter = array_diff(array_keys($table->getColumns()), $table->getPrimaryKey()
				->getColumns(), $dateColumns, $fkCols);

			if (count($columnsThatMatter) < 2) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if the table has a one to many relationship. If it does, we
	 * then also know that the inverse relationship also exists.
	 *
	 * @param Table $table - The table to check for relations.
	 *
	 * @return bool
	 */
	public function hasOneToManyRelations(Table $table): bool
	{
		return (bool) count($table->getForeignKeys());
	}

	/**
	 * Create a collection that is keyed by table name and points to the model it uses.
	 *
	 * @param array $models
	 *
	 * @return Collection
	 */
	private function getTableToModelCollection(array $models): Collection
	{
		$collection = new Collection();

		foreach ($models as $model) {
			$collection->put((new $model)->getTable(), $model);
		}

		return $collection;
	}
}
