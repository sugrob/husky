<?php

namespace Husky\Configuration\View\ListView\Column;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Exception\WrongArgumentException;
use sugrob\OnPHP\Toolkit\Delegate;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class ColumnCommon extends ColumnBase
{
	/**
	 * @return ColumnCommon
	 */
	public static function create() {
		return new self();
	}

	public function parse(SimpleXMLWrapper $xml)
	{
		foreach ($xml->attributes() as $attribute) {
			$name = $attribute->getName();
			$value = (string)$attribute;

			switch ($name) {
				case "name":
					$this->setName($value);
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

				case "type":
					$this->setType($value);
					break;

				case "sortable":
					$this->setSortable($value == "true");
					break;

				case "editable":
					$this->setEditable($value == "true");
					break;

				case "wrap":
					$this->setWrap($value == "true");
					break;

				case "stretch":
					$this->setStretch($value == "true");
					break;
			}

			if ($xml->label[0]) {
				$this->setLabel((string)$xml->label);
			}

			if ($xml->css[0]) {
				$this->parseCss($xml->settings);
			}
		}

		$this->ckeckConsistency();
	}

	/**
	 * @throws WrongArgumentException
	 */
	protected function ckeckConsistency()
	{
		Assert::isNotNull(
			$this->name,
			"Name of column is required parameter"
		);

		if ($this->name != "id") {
			Assert::isNotNull(
				$this->viewer,
				"Viewer of column cell is required parameter"
			);
		}
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param mixed $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}

	/**
	 * @param string $viewer
	 */
	public function setViewer(string $viewer)
	{
		$this->viewer = $viewer;
		return $this;
	}

	/**
	 * @param string $type
	 */
	public function setType(string $type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @param string $label
	 */
	public function setLabel(string $label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * @param string $dataProvider
	 */
	public function setDataProvider(string $dataProvider)
	{
		$this->dataProvider = $dataProvider;
		return $this;
	}

	/**
	 * @param string $decorator
	 */
	public function setDecorator(string $decorator)
	{
		$this->decorator = $decorator;
		return $this;
	}

	/**
	 * @param bool $sortable
	 */
	public function setSortable(bool $sortable)
	{
		$this->sortable = $sortable;
		return $this;
	}

	/**
	 * @param bool $editable
	 */
	public function setEditable(bool $editable)
	{
		$this->editable = $editable;
		return $this;
	}

	/**
	 * @param bool $wrap
	 */
	public function setWrap(bool $wrap)
	{
		$this->wrap = $wrap;
		return $this;
	}

	/**
	 * @param bool $stretch
	 */
	public function setStretch(bool $stretch)
	{
		$this->stretch = $stretch;
		return $this;
	}

	/**
	 * @param array $css
	 */
	public function setCss(array $css)
	{
		$this->css = $css;
		return $this;
	}

	private function parseCss(\SimpleXMLElement $settings)
	{
		// @TODO
	}

	public function toArray()
	{
		return array(
			"name" => $this->name,
			"viewer" => $this->viewer,
			"label" => $this->label,
			"sortable" => $this->sortable,
			"editable" => $this->editable,
			"wrap" => $this->wrap,
			"stretch" => $this->stretch,
			"css" => $this->css
		);
	}
}