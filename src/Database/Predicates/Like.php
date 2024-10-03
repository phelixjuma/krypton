<?php

namespace Kuza\Krypton\Database\Predicates;

class Like extends Predicate {

    public function getExpression($param_prefix=null) {
        //$this->expression = $this->left.' LIKE '.($param_prefix? $param_prefix.$this->column_alias : $this->right);
        //return $this->expression;

        // Check if left or right side is an instance of Literal
        $leftSide = $this->left instanceof Literal ? (string)$this->left : $this->left;
        $rightSide = $this->right instanceof Literal ? (string)$this->right : ($param_prefix ? $param_prefix.$this->column_alias : $this->right);

        $this->expression = $leftSide . ' LIKE ' . $rightSide;
        return $this->expression;
    }
}
