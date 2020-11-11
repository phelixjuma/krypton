<?php

namespace Kuza\Krypton\Database\Predicates;

class JsonKeyEquals extends Predicate {

    public function getExpression($param_prefix=null) {
        $this->expression = "JSON_EXTRACT({$this->left[0]}, '$.{$this->left[1]}') = '$this->right'";
        return $this->expression;
    }
}