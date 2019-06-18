<?php

namespace Kuza\Krypton\Database\Predicates;

class IsNotNull extends PredicateFunction
{
	public function __construct($value)
	{
		parent::__construct($value);
		$this->expression = ' NOT ISNULL('.$this->getValue().')';
	}
}