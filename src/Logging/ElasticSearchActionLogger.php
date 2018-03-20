<?php

namespace Fuzz\ApiServer\Logging;

use Carbon\Carbon;
use Elasticsearch\Client;
use Fuzz\ApiServer\Utility\UUID;
use Illuminate\Http\Request;

class ElasticSearchActionLogger extends BaseActionLogger
{
	/**
	 * Document type
	 *
	 * @const string
	 */
	const TYPE = 'action_log';

	/**
	 * ElasticSearch client storage
	 *
	 * @var \Elasticsearch\Client
	 */
	protected $es;

	/**
	 * The prefix for the logging index.
	 *
	 * @var string
	 */
	protected $prefix;

	/**
	 * Index
	 *
	 * @var string
	 */
	protected $index;

	/**
	 * ElasticSearchActionLogger constructor.
	 *
	 * @param array                    $config
	 * @param \Illuminate\Http\Request $request
	 * @param \Elasticsearch\Client    $client
	 */
	public function __construct(array $config, Request $request, Client $client)
	{
		parent::__construct($config, $request);

		$this->es     = $client;
		$this->prefix = $config['prefix'];
		$this->index  = strtolower("{$this->prefix}_action_log");
	}

	/**
	 * Write the message queue to store
	 *
	 * @return bool
	 */
	public function flushQueue(): bool
	{
		if ($this->getQueueLength() === 0) {
			return false;
		}

		$now    = Carbon::now()->toIso8601String();
		$events = $this->getMessageQueue();

		foreach ($events as $event) {
			$event['timestamp'] = $now;

			$this->write($event);
		}

		$this->clearQueue();

		return true;
	}

	/**
	 * Log an event to the store
	 *
	 * @param array $event
	 *
	 * @return array
	 */
	public function write(array $event): array
	{
		$response = $this->es->index([
			'index' => $this->index,
			'type'  => self::TYPE,
			'id'    => UUID::generate(),
			'body'  => $event,
		]);

		return $response;
	}
}
