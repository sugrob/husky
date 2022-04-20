<?php

namespace Husky\Logic;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Base\StaticFactory;
use OnPHP\Core\Logic\Expression;
use OnPHP\Core\Logic\LogicalObject;
use OnPHP\Core\OSQL\DBField;
use OnPHP\Core\OSQL\DBValue;

class FilterExpressionBuilder extends StaticFactory
{
	public static function build(Prototyped $subject, $propertyName, $value, FilterLogicEnumeration $logic): LogicalObject
	{
		$property = $subject->proto()->getPropertyByName($propertyName);
		$table = $subject->dao()->getTable();
		$field = DBField::create($property->getColumnName(), $table);

		switch ($property->getType()) {
			case 'string':
				$value = strip_tags(stripslashes($value));
				$exp = $logic->makeLogicalExpression($field, $value);
				break;

			case 'identifier':
			case 'integerIdentifier':
			case 'enumeration':
				if ($value instanceof Identifiable) {
					$exp = $logic->makeLogicalExpression(
						$field,
						DBValue::create($value->getId())
					);
				} else {
					$exp = $logic->makeLogicalExpression($field, (int)$value);
				}
				break;


			case 'timestamp':
				if ($value instanceof TimestampRange
					|| $value instanceof DateRange
				) {
					if ($value->getStart() && $value->getEnd()) {
						$exp = Expression::between(
							$field,
							$value->getStart()->toString(),
							$value->getEnd()->toString()
						);
					} elseif ($value->getStart()) {
						$exp = Expression::gtEq(
							$field,
							$value->getStart()->toString()
						);
					} elseif ($value->getEnd()) {
						$exp = Expression::ltEq(
							$field,
							$value->getEnd()->toString()
						);
					}
				} else {
					$exp = $logic->makeLogicalExpression($field, $value->toString());
				}
				break;

			default:
				if ($value instanceof SingleRange) {
					return Expression::between($field, $value->getStart()->toString(), $value->getEnd()->toString());
				} elseif ($value instanceof Stringable) {
					return $logic->makeLogicalExpression($field, $value->toString());
				}

				Assert::isFalse(
					is_object($value),
					'Cant use object in Criteria condition, only string accepted'
				);

				$exp = $logic->makeLogicalExpression($field, $value);
		}

		return $exp;
	}

}