<?php

namespace Husky\Configuration\Component\WebForm\Element;

use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

abstract class ElementBase
{
	protected $name;                    // Name of proto property
	protected $alias;                   // Will be used instead of original name
	protected $method;                  // Business->{method}()
	protected $label;
	protected $type;                    // PHP type (integer|string|...). Method settype() will be executed

	/**
	 * @var Delegate
	 */
	protected $dataProvider;            // Where to get related data (lists, DB-data and etc)

	/**
	 * @var Delegate
	 */
	protected $decorator;               // Who will prepare data for user
	protected $viewer;                  // JS viewer class
	protected $settings = array();
	protected $validation = array();    // Expression list
	protected $help;                    // Textual description

	abstract public function parse(SimpleXMLWrapper $xml);

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getAlias(): string
	{
		return $this->alias;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string|null
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return string
	 */
	public function getLabel(): string
	{
		return $this->label;
	}

	/**
	 * @return Delegate|null
	 */
	public function getDataProvider()
	{
		return $this->dataProvider;
	}

	/**
	 * @return Delegate|null
	 */
	public function getDecorator()
	{
		return $this->decorator;
	}

	/**
	 * @return string
	 */
	public function getViewer(): string
	{
		return $this->viewer;
	}

	/**
	 * @return array
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}

	/**
	 * @return array
	 */
	public function getValidation(): array
	{
		return $this->validation;
	}

	/**
	 * @return string
	 */
	public function getHelp(): string
	{
		return $this->help;
	}
}