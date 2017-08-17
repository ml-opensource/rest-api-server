<?php


namespace Fuzz\ApiServer\Console\ModelGeneration;

use Fuzz\ApiServer\Console\Commands\ModelGeneration\StubWriter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;


/**
 * The ModelWriter will write the output for the model generation.
 *
 * @package Fuzz\ApiServer\Console\ModelGeneration
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class ModelWriter
{
	/**
	 * The list of models we want to write to.
	 *
	 * @var array
	 */
	protected $models;

	/**
	 * The relation tree holding all the info.
	 *
	 * @var RelationTree
	 */
	protected $tree;

	/**
	 * Laravel app for FileSystem access mostly.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * ModelWriter constructor.
	 *
	 * @param Application  $app
	 * @param RelationTree $tree
	 * @param array|null   $models
	 */
	public function __construct(Application $app, RelationTree $tree, array $models = null)
	{
		$this->app    = $app;
		$this->models = $models;
		$this->tree   = $tree;
	}


	/**
	 * Write to all the model files we passed in.
	 *
	 * Think of this as a `run` command.
	 */
	public function writeToFiles()
	{
		$modelNodes = $this->tree->only($this->models);

		foreach ($modelNodes as $modelNode) {

			$this->writeToFile($modelNode);

		}
	}

	/**
	 * Run through each relation for a model, and attempt to write them to the file.
	 *
	 * @param ModelNode $modelNode
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	public function writeToFile(ModelNode $modelNode)
	{
		$path = $this->getPath($modelNode->getName());

		foreach ($modelNode->relations() as $relation) {

			(new StubWriter($relation->toStub(), $this->getFileSystem()))->write($path);

		}
	}

	/**
	 * The FileSystem.
	 *
	 * @return Filesystem
	 */
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
	protected function getPath(string $name): string
	{
		$name = str_replace_first($this->rootNamespace(), '', $name);

		return $this->app['path'] . '/' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace(): string
	{
		return $this->app->getNamespace();
	}
}
