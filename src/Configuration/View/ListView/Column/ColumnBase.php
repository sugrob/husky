<?php

namespace Husky\Configuration\View\ListView\Column;

use sugrob\OnPHP\Toolkit\Delegate;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

abstract class ColumnBase
{
	protected $name;              // Name of proto property
	protected $method;            // Business->{method}()
	protected $viewer;            // JS viewer class
	protected $type;              // var type like int, bool, string etc..
	protected $label;
	protected $dataProvider;      // Where to get related data (lists, DB-data and etc)
	protected $decorator;         // Who will prepare data for user
	protected $sortable = false;
	protected $editable = false;
	protected $wrap = false;
	protected $stretch = false;
	protected $css = array();

	abstract public function parse(SimpleXMLWrapper $xml);

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @return string
	 */
	public function getViewer()
	{
		return $this->viewer;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @return string
	 */
	public function getDataProvider()
	{
		return $this->dataProvider;
	}

	/**
	 * @return string
	 */
	public function getDecorator()
	{
		return $this->decorator;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return bool
	 */
	public function isSortable(): bool
	{
		return $this->sortable;
	}

	/**
	 * @return bool
	 */
	public function isEditable(): bool
	{
		return $this->editable;
	}

	/**
	 * @return bool
	 */
	public function getWrap(): bool
	{
		return $this->wrap;
	}

	/**
	 * @return bool
	 */
	public function getStretch(): bool
	{
		return $this->stretch;
	}

	/**
	 * @return array
	 */
	public function getCss(): array
	{
		return $this->css;
	}
}