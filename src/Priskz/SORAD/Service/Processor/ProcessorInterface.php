<?php

namespace Priskz\SORAD\Service\Processor;

use Priskz\SORAD\Service\ValidatorInterface;

/**
 *  A Processor is a simple class used to validate/clean data.
 */
interface ProcessorInterface
{
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
	public function process($data, $keys, $rules, $defaults);
}