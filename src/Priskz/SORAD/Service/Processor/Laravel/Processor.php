<?php

namespace Priskz\SORAD\Service\Processor\Laravel;

use App;
use Priskz\SORAD\Service\Processor\Laravel\Validator;
use Priskz\SORAD\Service\Processor\GenericProcessor;

class Processor extends GenericProcessor
{
	/**
	 * Construct
	 * 
	 * @param  SORAD\Action\Processor\Validator  $validator
	 */
	public function __construct($validator = null)
	{
		// If a custom validator is not given then use the default generic Laravel\Validator.
		if($validator === null)
		{
			$validator = App::make(Validator::class);
		}

		parent::__construct($validator);
	}
}