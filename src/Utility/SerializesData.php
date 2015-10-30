<?php

namespace Fuzz\ApiServer\Utility;

use Carbon\Carbon;
use League\Csv\Writer;
use SplTempFileObject;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use Illuminate\Support\Facades\Route;
use League\Fractal\Resource\Collection;
use Illuminate\Pagination\AbstractPaginator;
use League\Fractal\Resource\ResourceInterface;
use Fuzz\ApiServer\Exception\BadRequestException;
use Fuzz\Data\Serialization\FuzzModelTransformer;
use Fuzz\Data\Serialization\FuzzDataArraySerializer;
use Fuzz\Data\Serialization\FuzzCsvDataArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

/**
 * Class SerializesData
 *
 * @package Fuzz\ApiServer\Utility
 */
trait SerializesData
{
	/**
	 * Supported data serialization formats
	 *
	 * @var array
	 */
	public $export_formats = [
		'json' => 'formatJsonResponse',
		'csv' => 'formatCsvResponse',
	];

	/**
	 * Get an instance of the serialization manager
	 *
	 * @param $format
	 * @return \League\Fractal\Manager
	 * @throws \Fuzz\ApiServer\Exception\BadRequestException
	 */
	public function serializerManager($format)
	{
		if (! $this->isValidFormat($format)) {
			throw new BadRequestException('The requested format is invalid');
		}

		$manager = new Manager;
		$path = ['view', 'serialization', $format, 'serializer'];
		$serializer_class = config(implode('.', $path) , FuzzDataArraySerializer::class);
		$manager->setSerializer(new $serializer_class);
		return $manager;
	}

	/**
	 * Determine the appropriate data transformer
	 *
	 * @param mixed $transformer
	 * @param string $format
	 *
	 * @return callable|object
	 */
	public function findTransformer($transformer, $format)
	{
		if ($format === 'csv') {
			$path = ['view', 'serialization', $format, 'transformer'];
			$class = config(implode('.', $path), FuzzCsvDataArraySerializer::class);
			return new $class;
		}

		return is_callable($transformer) ? $transformer : new $transformer;
	}

	/**
	 * Invoke the Fractal Manager to return an array that can be encoded as a JSON response
	 *
	 * @param \League\Fractal\Manager                    $manager
	 * @param \League\Fractal\Resource\ResourceInterface $resource
	 *
	 * @return array
	 */
	public function formatJsonResponse(Manager $manager, ResourceInterface $resource)
	{
		return $manager->createData($resource)->toArray();
	}

	/**
	 * Format a CSV file download response
	 *
	 * Note that the request ends here as an octet stream (headers set in League\Csv\Writer@output)
	 *
	 * @param \League\Fractal\Manager                    $manager
	 * @param \League\Fractal\Resource\ResourceInterface $resource
	 */
	public function formatCsvResponse(Manager $manager, ResourceInterface $resource)
	{
		$rows = $manager->createData($resource)->toArray();
		$csv = Writer::createFromFileObject(new SplTempFileObject());

		if (array_key_exists('meta', $rows)) {
			unset($rows['meta']);
		}

		$csv->insertAll($rows);
		$csv->output(Carbon::now()->toDateTimeString() . config('app.timezone')  .'-export.csv');
		// End processing and dump the file stream to a response. There is most likely a better solution.
		die;
	}

	/**
	 * Serialize a collection of resources
	 *
	 * @param \Illuminate\Pagination\AbstractPaginator|array $data
	 * @param callable|string                                $transformer
	 * @param string                                         $format
	 * @return array
	 * @throws \Fuzz\ApiServer\Exception\BadRequestException
	 */
	public function serializeCollection($data, $transformer = FuzzModelTransformer::class, $format = 'json')
	{
		$manager = $this->serializerManager($format);

		$collection = $data;

		// Transformers can be a class namespace string or a callable closure
		$transformer = $this->findTransformer($transformer, $format);

		if ($data instanceof AbstractPaginator) {
			$collection = $data->getCollection();
			$resource = new Collection($collection, $transformer, Route::currentRouteName());
			$resource->setPaginator(new IlluminatePaginatorAdapter($data));
		} else {
			$resource = new Collection($collection, $transformer, Route::currentRouteName());
		}

		$response_formatter = $this->export_formats[$format];
		return $this->$response_formatter($manager, $resource);
	}

	/**
	 * Serialize a single resource
	 *
	 * @param \Fuzz\MagicBox\Contracts\Repository|array $data
	 * @param callable|string                           $transformer
	 * @param string                                    $format
	 * @return array
	 * @throws \Fuzz\ApiServer\Exception\BadRequestException
	 */
	public function serialize($data, $transformer = FuzzModelTransformer::class, $format = 'json')
	{
		$manager = $this->serializerManager($format);

		// Transformers can be a class namespace string or a callable closure
		$transformer = $this->findTransformer($transformer, $format);

		$resource = new Item($data, $transformer, Route::currentRouteName());

		$response_formatter = $this->export_formats[$format];
		return $this->$response_formatter($manager, $resource);
	}

	/**
	 * Validate that the requested format is one that we support
	 *
	 * @param string $format
	 * @return bool
	 */
	public function isValidFormat($format)
	{
		return in_array($format, array_keys($this->export_formats));
	}
}
