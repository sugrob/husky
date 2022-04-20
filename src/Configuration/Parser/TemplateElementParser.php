<?php

namespace Husky\Configuration\Parser;

use Husky\Configuration\ConfigurationPathResolver;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Main\Util\ClassUtils;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class TemplateElementParser
{
	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	public static function parse(SimpleXMLWrapper $xml, $subject)
	{
		if (!$xml->template[0]) {
			throw new MissingElementException("Template element is not found");
		}

		Assert::isNotNull(
			$xml->template[0]->getAttribute("path"),
			"You forgot to define attribute path at template node?"
		);

		$path = $xml->template[0]->getAttribute("path");

		return ConfigurationPathResolver::me()->
			resolvePath(
				$path,
				array("subject" => ClassUtils::getShortClassName($subject))
			);
	}
}