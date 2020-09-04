<?php

namespace Kuza\Krypton\Database\Predicates;

class DateDiffGreaterThanOrEqualTo extends Predicate {
        public function getExpression($param_prefix=null) {

          	$fields = explode(',',$this->left);

           	$this->expression = "DATEDIFF({$fields[0]}, $fields[1]) >= {$this->right}";

           	return $this->expression;
        }
}