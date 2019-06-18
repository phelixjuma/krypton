<?php

namespace Kuza\Krypton\Database\Predicates;

class Equal extends Predicate
{
        public function getExpression($param_prefix=null)
        {
           $this->expression = $this->left.' = '.($param_prefix? $param_prefix.$this->column_alias : $this->right);
           return $this->expression;
        }
}