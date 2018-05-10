<?php

namespace Priskz\SORAD\Service\Laravel;

use Priskz\Payload\Payload;

abstract class GenericCrudService extends GenericService
{
    /**
     * @property $dataSource
     */
	protected $dataSource;

    /**
     * @property array $configuration
     */
	protected $configuration = [];

    /**
     * @property array $core
     */
	protected $core = [
		'configuration' => [
			'GET' => [
				'keys'     => [],
				'rules'    => [],
				'defaults' => [],
			],
			'CREATE' => [
				'keys'     => [],
				'rules'    => [],
				'defaults' => [],
			],
			'UPDATE' => [
				'keys'     => [],
				'rules'    => [],
				'defaults' => [],
			],
			'DELETE' => [
				'keys'     => [],
				'rules'    => [],
				'defaults' => [],
			],
			'PURGE' => [
				'keys'     => [],
				'rules'    => [],
				'defaults' => [],
			]
		]
	];

	/**
	 * Get the Data Source.
	 */
	public function getDataSource()
	{
		return $this->dataSource;
	}

	/**
	 * Get the process configurations.
	 *
	 * @return array
	 */
	public function getConfiguration($key = null)
	{
		if(is_null($key))
		{
			return $this->configuration + $this->core['configuration'];
		}

		$configuration = $this->configuration + $this->core['configuration'];

		if(array_key_exists($key, $configuration))
		{
			return $configuration[$key];
		}

		return null;
	}

	/**
	 * Get
	 *
	 * @return Payload
	 */
	public function get($data = [])
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if( ! $processPayload->isStatus(Payload::STATUS_VALID))
		{
			return $processPayload;
		}

		return $this->dataSource->get($data);
	}

	/**
	 * Create a new Model with the given data.
	 *
	 * @param  array  $data
	 * @return Payload
	 */
	public function create($data)
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if( ! $processPayload->isStatus(Payload::STATUS_VALID))
		{
			return $processPayload;
		}

		return $this->dataSource->create($processPayload->getData());
	}

	/**
	 * Update the given Model with the given data
	 *
	 * @param  array  $data
	 * @param  \Paylorm\Laravel\ $object
	 * @return Payload
	 */
	public function update($data, $object)
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if( ! $processPayload->isStatus(Payload::STATUS_VALID))
		{
			return $processPayload;
		}

		return $this->dataSource->update($processPayload->getData(), $object);
	}

	/**
	 * Delete
	 *
	 * @param  $data
	 * @return Payload
	 */
	public function delete($data)
	{
		// @todo: implement process?
		return $this->dataSource->delete($data);
	}

	/**
	 * Purge
	 *
	 * @param  $data
	 * @return Payload
	 */
	public function purge($data)
	{
		// @todo: implement process?
		return $this->dataSource->purge($data);
	}

	/**
	 * Process Data for the Given Context.
	 *
	 * @param  string $context Type of function to be processed.
	 * @param  array  $data    Data to be processed.
	 * @return Payload
	 */
	protected function process($context, $data)
	{
		// Make sure the given context is all uppercase.
		$context = strtoupper($context);

		// Ensure the given context is configured before processing.
		if(is_null($this->getConfiguration($context)))
		{
			return new Payload(null, strtolower($context . '_not_configured'));
		}

		//  Finally, process the data.
		return $this->processor->process($data, $this->getConfiguration($context)['keys'], $this->getConfiguration($context)['rules'], $this->getConfiguration($context)['defaults']);
	}
}