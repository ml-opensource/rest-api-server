<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Routing\Console\ControllerMakeCommand;
use Symfony\Component\Console\Input\InputOption;

class GenControllerCommand extends ControllerMakeCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'gen:controller';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new controller class';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		parent::fire();

		if ($this->option('test')) {
			$this->createTest();
		}
	}

	/**
	 * Create a test for the controller.
	 *
	 * @return void
	 */
	protected function createTest()
	{
		$controllerClass = $this->getNameInput();

		$this->call('gen:test', [
			'name'         => 'Http/Controllers/' . $controllerClass . 'Test',
			'--controller' => $this->qualifyClass($this->getNameInput()),
		]);
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__ . '/stubs/controller.stub';
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
		$controllerNamespace = $this->getNamespace($name);

		$replace = [];

		if ($this->option('model')) {
			$replace = $this->buildModelReplacements($replace);
		}

		$replace["use {$controllerNamespace}\Controller;\n"] = '';

		$stub   = $this->files->get($this->getStub());
		$parent = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

		return str_replace(
			array_keys($replace), array_values($replace), $parent
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate a resource controller for the given model.'],

			['test', 't', InputOption::VALUE_NONE, 'Generate a companion test.'],
		];
	}
}
