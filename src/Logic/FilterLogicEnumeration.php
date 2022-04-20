<?php

namespace Husky\Logic;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Enumeration;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Logic\BinaryExpression;
use OnPHP\Core\Logic\Expression;
use OnPHP\Core\Logic\LogicalBetween;
use OnPHP\Core\OSQL\DBValue;

class FilterLogicEnumeration extends Enumeration
{
	const ANY		    	= '1';
	const EQUALS			= '2';
	const NOT_EQUALS		= '3';
	const GREATER	    	= '4';
	const GREATER_OR_EQUALS	= '5';
	const LOWER		        = '6';
	const LOWER_OR_EQUALS	= '7';
	const LIKE				= '8';
	const NOT_LIKE			= '9';
	const SIMILAR   		= '10';
	const NOT_SIMILAR   	= '11';
	const BETWEEN       	= '12';
	const IN              	= '13';
	const NOT_IN            = '14';
	const IS_TRUE           = '15';
	const IS_FALSE          = '16';
	const IS_NULL           = '17';
	const IS_EMPTY          = '19';

	protected $names = array(
		self::ANY   			=> 'any',
		self::EQUALS			=> 'equals',
		self::NOT_EQUALS		=> 'not_equals',
		self::GREATER			=> 'greater',
		self::GREATER_OR_EQUALS	=> 'greater_or_equals',
		self::LOWER	    		=> 'lower',
		self::LOWER_OR_EQUALS	=> 'lower_or_equals',
		self::LIKE              => 'like',
		self::NOT_LIKE          => 'not_like',
		self::SIMILAR           => 'similar',
		self::NOT_SIMILAR       => 'not_similar',
		self::BETWEEN           => 'between',
		self::IN                => 'in',
		self::NOT_IN            => 'not_in',
		self::IS_TRUE           => 'is_true',
		self::IS_FALSE          => 'is_false',
		self::IS_NULL           => 'is_null',
		self::IS_EMPTY          => 'is_empty',
	);

	/**
	 * @param integer $id
	 * @return FilterLogicEnumeration
	 */
	public static function create($id)
	{
		return new self($id);
	}

	public static function getAnyId()
	{
		return self::EQUALS;
	}

	/**
	 * @param $name
	 * @return FilterLogicEnumeration
	 * @throws WrongArgumentException
	 */
	public function createByName($name)
	{
		foreach ($this->getNameList() as $id => $value) {
			if ($name == $value) {
				return self::create($id);
			}
		}

		throw new WrongArgumentException(
			get_class($this) . ' knows nothing about such name == '.$name
		);
	}

	public function makeLogicalExpression($filed, $first = null, $second = null) {
		switch ($this->id) {
			case self::ANY:
				return Expression::isTrue(DBValue::create(TRUE));

			case self::EQUALS:
				Assert::isNotEmpty($first);
				return new BinaryExpression($filed, $first, BinaryExpression::EQUALS);

			case self::NOT_EQUALS:
				return new BinaryExpression($filed, $first, BinaryExpression::NOT_EQUALS);

			case self::GREATER:
				return new BinaryExpression($filed, $first, BinaryExpression::GREATER_THAN);

			case self::GREATER_OR_EQUALS:
				return new BinaryExpression($filed, $first, BinaryExpression::GREATER_OR_EQUALS);

			case self::LOWER:
				return new BinaryExpression($filed, $first, BinaryExpression::LOWER_THAN);

			case self::LOWER_OR_EQUALS:
				return new BinaryExpression($filed, $first, BinaryExpression::LOWER_OR_EQUALS);

			case self::LIKE:
				return new BinaryExpression($filed, $first, BinaryExpression::LIKE);

			case self::NOT_LIKE:
				return new BinaryExpression($filed, $first, BinaryExpression::NOT_LIKE);

			case self::SIMILAR:
				return new BinaryExpression($filed, "%".$first."%", BinaryExpression::LIKE);

			case self::NOT_SIMILAR:
				return new BinaryExpression($filed, "%".$first."%", BinaryExpression::NOT_LIKE);

			case self::BETWEEN:
				return new LogicalBetween($filed, $first, $second);

			case self::IN:
				Assert::isArray($first, __CLASS__.": Second argument must be an array");
				return Expression::in($filed, $first);

			case self::NOT_IN:
				Assert::isArray($first, __CLASS__.": Second argument must be an array");
				return Expression::notIn($filed, $first);

			case self::IS_TRUE:
				return Expression::isTrue($filed);

			case self::IS_FALSE:
				return Expression::isFalse($filed);

			case self::IS_NULL:
				return Expression::isNull($filed);

			case self::IS_EMPTY:
				return Expression::expOr(
					Expression::isNull($filed),
					Expression::eq($filed, "")
				);
		}
	}
}