<?php

namespace Husky\Configuration\Component\WebForm\Element;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Exception\WrongArgumentException;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class ElementCommon extends ElementBase
{
	public function parse(SimpleXMLWrapper $xml)
	{
		foreach ($xml->attributes() as $attribute) {
			$this->parseAttribute($attribute);
		}

		$this->parseProperties($xml);

		$this->ckeckConsistency();
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param mixed $alias
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;
	}

	/**
	 * @param mixed $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}

	/**
	 * @param mixed $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}

	/**
	 * @param Delegate $dataProvider
	 */
	public function setDataProvider(Delegate $dataProvider)
	{
		$this->dataProvider = $dataProvider;
	}

	/**
	 * @param Delegate $decorator
	 */
	public function setDecorator(Delegate $decorator)
	{
		$this->decorator = $decorator;
	}

	/**
	 * @param mixed $viewer
	 */
	public function setViewer($viewer)
	{
		$this->viewer = $viewer;
	}

	/**
	 * @param mixed $help
	 */
	public function setHelp($help)
	{
		$this->help = $help;
	}

	public function toArray()
	{
		return array(
			"name" => $this->alias ? $this->alias : $this->name,
			"viewer" => $this->viewer,
			"label" => $this->label,
			"help" => $this->help,
			//"validation" => $this->validation, ???
			"settings" => $this->settings
		);
	}
	
	protected function parseAttribute(SimpleXMLWrapper $attribute)
	{
		$name = $attribute->getName();
		$value = (string)$attribute;

		switch ($name) {
			case "name":
				$this->setName($value);
				break;

			case "alias":
				$this->setAlias($value);
				break;

			case "method":
				$this->setMethod($value);
				break;

			case "dataProvider":
				$this->setDataProvider($value);
				break;

			case "decorator":
				$this->setDecorator($value);
				break;

			case "viewer":
				$this->setViewer($value);
				break;
		}
	}

	protected function parseProperties(SimpleXMLWrapper $xml)
	{
		if ($xml->label[0]) {
			$this->setLabel((string)$xml->label);
		}

		if ($xml->help[0]) {
			$this->setHelp((string)$xml->help);
		}

		if ($xml->settings[0]) {
			$this->parseSettings($xml->settings);
		}

		if ($xml->validation[0]) {
			$this->parseValidation($xml->validation);
		}
	}

	/**
	 * @throws WrongArgumentException
	 */
	protected function ckeckConsistency()
	{
		Assert::isNotNull(
			$this->name,
			"Name of form element is required parameter"
		);

		Assert::isNotNull(
			$this->viewer,
			"Viewer of form element is required parameter"
		);
	}

	/**
	 * @param array $settings
	 */
	protected function parseSettings(array $settings)
	{
		// TODO: Implement parse settings.
	}

	/**
	 * @param array $validation
	 */
	protected function parseValidation(array $validation)
	{
		// TODO: Implement parse validation.
	}
}