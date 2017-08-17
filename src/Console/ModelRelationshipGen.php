<?php

namespace Fuzz\ApiServer\Console\Commands;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Fuzz\ApiServer\Console\ModelGeneration\ModelWriter;
use Fuzz\ApiServer\Console\ModelGeneration\RelationMapper;


/**
 * Generates relationships for models.
 *
 * @package Fuzz\ApiServer\Console\Commands
 *
 * @author  Kirill Fuchs <kfuchs@fuzzproductions.com>
 */
class ModelRelationshipGen extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'gen:relationships 
            {model? : The model to generate relationships for.}
            {--database= : The database connection to use.}
            {--path= : Where the model(s) are located.}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generates relationships for models based on database foreign keys.';

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * ModelRelationshipGen constructor.
	 *
	 * @param DatabaseManager $manager
	 * @param Filesystem      $files
	 * @param Config          $config
	 */
	public function __construct(DatabaseManager $manager, Filesystem $files, Config $config)
	{
		$this->manager = $manager;
		$this->files   = $files;
		$this->config  = $config;
		parent::__construct();
	}


	/**
	 * Fire the cmd.
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function handle()
	{
		$models = $this->getModelsFromDir($this->getModelsDir());
		$selectedModels = ($this->argument('model')) ? [$this->getQualifiedClass($this->argument('model'))] : $models;


		// This fixes an issue with doctrine and mysql enums...
		$connection = $this->manager->connection()->getDoctrineConnection();
		$platform   = $connection->getDatabasePlatform();
		$platform->registerDoctrineTypeMapping('enum', 'string');
		$schemaManager = $this->manager->connection()->getDoctrineSchemaManager();

		$tree = (new RelationMapper($schemaManager, $models))->map();

		(new ModelWriter($this->getLaravel(), $tree, $selectedModels))->writeToFiles();
	}

	/**
	 * Gets all the models from the directories.
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	public function getModelsFromDir(string $dir): array
	{
		$models = [];

		$files = $this->files->files($dir);

		foreach ($files as $file) {
			$class = $this->getQualifiedClass(trim(str_replace([$this->laravel->path(), '.php'], '', $file), '/'));
			if ($this->isEloquentModel($class)) {
				$models[] = $class;
			}
		}

		return $models;
	}

	/**
	 * Checks if it's an eloquent model.
	 *
	 * @param string $class
	 *
	 * @return bool
	 */
	public function isEloquentModel(string $class): bool
	{
		return (new \ReflectionClass($class))->isSubclassOf(Model::class);
	}

	/**
	 * Gets the classes fully qualified name.
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	public function getQualifiedClass(string $class): string
	{
		$rootNamespace = $this->rootNamespace();

		if (Str::startsWith($class, $rootNamespace)) {
			return $class;
		}

		$class = str_replace('/', '\\', $class);

		return $this->getQualifiedClass(
			trim($rootNamespace, '\\') . '\\' . $class
		);
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

		return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the full namespace for a given class, without the class name.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function getNamespace(string $name): string
	{
		return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
	}

	/**
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace(): string
	{
		return $this->laravel->getNamespace();
	}

	/**
	 * Gets the directory the models are in.
	 *
	 * @return string
	 */
	protected function getModelsDir(): string
	{
		if ($this->argument('model')) {
			return $this->laravel->path() . DIRECTORY_SEPARATOR . trim(implode('/', array_slice(explode('/', $this->argument('model')), 0, -1)), '/');
		}

		if ($this->option('path')) {
			return $this->laravel->path() . DIRECTORY_SEPARATOR . trim($this->option('path'), '/');
		}

		return $this->laravel->path();
	}
}

