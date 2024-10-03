<?php

namespace Kuza\Krypton\Database\Predicates;

class Literal {
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function __toString() {
        // Directly return the value. Depending on security requirements,
        // you might want to handle escaping or quoting here.
        return "'" . str_replace("'", "''", $this->value) . "'";
    }
}
