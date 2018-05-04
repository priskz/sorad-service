<?php

namespace Priskz\SORAD\Service\Laravel;

use App;
use Priskz\SORAD\Service\Processor\Laravel\Processor;

class GenericService
{
	/**
	 * @property Priskz\SORAD\Service\Processor\Laravel\Processor
	 */
	protected $alias;

	/**
	 * @property Priskz\SORAD\Service\Processor\Laravel\Processor
	 */
	protected $processor;

	/**
	 *	Constructor
	 */
	public function __construct($alias, $processor = null)
	{
		$this->alias = $alias;

		// If a custom processor is not given then use the default generic Laravel\Processor.
		if($processor === null)
		{
			$processor = App::make(Processor::class);
		}

		$this->processor = $processor;
	}

	/**
	 * Get Alias.
	 *
	 * @return string
	 */
	public function getAlias()
	{
		return $this->alias;
	}
}