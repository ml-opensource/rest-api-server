<?php

namespace Fuzz\ApiServer\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Console\AppNamespaceDetectorTrait;
use LucaDegasperi\OAuth2Server\Storage\FluentScope;
use LucaDegasperi\OAuth2Server\Storage\FluentClient;

class ApiInitializeCommand extends Command
{
	/**
	 * Enable the ability to detect the app namespace.
	 */
	use AppNamespaceDetectorTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'api:initialize';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Initialize an API code base.';

	/**
	 * The client repo.
	 *
	 * @var \LucaDegasperi\OAuth2Server\Storage\FluentClient
	 */
	protected $client_repository;

	/**
	 * The scope repo.
	 *
	 * @var \LucaDegasperi\OAuth2Server\Storage\FluentScope
	 */
	protected $scope_repository;

	/**
	 * Class constructor.
	 *
	 * @param \LucaDegasperi\OAuth2Server\Storage\FluentClient $client_repository
	 * @return self
	 */
	public function __construct(FluentClient $client_repository, FluentScope $scope_repository)
	{
		parent::__construct();

		$this->client_repository = $client_repository;
		$this->scope_repository  = $scope_repository;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->publishVendorPackages();
		$this->runVendorPackageMigrations();
		$this->createAuthorizationDefaults();
	}

	/**
	 * Publish vendor packages.
	 *
	 * @return void
	 */
	private function publishVendorPackages()
	{
		$parameters = [];

		$this->call('vendor:publish', ['--force' => 1]);
	}

	/**
	 * Run vendor package migrations.
	 *
	 * @return void
	 */
	private function runVendorPackageMigrations()
	{
		$this->call('migrate', [
			'--database' => $this->input->getOption('database'),
		]);
	}

	/**
	 * Create defaults for authorization.
	 *
	 * @return void
	 */
	private function createAuthorizationDefaults()
	{
		$namespace     = str_replace('\\', '', $this->getAppNamespace());
		$client_name   = Str::slug($namespace, '_');
		$client_id     = Str::random();
		$client_secret = Str::random(32);

		$this->client_repository->create($client_name, $client_id, $client_secret);
		$this->info('Client created successfully!');

		$scope_id          = sprintf('%s_user', $client_name);
		$scope_description = sprintf('Default user scope for %s.', $namespace);

		$this->scope_repository->create($scope_id, $scope_description);
		$this->info('Scope created successfully!');

		$this->comment(sprintf('Client ID:     %s', $client_id));
		$this->comment(sprintf('Client Secret: %s', $client_secret));
		$this->comment(sprintf('Scope ID:      %s', $scope_id));
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			[
				'database',
				null,
				InputOption::VALUE_OPTIONAL,
				'The database connection to use.',
			],
		];
	}
}
