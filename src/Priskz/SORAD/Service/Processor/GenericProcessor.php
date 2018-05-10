<?php

namespace Priskz\SORAD\Service\Processor;

use Priskz\Payload\Payload;
use Priskz\SORAD\Service\Processor\ProcessorInterface;

/**
 * A Processor is a simple class used to validate/clean data.
 */
class GenericProcessor implements ProcessorInterface
{
	/**
	 * Custom Validation Messages
	 *  
	 * @var $messages
	 */
	protected $messages = [
		'EXAMPLE' => 'Custom validation example failure message.',
	];

	/**
	 * @var  \SORAD\Laravel\Validator
	 */
	protected $validator;

	/**
	 * Constructor
	 *
	 * @param  \SORAD\Action\Processor\Validator  $validator
	 */
	public function __construct($validator)
	{
		$this->validator = $validator;
	}

	/**
	 * Process the given data against the given rules and useable data keys.
	 *
	 * @param  array  $data
	 * @param  array  $keys
	 * @param  array  $rules
	 * @param  array  $defaults
	 * 
	 * @return Payload\Payload
	 */
	public function process($data, $keys, $rules, $defaults = null)
	{
		// Set any configured default data values.
		$processData = $this->processDefaults($data, $defaults);

		//  Validate data based on the given context of the data.
		$validateContextPayload = $this->validateContext($processData, $rules);

		// Return sanitized data if no validation errors exist.
		if( ! $validateContextPayload->isStatus(Payload::STATUS_VALID))
		{
			return $validateContextPayload;
		}

		// Extract only the data that we want to validate.
		$processData = array_intersect_key($processData + $validateContextPayload->getData(), array_flip($keys));

		// Validate the given data and return.
		return $this->validator->validate($processData, $rules, $this->messages);
	}

	/**
	 * Process the default values for the given defaults configuration.
	 * 
	 * @param  string $defaults Type of function to be processed.
	 * @param  array  $data    Data to be processed.
	 * 
	 * @return array
	 */
	public function processDefaults($data, $defaults = [])
	{
		if(is_array($defaults))
		{
			// Replace default values with given values.
			$defaults = $data + $defaults;
			
			// Iterate each default key set.
			foreach($defaults as $key => $value)
			{
				if( ! isset($data[$key]))
				{
					// If value is not false, use that directly otherwise compute it.
					if($value !== false && empty($data[$key]))
					{
						$data[$key] = $value;
					}
					// Otherwise, do some custom processing.
					elseif(empty($data[$key]))
					{
						switch($key)
						{
							default:
							break;
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 *  Validate data based on the given context of the data AKA Custom Validation.
	 *
	 * @param  array  $data   Data to be processed.
	 * @param  string $rules  Custom context validation rules.
	 * 
	 * @return Payload\Payload
	 */
	public function validateContext($data, $rules)
	{
		// Init base return values.
		$returnStatus = 'valid';
		$returnData   = null;

		// We only need to validate context if context rules are configured.
		if(array_key_exists('context', $rules))
		{
			foreach($rules['context'] as $rule)
			{
				switch($rule)
				{
					case 'EXAMPLE':
						if(1 !== 2)
						{
							$returnStatus = $rule;
						}
					break;

					default:
					break;
				}
			}
		}

		// Check for any configured custom validation messages.
		if($returnStatus != 'valid')
		{
			if(array_key_exists($returnStatus, $this->messages))
			{
				$data = $this->messages[$returnStatus];
			}
		}

		return new Payload($data, $returnStatus);
	}
}