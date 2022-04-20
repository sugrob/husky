<?php

namespace Husky\Configuration\Parser;

use OnPHP\Core\Exception\WrongArgumentException;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class MarkupParser
{
	/**
	 * @param SimpleXMLWrapper $xml
	 * @return array
	 * @throws WrongArgumentException
	 */
	public static function parse(SimpleXMLWrapper $xml)
	{
		$markup = array("type" => "layout", "children" => [], "legend" => "");

		if ($xml->markup[0]) {
			foreach ($xml->markup[0] as $node) {
				if ($node->getAttribute("type")) {
					$markup = $node->getAttribute("type");
				}

				switch ($node->getName()) {
					case "legend":
						$markup["legend"] = (string)$node;
						break;

					case "layout":
					case "fieldset":
						$markup["children"][] = self::recursiveParseLayout($node);
						break;
					default:
						throw new WrongArgumentException("Unreacheble code reached");
				}
			}
		}

		return $markup;
	}

	/**
	 * @param SimpleXMLWrapper $xml layout|fieldset node
	 * @return array
	 * @throws WrongArgumentException
	 */
	private static function recursiveParseLayout(SimpleXMLWrapper $xml) {
		// Fill by default values
		$layout = array(
			"type" => "layout",
			"direction" => "row",
			"wrap" => "",
			"width" => "100%",
			"children" => [],
			"elements" => []
		);

		$layout["type"] = $xml->getName();

		foreach ($xml->attributes() as $attribute) {
			$layout[$attribute->getName()] = (string)$attribute;
		}

		foreach ($xml->getChildren() as $child) {
			switch ($child->getName()) {
				case "layout":
					$layout["children"][] = self::recursiveParseLayout($child);
					break;

				case "element":
					$layout["elements"][] = $child->getAttribute("name");
					break;

				default:
					throw new WrongArgumentException("Unreacheble code reached");
			}
		}

		return $layout;
	}
}