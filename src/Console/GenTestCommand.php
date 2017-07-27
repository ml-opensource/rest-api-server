<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\Str;

class GenTestCommand extends TestMakeCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'gen:test 
						    {name : The name of the class} 
						    {--model= : Create a model test} 
						    {--controller= : Create a controller test}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new test class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Test';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('model') !== null) {
			return __DIR__ . '/stubs/test.model.stub';
		} elseif ($this->option('controller') !== null) {
			return __DIR__ . '/stubs/test.controller.stub';
		}

		return __DIR__ . '/stubs/test.stub';
	}

	/**
	 * Build the class with the given name.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function buildClass($name)
	{
		$replace = [];

		if ($this->option('model')) {
			$replace = $this->buildModelReplacements($replace);
		} elseif ($this->option('controller')) {
			$replace = $this->buildControllerReplacements($replace);
		}

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
		$modelClass = $this->parseClass($this->option('model'));

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
	 * Build the model replacement values based on the controller.
	 *
	 * @param  array $replace
	 *
	 * @return array
	 */
	protected function buildControllerReplacements(array $replace)
	{
		$controllerClass = $this->parseClass($this->option('controller'), $this->laravel->getNamespace() . 'Http\Controllers\\');

		if (! class_exists($controllerClass)) {
			if ($this->confirm("A {$controllerClass} controller does not exist. Do you want to generate it?", true)) {
				$this->call('gen:controller', ['name' => $controllerClass]);
			}
		}

		if (! isset($controllerClass::$resource)) {
			return $replace;
		}

		return array_merge($replace, [
			'DummyFullModelClass' => $controllerClass::$resource,
			'DummyModelClass'     => class_basename($controllerClass::$resource),
			'DummyUrlPath'        => Str::plural(Str::lower(class_basename($controllerClass::$resource))),
		]);
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

		return $this->laravel->basePath() . '/tests' . str_replace('\\', '/', $name) . '.php';
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string $rootNamespace
	 *
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace;
	}

	/**
	 * Get the root namespace for the class.
	 *
	 * @return string
	 */
	protected function rootNamespace()
	{
		return 'Tests';
	}

	/**
	 * Get the fully-qualified class name.
	 *
	 * @param string      $class
	 * @param string|null $namespace
	 *
	 * @return string
	 */
	protected function parseClass(string $class, string $namespace = null): string
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
}
