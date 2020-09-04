<?php

namespace Kuza\Krypton\Database\Predicates;

class Match extends Predicate
{
        public function getExpression($param_prefix=null)
        {
          	$param = ($param_prefix? $param_prefix.$this->column_alias : $this->right);          	
           	$this->expression = ' ( MATCH ('.$this->left.')  AGAINST ('.$param.' IN BOOLEAN MODE) ) ';
           	return $this->expression;
        }
}