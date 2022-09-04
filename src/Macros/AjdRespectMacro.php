<?php 

namespace AjdRespect\Macros;

use AJD_validation\Contracts\CanMacroInterface;
use Respect\Validation\Validator;

class AjdRespectMacro implements CanMacroInterface
{

	/**
     * Returns an array of method name to be made as ajd validation macro.
     *
     * @return array
     *
     */
	public function getMacros()
	{
		return [
			'getRespectValidator',
		];
	}

	 /**
     * Returns repect validator instance.
     *
     * @return \Respect\Validation\Validator
     */
	public function getRespectValidator()
	{
		$that = $this;
		return function() use ($that)
		{
			return new Validator;
		};
		
	}
}