<?php

namespace Husky\UI;

use Husky\Configuration\View\ListView\Column\ColumnBase;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\BaseException;
use sugrob\OnPHP\Toolkit\ClosureResolver;
use sugrob\OnPHP\Toolkit\Delegate;

final class ListColumnValueRenderer
{
	/**
	 * @return ListColumnValueRenderer
	 */
	public static function create() {
		return new self;
	}

	public function render(Prototyped $subject, ColumnBase $column)
	{
		$value = $this->getValue($subject, $column);

		if ($column->getType()
			&& (
				!is_object($value)
				|| method_exists($value, '__toString')
			)
		) {
			settype($value, $column->getType());
		}

		if ($decorator = $this->resolveDecorator($subject, $column, $value)) {
			$value = $decorator->run();
		}

//		$array = array(
//			'value' => $value
//		);

//		if ($handler = $this->resolveHandler($subject, $column)) {
//			if ($context = $handler->run()) {
//				$array['context'] = $context;
//			}
//		}

//		return $array;

		return $value;
	}

	private function getValue(Prototyped $subject, ColumnBase $column) {
		try {
			if ($dataProviderSignature = $column->getDataProvider()) {
				try {
					$result = ClosureResolver::resolveStaticMethod(
							$dataProviderSignature,
							array($subject)
						)->
						run();

					if (is_object($result) && $column->getMethod()) {
						return $this->resolveMethod($result, $column);
					}

					return $result;
				} catch (BaseException $e) {}
			}

			if ($method = $column->getMethod()) {
				return $this->resolveMethod($subject, $column);
			}

			$name = $column->getName();

			if ($subject->proto()->isPropertyExists($name)) {
				$property = $subject->proto()->getPropertyByName($name);
				$getter = $property->getGetter();

				return Delegate::create(array($subject, $getter))->run();
			}
		} catch (BaseException $e) {}
	}

	private function resolveMethod(
		Prototyped $subject,
		ColumnBase $column
	) {
		return ClosureResolver::resolveMethod(
				$subject,
				$column->getMethod()
			)->
			run();
	}

	/**
	 * @param Prototyped $subject
	 * @param ColumnBase $column
	 * @param $value
	 * @return Delegate|null
	 */
	private function resolveDecorator(
		Prototyped $subject,
		ColumnBase $column,
		$value
	) {
		if ($decorator = $column->getDecorator()) {
			return Delegate::create(
				$decorator,
				array($subject, $column, $value)
			);
		}
	}

	/**
	 * @param Prototyped $subject
	 * @param ColumnBase $column
	 * @return Delegate|null
	 */
	private function resolveHandler(
		Prototyped $subject,
		ColumnBase $column
	) {
		if ($handler = $column->getHandler()) {
			return Delegate::create(
				$column->getHandler(),
				array($subject, $column)
			);
		}
	}
}