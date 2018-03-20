<?php

namespace Fuzz\ApiServer\Logging;

use Aws\ElasticsearchService\ElasticsearchPhpHandler;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Manager;

class ActionLogEngineManager extends Manager
{
	/**
	 * Get a driver instance.
	 *
	 * @param  string|null $name
	 *
	 * @return mixed
	 */
	public function engine($name = null)
	{
		return $this->driver($name);
	}

	/**
	 * Create an aws-elasticsearch engine instance.
	 *
	 * @return \Fuzz\ApiServer\Logging\ElasticSearchActionLogger
	 */
	public function createAwsElasticsearchDriver(): ElasticSearchActionLogger
	{
		return new ElasticSearchActionLogger(
			config('action_log'),
			Request::instance(),
			ClientBuilder::create()
						 ->setHandler(new ElasticsearchPhpHandler(config('action_log.elasticsearch.region')))
						 ->setHosts(config('action_log.elasticsearch.config.hosts'))
						 ->build());
	}

	/**
	 * Create a Null engine instance.
	 *
	 * @return \Fuzz\ApiServer\Logging\MySQLActionLogger
	 */
	public function createMysqlDriver(): MySQLActionLogger
	{
		return new MySQLActionLogger(config('action_log'), Request::instance());
	}

	/**
	 * Create a Null engine instance.
	 *
	 * @return \Fuzz\ApiServer\Logging\NullActionLogger
	 */
	public function createNullEngineDriver(): NullActionLogger
	{
		return new NullActionLogger(config('action_log'), Request::instance());
	}

	/**
	 * Get the default action log driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['action_log.driver'];
	}
}
