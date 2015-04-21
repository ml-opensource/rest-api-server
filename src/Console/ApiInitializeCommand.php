<?php

namespace Fuzz\ApiServer\Console;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

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
	 * The configuration repository.
	 *
	 * @var \Illuminate\Contracts\Config\Repository
	 */
	protected $config;

	/**
	 * The database manager.
	 *
	 * @var \Illuminate\Database\DatabaseManager
	 */
	protected $db;

	/**
	 * Class constructor.
	 *
	 * @param \Illuminate\Contracts\Config\Repository $config
	 * @param \Illuminate\Database\DatabaseManager    $db
	 * @return self
	 */
	public function __construct(ConfigRepository $config, DatabaseManager $db)
	{
		parent::__construct();

		$this->config = $config;
		$this->db     = $db;
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
		$parameters = [
			'--provider' => 'LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider',
		];

		if ($this->confirm('Overwrite existing package configuration and migrations? (no)')) {
			$parameters['--force'] = 1;
		}

		$this->call('vendor:publish', $parameters);
	}

	/**
	 * Run vendor package migrations.
	 *
	 * @return void
	 */
	private function runVendorPackageMigrations()
	{
		$this->call('migrate');
	}

	/**
	 * Create defaults for authorization.
	 *
	 * @return void
	 */
	private function createAuthorizationDefaults()
	{
		$client_name = Str::slug($this->getAppNamespace());
		$this->call('oauth2-server:create-client', ['clientName' => $client_name]);

		$fresh_timestamp = new Carbon;

		$db_connection = $this->config->get('oauth2.database');

		if ($db_connection === 'default') {
			$db_connection = $this->config->get('database.default');
		}

		$this->db->connection($db_connection)->table('oauth_scopes')->insert(
			[
				'id'          => 'user',
				'description' => 'The default scope for users.',
				'created_at'  => $fresh_timestamp,
				'updated_at'  => $fresh_timestamp,
			]
		);
	}
}
