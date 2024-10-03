<?php

namespace Kuza\Krypton\Database\Predicates;

abstract class Predicate {
	
	/*
	 * @desc first argument of comparison, preferrably, the column name
	 */
	protected $left;
	
	/*
	 * @desc second argument of comparison, preferrably, the column value
	 */
	protected $right;
	
	/*
	 * @desc value expected from expression
	 */
	protected $value;
	
	/*
	 * @desc description of expression
	 */
	protected $expression;
	
	/*
	 * @desc column alias string
	 */
	protected $column_alias;
	
	public function __construct($left, $right,$column_alias) {

        $left = $left instanceof Literal ? (string)$left : $left;
        $right = $right instanceof Literal ? (string)$right : $right;

        $this->left = $left;
		$this->right = $right;
		$this->value = $this->right;				
		if(is_object($left)!==true && is_object($right)!==true)		
		{
			$this->column_alias = $column_alias;
			$this->expression = (is_array($left)!=true && is_array($right)!=true)? $this->left . '=' . $this->right : null;
		}
	}
	public function getExpression() {
		return $this->expression;
	}
	public function getValue() {
		return $this->value;
	}
	public function getAlias() {
		return $this->column_alias;
	}
}
