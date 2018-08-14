<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class GenModelCommand extends ModelMakeCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'gen:model';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Eloquent model class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Model';

	/**
	 * Duplicate of base parent method.
	 *
	 * @return bool
	 */
	protected function baseFire()
	{
		$name = $this->qualifyClass($this->getNameInput());

		$path = $this->getPath($name);

		// First we will check to see if the class already exists. If it does, we don't want
		// to create the class and overwrite the user's code. So, we will bail out so the
		// code is untouched. Otherwise, we will continue generating this class' files.
		if ($this->alreadyExists($this->getNameInput())) {
			$this->error($this->type.' already exists!');

			return false;
		}

		// Next, we will generate the path to the location where this class' file should get
		// written. Then, we will build the class and make the proper replacements on the
		// stub files so that it gets the correctly formatted namespace and class name.
		$this->makeDirectory($path);

		$this->files->put($path, $this->buildClass($name));

		$this->info($this->type.' created successfully.');
	}

	public function handle()
	{
		$this->baseFire();

		if ($this->option('controller') || $this->option('resource')) {
			$this->createController();
		}

		if ($this->option('tests')) {
			$this->createTest();
		}

		if ($this->option('factory')) {
			$this->createFactory();
		}

		if ($this->option('migration')) {
			$this->createMigration();
		}

		if ($this->option('seeder')) {
			$this->createSeeder();
		}
	}

	/**
	 * Create a factory file for the model.
	 *
	 * @return void
	 */
	protected function createFactory()
	{
		$model = $this->getNameInput();

		$this->call('gen:factory', ['name' => $model]);
	}

	/**
	 * Create a test file for the model.
	 *
	 * @return void
	 */
	protected function createTest()
	{
		$model = $this->getNameInput();

		$this->call('gen:test', ['name' => "Models/{$model}Test", '--model' => $model]);
	}

	/**
	 * Create a migration file for the model.
	 *
	 * @return void
	 */
	protected function createSeeder()
	{
		$model = $this->getNameInput();

		$this->call('gen:seeder', ['name' => "{$model}Seeder", '--model' => $model, '--dumpautoload' => $this->option('dumpautoload')]);
	}

	/**
	 * Create a migration file for the model.
	 *
	 * @return void
	 */
	protected function createMigration()
	{
		$model = $this->getNameInput();

		$this->call('gen:migration', ['model' => $model, '--dumpautoload' => $this->option('dumpautoload')]);
	}

	/**
	 * Create a controller for the model.
	 *
	 * @return void
	 */
	protected function createController()
	{
		$controller = Str::studly(class_basename($this->argument('name')));

		$modelName = $this->qualifyClass($this->getNameInput());
		$options   = [
			'name'    => "{$controller}Controller",
			'--model' => $modelName,
		];

		if ($this->options('tests')) {
			$options['--test'] = true;
		}

		$this->call('gen:controller', $options);
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__ . '/stubs/model.stub';
	}

	/**
	 * Get the table name for the class.
	 *
	 * @return array|string
	 */
	protected function getTableName()
	{
		return $this->option('table') ?: Str::plural(Str::snake(class_basename($this->argument('name'))));
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
		$replace = [
			'DummyTable' => $this->getTableName(),
		];

		return str_replace(array_keys($replace), array_values($replace), parent::buildClass($name));
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the model already exists.'],
			['table', 't', InputOption::VALUE_REQUIRED, 'Define the database table for the model.'],
			['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model.'],
			['controller', 'c', InputOption::VALUE_NONE, 'Create a new controller for the model.'],
			['seeder', 's', InputOption::VALUE_NONE, 'Create a new seeder for the model.'],
			['factory', 'fac', InputOption::VALUE_NONE, 'Create a new factory for the model.'],
			['tests', 'tests', InputOption::VALUE_NONE, 'Create tests boilerplate.'],
			['resource', 'r', InputOption::VALUE_NONE, 'Indicates if the generated controller should be a resource controller.'],
			['dumpautoload', 'da', InputOption::VALUE_REQUIRED, 'Specify if the composer should dump its autoloader.',
				true,
			],
		];
	}
}
