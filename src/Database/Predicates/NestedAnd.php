<?php

namespace Kuza\Krypton\Database\Predicates;

class NestedAnd extends Predicate
{       	
       	public function __construct(array $values) 
       	{
       		$this->value = (array)$values;
       	}
       	
       	public function getValue()
       	{
       		return $this->value;
       	}
}