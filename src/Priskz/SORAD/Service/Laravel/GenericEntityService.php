<?php namespace Priskz\SORAD\Service\Laravel;

use Priskz\Payload\Payload;
use Priskz\SORAD\Service\Laravel\GenericService;
use Priskz\SORAD\Entity\Service\Identifier\Laravel\ServiceProvider as IdentifierServiceProvider;
use Priskz\SORAD\Entity\Service\Reference\Laravel\ServiceProvider as ReferenceServiceProvider;

class GenericEntityService extends GenericService
{
    /**
     * @property string $entityType
     */
	protected static $entityType = null;

    /**
     * @property array $aggregate
     */
	protected $aggregate;

    /**
     * @property array $configuration
     */
	protected $configuration = [];

    /**
     * @property array $core
     */
	protected $core = [
		'configuration' => 
		[
			'GET' => [
				'keys'  => [
					'filter', 'sort', 'field', 'embed',
				],
				'rules' => [
					'filter.*.field'  => '',
					'sort.*.field'    => '',
					'field'           => '',
					'embed'           => '',
					'context'         => []
				],
				'defaults' => [
					'filter' => [],
					'sort'   => [],
					'field'  => [],
					'embed'  => [],
				],
			],
			'DATA'      => [
				'keys'  => [
					'uuid', 'filter', 'sort', 'field', 'embed',
				],
				'rules' => [
					'uuid'            => '',
					'filter.*.field'  => '',
					'sort.*.field'    => '',
					'field'           => '',
					'embed'           => '',
					'context'         => []
				],
				'defaults' => [
					'filter' => [],
					'sort'   => [],
					'field'  => [],
					'embed'  => [],
				],
			],
			'CREATE' => [
				'keys'  => [
					'uuid',
				],
				'rules' => [
					'uuid'        => 'required',
					'context'     => []
				],
				'defaults'        => [],
			],
			'DELETE' => [
				'keys'  => [
					'uuid',
				],
				'rules' => [
					'uuid'        => 'required',
					'context'     => []
				],
				'defaults'        => [],
			],
			'PURGE' => [
				'keys'  => [
					'uuid',
				],
				'rules' => [
					'uuid'        => 'required',
					'context'     => []
				],
				'defaults'        => [],
			],
			'REFERENCE' => [
				'keys'  => [
					'uuid', 'reference',
				],
				'rules' => [
					'uuid'       => '',
					'reference'  => '',
					'context'    => []
				],
				'defaults'       => [],
			],
			'DELETE_REFERENCE' => [
				'keys'  => [
					'uuid', 'reference',
				],
				'rules' => [
					'uuid'       => '',
					'reference'  => '',
					'context'    => []
				],
				'defaults'       => [],
			],
			'PERSIST' => [
				'keys'  => [
					'persist',
				],
				'rules' => [
					'persist'  =>  'required',
					'context'  =>  []
				],
				'defaults'     =>  [],
			],
		]
	];

	/**
	 *	Constructor
	 */
	public function __construct($alias, $processor, $aggregate)
	{
		parent::__construct($alias, $processor);
		$this->setAggregate($aggregate);
	}

	/**
	 * Persist
	 *
	 * @param  array  $data
	 * @return Payload
	 */
	public function persist($data)
	{
		// Process the given data.
		$processPayload = $this->process(__FUNCTION__, $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		// Build the method needed to persist.
		$method = $this->parseMethod($data['persist']);

		return $this->$method($data + $processPayload->getData());
	}

	/**
	 * @todo: Current data() and get() exist as duplicate functionality.
	 * 
	 * Data
	 *
	 * @param  array  $data
	 * @return Payload
	 */
	public function data($data = [])
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		return $this->aggregate[self::getEntityType()]->get(
			$processPayload->getData()['filter'],
			$processPayload->getData()['sort'],
			$processPayload->getData()['field'],
			$processPayload->getData()['embed']
		);
	}

	/**
	 * @todo: Current data() and get() exist as duplicate functionality.
	 * 
	 * Get
	 * 
	 * @param  array  $data
	 * @return Payload
	 */
	public function get($data = [])
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		return $this->aggregate[self::getEntityType()]->get(
			$processPayload->getData()['filter'],
			$processPayload->getData()['sort'],
			$processPayload->getData()['field'],
			$processPayload->getData()['embed']
		);
	}

	/**
	 * Create
	 *
	 * @param  array  $data
	 * @return Payload
	 */
	public function create($data)
	{
		// @todo: Implement processing
		$processPayload = new Payload($data, 'valid'); // @todo: Super temporary WIP

		// // Process data given.
		// $processPayload = $this->process(__FUNCTION__, $data);

		// if($processPayload->getStatus() != 'valid')
		// {
		// 	return $processPayload;
		// }

		// Create the entity with the given data.
		$entityPayload = $this->aggregate[self::getEntityType()]->create($processPayload->getData());

		// No need to procede if dynamic content was not created.
		if($entityPayload->getStatus() != 'created')
		{
			return $entityPayload;
		}

		// Create the newly created entity's EntityIdentifier.
		$identifierPayload = $this->createIdentifier($entityPayload->getData());

		if($identifierPayload->getStatus() != 'created')
		{
			return $identifierPayload;
		}

		// @todo: The following currently adds an additional query just for the ease of getting the full entity to return.
		$createdEntityPayload = $this->getOneByUuid($entityPayload->getData()->getUuid());

		if($createdEntityPayload->getStatus() !== 'found')
		{
			return $createdEntityPayload;
		}

		return new Payload($createdEntityPayload->getData(), $entityPayload->getStatus());
	}

	/**
	 * Update
	 *
	 * @param  array  $data
	 * @param  mixed  $entity
	 * @return Payload
	 */
	public function update($data, $entity = null)
	{
		if(is_null($entity) && array_key_exists('uuid', $data))
		{
			// Pull the uuid value out and set it as the entity.
			$entity = key($data['uuid']);

			// Remove the outer array layer.
			$data = $data['uuid'][key($data['uuid'])];
		}

		// Update the outer most entity.
		$updatePayload = $this->updateByType($data, $entity);

		if($updatePayload->getStatus() != 'updated')
		{
			$updatePayload;
		}

		// Update referenced entities.
		if(array_key_exists('ref', $data))
		{
			// Init
			$referenceUpdatePayload = [];

			// Currently only way layer deep, @todo: add recursion @todo: requires uuids or entity only.
			foreach($data['ref'] as $referenceUuid => $referenceData)
			{
				// Update referenced entities. @todo: Embedded updates failures will go silently.
				$referenceUpdatePayload[] = $this->updateByType($referenceData, $referenceUuid);
			}
		}

		// @todo: The following currently adds an additional query just for the ease of getting the full entity to return.
		$updatedEntityPayload = $this->getOneByUuid($updatePayload->getData()->getUuid());

		if($updatedEntityPayload->getStatus() !== 'found')
		{
			return $updatedEntityPayload;
		}

		return new Payload($updatedEntityPayload->getData(), $updatePayload->getStatus());
	}

	/**
	 * Delete Entity.
	 *
	 * @param  mixed  $data
	 * @return Payload
	 */
	public function delete($data)
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		// Find Entity(s).
		$identifierPayload = $this->aggregate[IdentifierServiceProvider::getProviderKey()]->get([['field' => 'uuid', 'value' => $processPayload->getData()['uuid'], 'operator' => '=', 'or' => false]]);

		if($identifierPayload->getStatus() != 'found')
		{
			return $identifierPayload;
		}

		// Perform delete.
		$deletePayload = $this->aggregate[self::getEntityType()]->delete($identifierPayload->getData()->pluck('entity_id')->toArray());

		if($deletePayload->getStatus() != 'deleted')
		{
			return new Payload($identifierPayload->getData(), $deletePayload->getStatus());
		}

		return new Payload($identifierPayload->getData(), $deletePayload->getStatus());
	}

	/**
	 * Purge Entity.
	 *
	 * @param  mixed  $data
	 * @return Payload
	 */
	public function purge($data)
	{
		// Process data given.
		$processPayload = $this->process(__FUNCTION__, $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		// Find Entity(s).
		$identifierPayload = $this->aggregate[IdentifierServiceProvider::getProviderKey()]->get([['field' => 'uuid', 'value' => $processPayload->getData()['uuid'], 'operator' => '=', 'or' => false]]);

		if($identifierPayload->getStatus() != 'found')
		{
			return $identifierPayload;
		}

		// Perform purge of Entity.
		$entityPurgePayload = $this->aggregate[self::getEntityType()]->purge($identifierPayload->getData()->pluck('entity_id')->toArray());

		if($entityPurgePayload->getStatus() != 'purged')
		{
			return new Payload($identifierPayload->getData(), $entityPurgePayload->getStatus());
		}

		// Perform purge of identifier.
		$identifierPurgePayload = $this->aggregate[IdentifierServiceProvider::getProviderKey()]->purge($identifierPayload->getData()->pluck('id')->toArray());

		return new Payload($identifierPayload->getData(), $entityPurgePayload->getStatus());
	}

	/**
	 * Reference.
	 *
	 * @param  array  $data
	 * @return Payload
	 */
	public function reference($data)
	{
		// Process the given data.
		$processPayload = $this->process(__FUNCTION__, $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		$entityPayload = $this->getOneByUuid($data['uuid']);

		if($entityPayload->getStatus() != 'found')
		{
			return $entityPayload;
		}
		
		// Either get an existing entity with uuid given or create a new one with data given.
		if(array_key_exists('uuid', $processPayload->getData()['reference']))
		{
			// @todo: Make this more consistent - returns a collection.
			$referencePayload = $this->getOneByUuid($processPayload->getData()['reference']['uuid']);

			if($referencePayload->getStatus() != 'found')
			{
				return $referencePayload;
			}

			// @todo: Make this more consistent - extra instanciating.
			$referencePayload = new Payload($referencePayload->getData(), 'found');
		}
		elseif(array_key_exists('reference_type', $processPayload->getData()['reference']))
		{
			$referencePayload = $this->create(['entity_type' => $processPayload->getData()['reference']['reference_type']] + $processPayload->getData()['reference']);

			if($referencePayload->getStatus() != 'created')
			{
				return $referencePayload;
			}
		}
		else
		{
			dd("@todo: Implement proper validation to avoid getting to this point");
		}

		// Finally, create the reference.
		$referenceData = [];
		$referenceData['entity_uuid']    = $entityPayload->getData()->first()->getUuid();
		$referenceData['entity_type']    = $entityPayload->getData()->first()->getType();
		$referenceData['entity_id']      = $entityPayload->getData()->first()->getKey();
		$referenceData['reference_uuid'] = $referencePayload->getData()->first()->getUuid();
		$referenceData['reference_type'] = $referencePayload->getData()->first()->getType();
		$referenceData['reference_id']   = $referencePayload->getData()->first()->getKey();

		$newReferencePayload = $this->createReference($referenceData);

		if($newReferencePayload->getStatus() != 'created')
		{
			return $newReferencePayload;
		}

		// @todo: What do we actually want to return here?
		return new Payload($entityPayload->getData()->first(), 'created');
	}

	/**
	 * Delete Reference
	 * 
	 * @return Payload
	 */
	public function deleteReference($data)
	{
		// Process the given data.
		$processPayload = $this->process('DELETE_REFERENCE', $data);

		if($processPayload->getStatus() != 'valid')
		{
			return $processPayload;
		}

		return $this->aggregate[ReferenceServiceProvider::getProviderKey()]->delete(['entity_uuid' => $data['uuid'], 'reference_uuid' => $data['reference']]);
	}

	/**
	 * Get Entity by key.
	 *
	 * @param  mixed  $key - string, array, or object
	 * @return Payload
	 */
	public function getOneByKey($key)
	{
		// Note: this switch exists like this because PHP does not have "traditional method overloading".
		switch(gettype($key))
		{
			// Use EntityIdentifier to retrieve.
			case 'object':
				return $this->getOneByIdentifier($key);
			break;

			// Use a composite key to retrieve.
			case 'array':
				return $this->getOneByCompoundKey($key);
			break;

			// Use an actual Identifier string to retrieve.
			case 'string':
				return $this->getOneByUuid($key);
			break;

			// Use Entity to retrieve.
			default:
				return new Payload(null, 'invalid_key');
		}
	}

	/**
	 * Update Entity via overload.
	 *
	 * @param  array  $data
	 * @param  mixed  $entity
	 * 
	 * @return Payload
	 */
	protected function updateByType($data, $entity)
	{
		// Note: this switch exists like this because PHP does not have "traditional method overloading".
		switch(gettype($entity))
		{
			// UUID string
			case 'string':
				$entityPayload = $this->getOneByUuid($entity);

				if($entityPayload->getStatus() != 'found')
				{
					return $entityPayload;
				}

				// Update the Entity itself.
				return $this->updateByEntity($data, $entityPayload->getData()->first());
			break;

			// Use an Object to update.
			case 'object':
				switch($entity->getType())
				{
					// Use an EntityIdentifier to update.
					case IdentifierServiceProvider::getProviderKey():

						// Update the Entity itself.
						return $this->updateByIdentifier($entity, $data);
					break;

					// Use an Entity to update.
					default:
						// Make sure this service is configured to use the given entity's aggregate.
						if(array_key_exists($entity->getType(), $this->getService()))
						{
							// Update the Entity itself.
							return $this->updateByEntity($data, $entity);
						}
						else
						{
							return new Payload(null, $entity->getType() . '_' . 'not_configured');
						}
				}
			break;

			// Use a compound key to update.
			case 'array':
				$entityPayload = $this->getOneByCompoundKey($entity);

				if($entityPayload->getStatus() != 'found')
				{
					return $entityPayload;
				}

				// Update the Entity itself.
				return $this->updateByEntity($data, $entityPayload->getData());
			break;

			default:
				return new Payload(null, 'invalid_entity');
		}
	}

	/* =====================================================
	 * Entity Persistence
	 * ================================================== */

	/**
	 * Update a Entity by object.
	 *
	 * @param  array   $data
	 * @param  Entity  $entity
	 * @return Payload
	 */
	protected function updateByEntity($data, $entity)
	{
		return $this->aggregate[$entity->getType(true)]->update($data, $entity);
	}

	/* =====================================================
	 * EntityIdentifier Persistence
	 * ================================================== */

	/**
	 * Update an Entity by Identifier.
	 *
	 * @param  EntityIdentifier $entityIdentifier
	 * @param  array            $data
	 * @return Payload
	 */
	protected function updateByIdentifier($identifier, $data)
	{
		return $this->aggregate[IdentifierServiceProvider::getProviderKey()]->update($identifier->getEntity(), $data);
	}

	/**
	 * Get an Entity by given EntityIdentifier.
	 *
	 * @param  EntityIdentifier $entityIdentifier
	 * @return Payload
	 */
	protected function getOneByIdentifier($entityIdentifier)
	{
		return $this->aggregate[IdentifierServiceProvider::getProviderKey()]->get([['field' => 'id', 'value' =>  $identifier->getEntityKey(), 'operator' => '=', 'or' => false]]);
	}

	/* =====================================================
	 * String/Array Persistence
	 * ================================================== */

	/**
	 * Get an Entity by it's UUID.
	 *
	 * @param  string  $uuid
	 * @return Payload
	 */
	public function getOneByUuid($uuid)
	{
		$identifierPayload = $this->aggregate[IdentifierServiceProvider::getProviderKey()]->get([['field' => 'uuid', 'value' =>  $uuid, 'operator' => '=', 'or' => false]]);

		if($identifierPayload->getStatus() != 'found')
		{
			return $identifierPayload;
		}

		return $this->aggregate[$this->formatEntityType($identifierPayload->getData()->first()->getEntityType())]->get([['field' => 'id', 'value' => $identifierPayload->getData()->first()->getEntityKey(), 'operator' => '=', 'or' => false]]);
	}

	/**
	 * Get an Entity by compound key.
	 *
	 * @param  array   $key
	 * @return Payload
	 */
	protected function getOneByCompoundKey($key)
	{
		$sanitizedKey = [];

		// We can can potentially accept an associaitve or standard array.
		if($this->isAssociativeArray($key))
		{
			if(array_key_exists('entity_type', $key) && array_key_exists('entity_id', $key))
			{
				$sanitizedKey['entity_type'] = $key['entity_type'];
				$sanitizedKey['entity_id']   = $key['entity_id'];
			}
			else
			{
				return new Payload(null, 'insufficient_entity_key');
			}
		}
		else
		{
			$sanitizedKey['entity_type'] = $key[0];
			$sanitizedKey['entity_id']   = $key[1];
		}

		return $this->aggregate[$this->formatEntityType($sanitizedKey['entity_type'])]->get([['field' => 'id', 'value' => $sanitizedKey['entity_id'], 'operator' => '=', 'or' => false]]);
	}

	/* =====================================================
	 * Helpers
	 * ================================================== */

	/**
	 * Create a new EntityIdentifier for a newly created Entity.
	 *
	 * @param  mixed $data EntityIdentifier create data.
	 * @return Payload
	 */
	public function createIdentifier($data)
	{
		// Get the data ready for creation.
		switch(gettype($data))
		{
			// Use an Object to update.
			case 'object':
				// Add entity type and key to creation data.
				$createData = ['entity_type' => $data->getType(), 'entity_id' => $data->getKey()];
			break;

			default:
				$createData = $data;
			break;
		}

		// Create the newly created entity's EntityIdentifier.
		return $this->aggregate[IdentifierServiceProvider::getProviderKey()]->create($createData);
	}

	/**
	 * Create Reference
	 *
	 * @param  mixed   $data
	 * @return Payload
	 */
	public function createReference($data)
	{
		// Get the data ready for creation.
		switch(gettype($data))
		{
			// Use an Object to update.
			case 'object':
				// Add entity type and key to creation data.
				$createData = ['entity_type' => $data->getType(), 'entity_id' => $data->getKey()];
			break;

			default:
				$createData = $data;
			break;
		}

		// Create the newly created entity's EntityIdentifier.
		return $this->aggregate[ReferenceServiceProvider::getProviderKey()]->create($createData);
	}

	/**
	 * Get aggregate(s).
	 *
	 * @return array
	 */
	protected function getAggregate()
	{
		return $this->aggregate;
	}

	/**
	 * Set aggregate(s).
	 *
	 * @param  array  $aggregate
	 * @return void
	 */
	protected function setAggregate(array $aggregate)
	{
		$this->aggregate = $aggregate;
	}

	/**
	 * Convert upper+snake case to lower-kebab case (LINE_ITEM to line-item).
	 *
	 * @param  string $upperSnakeCaseString
	 * @return string
	 */
	protected function formatEntityType($upperSnakeCaseString)
	{
		return strtolower(str_replace(['_'], '-', $upperSnakeCaseString));
	}

	/**
	 * Check whether the given array is an associative array.
	 *
	 * @param  array  $array
	 * @return bool
	 */
	protected function isAssociativeArray(array $array)
	{
		for (reset($array); is_int(key($array)); next($array));

		return ! is_null(key($array));
	}

	/**
	 * Parse the given string into method format.
	 *
	 * @param  string $string.
	 * 
	 * @return string
	 */
	protected function parseMethod($string)
	{
		return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($string)))));
	}

	/**
	 * Get the process configuration.
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
	 * Get Entity Type.
	 *
	 * @return string
	 */
	public static function getEntityType()
	{
		return static::$entityType; 
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