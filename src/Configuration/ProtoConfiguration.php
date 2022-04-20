<?php

namespace Husky\Configuration;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\WrongArgumentException;

abstract class ProtoConfiguration
{
	/**
	 * @var Prototyped
	 */
	protected $subject;

	abstract public function loadXML($file);

	public function __construct($subject) {
		if (is_object($subject)) {
			Assert::isInstance(
				$subject,
				Prototyped::class,
				"Prototyped subject expected, ".get_class($subject)." given"
			);

			$this->subject = $subject;
		} else if (is_string($subject)) {
			Assert::isInstance(
				$subject,
				Prototyped::class,
				"Prototyped subject expected, {$subject} given"
			);

			$this->subject = new $subject;
		} else {
			throw new WrongArgumentException('Strange object given. Prototyped expected.');
		}
	}

	/**
	 * @return Prototyped
	 */
	protected function getSubject() {
		return $this->subject;
	}
}