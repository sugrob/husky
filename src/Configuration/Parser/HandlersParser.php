<?php

namespace Husky\Configuration\Parser;

use Husky\Configuration\Form\Element\ElementBase;
use OnPHP\Core\Exception\MissingElementException;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

final class HandlersParser
{
	/**
	 * @param SimpleXMLWrapper $xml
	 */
	public static function parse(SimpleXMLWrapper $xml)
	{
		if (!$xml->handlers[0]) {
			throw new MissingElementException("Handlers element is not found");
		}

		$handlers = array();

		if ($xml->handlers[0]) {
			foreach ($xml->handlers[0] as $chainNode) {
				$eventName = $chainNode->getAttribute("event");

				$handlers[$eventName] = array();

				if ($chainNode[0]) {
					foreach ($chainNode as $handlerNode) {
						$item = array(
								"name" => $handlerNode->getAttribute("name"),
								"configuration" => array()
							);

						$item["configuration"]["stateless"] = $handlerNode->hasAttribute("stateless")
							&& strtolower($handlerNode->getAttribute("stateless")) == "true";

						$handlers[$eventName][] = $item;
					}
				}
			}
		}

		return $handlers;
	}
}