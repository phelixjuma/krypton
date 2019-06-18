<?php

namespace Kuza\Krypton\Database\Predicates;

class NestedOr extends Predicate
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