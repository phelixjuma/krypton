<?php

namespace Kuza\Krypton\Database\Predicates;

class In extends Predicate {

    public function getExpression($param_prefix=null) {
        //$inQuery = implode(",", $this->right);
        $inQuery = "";
        $i = 1;
        $size = sizeof($this->right);
        foreach ($this->right as $item) {
            if ($i == $size) {
                $inQuery .= "'".$item."'";
            } else {
                $inQuery .= "'".$item."',";
            }
            $i++;
        }
        $this->expression = $this->left. " IN ($inQuery)";
        return $this->expression;
    }
}