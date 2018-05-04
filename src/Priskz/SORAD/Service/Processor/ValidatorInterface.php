<?php

namespace Priskz\SORAD\Service\Processor;

/**
 * A Validator is just a way to check to see if an array of values
 * meet certain criteria, such as existence of certain bits of
 * data, data being of certain types, etc.
 */
interface ValidatorInterface
{
	/**
	 * Validate input against the specified rules
	 *
	 * @param  array  $data
	 * @param  array  $rules
	 * @param  array  $messages
	 * @return Payload
	 */
	function validate($data, $rules, $messages);
}