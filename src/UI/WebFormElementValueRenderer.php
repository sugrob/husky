<?php

namespace Husky\UI;

use Husky\Configuration\Component\WebForm\Element\ElementBase;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Identifiable;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Form;
use sugrob\OnPHP\Toolkit\ClosureResolver;
use sugrob\OnPHP\Toolkit\Delegate;

class WebFormElementValueRenderer
{
	/**
	 * @return WebFormElementValueRenderer
	 */
	public static function create() {
		return new self;
	}

	public static function render(
		Form $form,
		ElementBase $element
	) {
		Assert::isTrue(
			$form->exists("id"),
			"Can't render from without 'id' field"
		);

		$subject = $form->getValue("id");

		if ($subject) {
			$value = self::getValue($form, $element, $subject);
		} else {
			$value = $form->getValue($element->getName());
		}

		if ($element->getType()) {
			settype($value, $element->getType());
		}
		
		if ($subject
			&& $decorator = self::resolveDecorator($element, $subject, $value)
		) {
			$value = $decorator->run();
		}
		
		if (is_object($value)) {
			/**
			* FIX ME 
			*/
			if (!$value instanceof OneToManyLinked) {
				$value = self::valueToString($value, $element);
			}
		}

		$array = array(
			'value' => $value,
			'context' => [],
		);
		
		if ($subject
			&& $handler = self::resolveHandler($form, $element, $subject, $value)
		) {
			if ($context = $handler->run()) {
				$array['context'] = $context;
			}
		}

		return $array;
	}
	
	private static function getValue(
		Form $form,
		ElementBase $element,
		Prototyped $subject
	) {
		try {
			if ($subject->getId()) {
				if ($method = $element->getMethod()) {
					return ClosureResolver::resolveMethod(
						$subject,
						$method
					)->run();
				}
			}

			$name = $element->getName();

			if ($form->exists($name)
				&& $form->getValue($name)
			) {
				return $form->getValue($name);
			}
			
			if ($subject->proto()->isPropertyExists($name)) {
				$property = $subject->proto()->getPropertyByName($name);
				$getter = $property->getGetter();
				
				return Delegate::create(array($subject, $getter))->run();
			}
		} catch (WrongArgumentException $e) {}
	}
	
	private static function resolveDecorator(
		ElementBase $element,
		Prototyped $subject,
		/* mixed */ $value
	) {
		if (!$element->getDecorator())
			return null;
		
		return Delegate::create(
			$element->getDecorator(),
			array($subject, $element, $value)
		);
	}
	
	private static function resolveHandler(
		Form $form,
		ElementBase $element,
		Prototyped $subject,
		$value
	) {
		if (!$element->getDataProvider())
			return null;

		return Delegate::create(
			$element->getDataProvider(),
			array($form, $element, $subject, $value)
		);
	}
	
	private static function valueToString($value)
	{
		if ($value instanceof Identifiable) {
			return $value->getId();
		} elseif($value instanceof Date) {
			return $value->toString();
		} elseif ($value instanceof Stringable) {
			return $value->toString();
		}

		Assert::isUnreachable(
			"Don't know how make string from object:".get_class($value)
		);
	}
}

?>