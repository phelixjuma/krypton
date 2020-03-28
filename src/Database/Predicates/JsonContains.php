<?php

namespace Kuza\Krypton\Database\Predicates;

class JsonContains extends Predicate {
        
        public function getExpression($param_prefix=null) {

            $expressions = [];

            foreach ($this->right as $exp) {
                $expressions[] = "JSON_CONTAINS($this->left, '".$exp['value']."' , '". $exp['field']."') = 1";
            }

            $this->expression = implode(" AND ", $expressions);

            return $this->expression;
        }
}