<?php

namespace Kuza\Krypton\Database\Predicates;

class JsonContains extends Predicate {
        
        public function getExpression($param_prefix=null) {
           $this->expression = "JSON_CONTAINS('$this->left', '".$this->right[0]."' , '". $this->right[1]."') = 1";
           return $this->expression;
        }
}