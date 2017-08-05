<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;


/**
 * The ModelWriter will write the output for the model generation.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 *
 * @author Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class ModelWriter
{
	protected $models;
	protected $tree;
	protected $app;

	public function __construct(Application $app, RelationTree $tree, array $models = null)
	{
		$this->app = $app;
		$this->models = $models;
		$this->tree = $tree;
	}


	public function writeToFiles()
	{
		$modelNodes = $this->tree->only($this->models);
		//dd($this->tree->only($this->models));
		foreach ($modelNodes as $modelNode) {

			$this->writeToFile($modelNode);

		}
	}

	public function writeToFile(ModelNode $modelNode)
	{
		$path = $this->getPath($modelNode->getName());

		foreach ($modelNode->relations() as $relation) {

			(new StubWriter($relation->toStub(), $this->getFileSystem()))->write($path);

		}
	}

	//public function write(RelationDefinition $relation, $file)
	//{
	//
	//	// Add in the use statement if it doesn't exist.
	//	if (! strpos($file, $relation->getUseStatement())) {
	//		$file = preg_replace('/(use .*;)/', '$1 ' . "\n{$relation->getUseStatement()}", $file, 1);
	//	}
	//
	//	// Add the relationship.
	//	$file = preg_replace(
	//		'/\{(.*)\}/s', // Will select entire class contents.
	//		'{$1' . "\n" . (string) new CompiledRelationStub($relation) . '}',
	//		$file,
	//		1
	//	);
	//
	//	return $file;
	//}

	protected function getFileSystem()
	{
		return $this->app['files'];
	}

	/**
	 * Get the destination class path.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function getPath($name)
	{
		$name = str_replace_first($this->rootNamespace(), '', $name);

		return $this->app['path'] . '/' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace()
	{
		return $this->app->getNamespace();
	}
}
