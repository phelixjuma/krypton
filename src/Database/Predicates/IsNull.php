<?php

namespace Kuza\Krypton\Database\Predicates;

class IsNull extends PredicateFunction
{
	public function __construct($value)
	{
		parent::__construct($value);
		$this->expression = ' '.$this->getValue().' IS NULL';
	}
}