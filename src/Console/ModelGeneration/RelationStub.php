<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;


/**
 * The RelationStub uses a relation definition to fill in the information for a stub file.
 * The compiled stub file will then be used by the StubWriter to write to the model.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 */
class RelationStub
{
	/**
	 * Stub file extension.
	 */
	const FILE_EXTENSION = '.stub';

	/**
	 * The placeholder for the relation name.
	 */
	const DUMMY_NAME = 'DummyName';

	/**
	 * The placeholder for the class this relation will conenct to.
	 */
	const DUMMY_RELATED_TO_CLASS = 'DummyRelatedToClass';

	/**
	 * The placeholder for parameters. The stubs suffix them with an integer
	 * starting from 0. Different relationship types have different sets of
	 * parameters, so we just number them starting after the related to parameter.
	 */
	const DUMMY_PARAM = 'DummyParam';

	/**
	 * A reserved set of strings. We wrap our other DummyParam in quotes, except
	 * any param that has a name within this array.
	 */
	const RESERVED = ['__FUNCTION__'];

	/**
	 * The RelationDefinition.
	 *
	 * @var RelationDefinition
	 */
	protected $definition;

	/**
	 * RelationStub constructor.
	 *
	 * @param RelationDefinition $definition
	 */
	public function __construct(RelationDefinition $definition)
	{
		$this->definition = $definition;
	}

	/**
	 * Returns the compiled stub for the relation definition.
	 *
	 * @return string
	 */
	public function compile(): string
	{
		$stub = app()->files->get($this->getStubPath());

		return str_replace(
			iterator_to_array($this->searchForParams(), false),
			iterator_to_array($this->replaceWithParams(), false),
			$stub
		);
	}

	/**
	 * The name of the stub file.
	 * Note: This does not include the dir path or extention.
	 *
	 * @return string
	 */
	public function getStubName(): string
	{
		return class_basename($this->definition->getRelationship());
	}

	/**
	 * Returns a string for the function declaration.
	 *
	 * @return string
	 */
	public function getFunctionDeclaration(): string
	{
		return "function {$this->definition->getName()}()";
	}

	/**
	 * Returns a string for the relationship use statement.
	 *
	 * @return string
	 */
	public function getUseStatement(): string
	{
		return "use {$this->definition->getRelationship()};";
	}

	/**
	 * The full path to the stub file, including filename, and extension.
	 *
	 * @example:
	 *         Users/home/Sites/project/vendor/fuzz/api-server/Console/ModelGeneration/stubs/HasMany.stub
	 *
	 * @return string
	 */
	public function getStubPath(): string
	{
		return $this->getStubDir() . $this->getStubName() . self::FILE_EXTENSION;
	}

	/**
	 * The directory the stub file is in, does not including the file name.
	 *
	 * @example:
	 *         Users/home/Sites/project/vendor/fuzz/api-server/Console/ModelGeneration/stubs
	 *
	 * @return string
	 */
	public function getStubDir(): string
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Defines what casting this class to a string will do.
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->compile();
	}

	/**
	 * Returns the set of parameters we search for in the stub.
	 *
	 * @return \Generator
	 */
	protected function searchForParams(): \Generator
	{
		yield self::DUMMY_NAME;
		yield self::DUMMY_RELATED_TO_CLASS;
		for ($i = 0; $i < count($this->definition->getParams()); $i++) {
			yield self::DUMMY_PARAM . $i;
		}
	}

	/**
	 * Returns the values for the parameters we search for in the stub.
	 *
	 * @return \Generator
	 */
	protected function replaceWithParams(): \Generator
	{
		yield $this->definition->getName();
		yield $this->definition->getRelatedToBaseName();
		foreach ($this->definition->getParams() as $param) {
			yield $this->wrapWithQuotes($param);
		}
	}

	/**
	 * Wraps a string with single or double quotes, taking care to exclude any reserved strings.
	 *
	 * @param string $string - The string to wrap.
	 *
	 * @return string
	 */
	protected function wrapWithQuotes(string $string): string
	{
		if (! in_array($string, self::RESERVED)) {
			return "'{$string}'";
		}

		return $string;
	}
}
