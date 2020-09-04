<?php

namespace Kuza\Krypton\Database\Predicates;

class Between extends Predicate {
        
        public function getExpression($param_prefix=null) {
           $this->expression = $this->left." BETWEEN '".$this->right[0]."' AND '". $this->right[1]."'";
           return $this->expression;
        }
}