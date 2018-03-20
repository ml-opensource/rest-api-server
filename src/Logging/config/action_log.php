<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Default Logging Engine
	|--------------------------------------------------------------------------
	|
	| This option controls the default logging connection that gets used while
	| using Felk. This connection is used when logging requests and responses.
	| You should adjust this based on your needs.
	|
	| Supported: "aws-elasticsearch", "mysql", "null_engine"
	|
	*/

	'driver' => env('ACTION_LOG_DRIVER', 'null_engine'),

	/*
	|--------------------------------------------------------------------------
	| Elasticsearch Configuration
	|--------------------------------------------------------------------------
	|
	| Here you may configure your elasticsearch settings.
	|
	*/

	'elasticsearch' => [
		'region' => env('AWS_ELASTICSEARCH_REGION', 'us-east-1'),
		// Only needed when using aws-elasticsearch provider.
		'config' => [
			'hosts' => [
				[
					'host'   => env('ELASTICSEARCH_HOST', 'localhost'),
					'port'   => env('ELASTICSEARCH_PORT', 9200),
					'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
					'user'   => env('ELASTICSEARCH_USERNAME'),
					'pass'   => env('ELASTICSEARCH_PASSWORD'),
				],
			],
		],
	],

	/*
	|--------------------------------------------------------------------------
	| MySQL Configuration
	|--------------------------------------------------------------------------
	|
	| Here you may configure your MySQL Logger settings.
	|
	*/

	'mysql' => [
		'model_class' => \Fuzz\ApiServer\Logging\ActionLog::class,
	],

	/*
	|--------------------------------------------------------------------------
	| Action Logger Configuration
	|--------------------------------------------------------------------------
	|
	| Configure all options of the action logger
	|
	*/

	'enabled' => env('LOG_ACTIONS_ENABLED', true),

	/*
	|--------------------------------------------------------------------------
	| Prefix
	|--------------------------------------------------------------------------
	|
	| Here you may specify a prefix that will be applied to all logging records
	| recorded by the action logger. This prefix may be useful if you have multiple
	| "tenants" or applications sharing the same logging infrastructure.
	|
	*/

	'prefix' => env('APP_NAME', 'koala_api'),
];