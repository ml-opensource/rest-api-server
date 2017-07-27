<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

class GenMigrateCommand extends GeneratorCommand
{
	/**
	 * The console command signature.
	 *
	 * @var string
	 */
	protected $signature = 'gen:migration {model : The model this create table migration should be based off.}
        {--name= : The file name for this migration.}
        {--dumpautoload=true : The file name for this migration.}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new migration file';

	/**
	 * The Composer instance.
	 *
	 * @var \Illuminate\Support\Composer
	 */
	protected $composer;

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Migration';

	/**
	 * Create a new command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem $files
	 * @param  \Illuminate\Support\Composer      $composer
	 */
	public function __construct(Filesystem $files, Composer $composer)
	{
		parent::__construct($files);

		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return bool|null
	 */
	public function fire()
	{
		$this->ensureMigrationDoesntAlreadyExist();

		$path = $this->getMigrationsPath();

		// Next, we will generate the path to the location where this class' file should get
		// written. Then, we will build the class and make the proper replacements on the
		// stub files so that it gets the correctly formatted namespace and class name.
		$this->makeDirectory($path);

		$this->files->put($path, $this->buildClass($this->getClassName()));

		$this->info($this->type . ' created successfully.');

		if ($this->option('dumpautoload')) {
			$this->composer->dumpAutoloads();
		}
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__ . '/stubs/migration.stub';
	}

	/**
	 * Build the class with the given name.
	 *
	 * Remove the base controller import if we are already in base namespace.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function buildClass($name)
	{
		$replace = [];

		$replace = $this->buildModelReplacements($replace);

		return str_replace(
			array_keys($replace), array_values($replace), parent::buildClass($name)
		);
	}

	/**
	 * Build the model replacement values.
	 *
	 * @param  array $replace
	 *
	 * @return array
	 */
	protected function buildModelReplacements(array $replace)
	{
		$modelClass = $this->parseClass($this->argument('model'));

		if (! class_exists($modelClass)) {
			if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
				$this->call('gen:model', ['name' => $modelClass]);
			}
		}

		return array_merge($replace, [
			'DummyFullModelClass' => $modelClass,
			'DummyModelClass'     => class_basename($modelClass),
		]);
	}

	/**
	 * Get the fully-qualified class name.
	 *
	 * @param string      $class
	 * @param string|null $namespace
	 *
	 * @return string
	 */
	protected function parseClass(string $class = null, string $namespace = null): string
	{
		if (preg_match('([^A-Za-z0-9_/\\\\])', $class)) {
			throw new \InvalidArgumentException('Class name contains invalid characters.');
		}

		$class         = trim(str_replace('/', '\\', $class), '\\');
		$rootNamespace = $namespace ?? $this->laravel->getNamespace();

		if (! Str::startsWith($class, $rootNamespace)) {
			$class = $rootNamespace . $class;
		}

		return $class;
	}

	/**
	 * Get the table name for the model.
	 *
	 * @param $model
	 *
	 * @return null|string
	 */
	protected function getTableName($model)
	{
		$table = null;
		$model = $this->parseClass($model);

		if (! class_exists($model)) {
			$table = Str::plural(Str::snake(class_basename($model)));
		}

		return $table ?? (new $model)->getTable();
	}

	/**
	 * Get the destination class path.
	 *
	 * @return string
	 */
	protected function getMigrationsPath(): string
	{
		return $this->laravel->databasePath() . '/migrations/' . $this->getDatePrefix() . '_' . $this->getNameInput() . '.php';
	}

	/**
	 *
	 *
	 * @return false|string
	 */
	protected function getDatePrefix()
	{
		return date('Y_m_d_His');
	}

	/**
	 * Parse the class name and format according to the root namespace.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function qualifyClass($name)
	{
		return $name;
	}

	/**
	 * Get the desired model name from the input.
	 *
	 * @return string
	 */
	protected function getModelInput()
	{
		return trim($this->argument('model'));
	}

	/**
	 * Get the desired class name from the input.
	 *
	 * @return string
	 */
	protected function getNameInput(): string
	{
		return trim($this->option('name')) ?: 'create_' . $this->getTableName($this->getModelInput()) . '_table';
	}

	/**
	 * Get the class name of the migration.
	 *
	 * @return string
	 */
	protected function getClassName(): string
	{
		return Str::studly($this->getNameInput());
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments(): array
	{
		return [];
	}

	/**
	 * Ensure that a migration with the given name doesn't already exist.
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function ensureMigrationDoesntAlreadyExist()
	{
		if (class_exists($className = $this->getClassName())) {
			throw new \InvalidArgumentException("A {$className} migration already exists.");
		}
	}
}
