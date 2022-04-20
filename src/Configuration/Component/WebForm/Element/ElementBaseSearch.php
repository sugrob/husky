<?php

namespace Husky\Configuration\Component\WebForm\Element;

use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class ElementBaseSearch extends ElementCommon
{
	protected $expressions = array();    // Query conditions list

	public function toArray()
	{
		$array = parent::toArray();
		$array["expressions"] = $this->expressions;
		return $array;
	}

	/**
	 * @return array
	 */
	public function getExpressions(): array
	{
		return $this->expressions;
	}

	protected function parseProperties(SimpleXMLWrapper $xml)
	{
		parent::parseProperties($xml);

		if ($xml->expressions[0]) {
			$this->expressions = array();

			foreach ($xml->expressions[0] as $node) {
				$logic = $node->getAttribute("logic");
				$default = strtolower($node->getAttribute("default")) === "true";
				$name = (string)$node;

				$this->expressions[] = ["logic" => $logic, "name" => $name, "default" => $default];
			}
		}
	}
}