<?php

namespace Kuza\Krypton\Database\Predicates;

class PredicateFunction extends Predicate{
	
	public function __construct($value)
	{
		$this->value = $value;		
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
}