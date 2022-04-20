<?php

namespace Husky\Configuration\View\ListView;

use Husky\Configuration\Component\ListFilter\ListFilter;
use Husky\Configuration\IRenderable;
use Husky\Configuration\Parser\TemplateElementParser;
use Husky\Configuration\View\IEditableViewConfiguration;
use Husky\Configuration\View\ListView\Column\ColumnBase;
use Husky\Configuration\View\BaseViewConfiguration;
use Husky\Configuration\ConfigurationPathResolver;
use Husky\Configuration\View\IStaticTemplateView;
use Husky\UI\Control;
use OnPHP\Core\Base\Stringable;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Base\Assert;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class ListConfiguration
	extends BaseViewConfiguration
	implements IEditableViewConfiguration, IStaticTemplateView
{

	/**
	 * @var Form
	 */
	protected $form;
	/**
	 * @var ListFilter
	 */
	protected $filter;

	/**
	 * @var string
	 */
	protected $defaultColumnType;
	protected $columnTypes = array();
	protected $columns = array();

	protected $template;

	/**
	 * @param $subject
	 * @return ListConfiguration
	 */
	public static function create($subject)
	{
		return new self($subject);
	}

	/**
	 * ListConfiguration constructor.
	 * @param $subject
	 * @throws WrongArgumentException
	 */
	public function __construct($subject)
	{
		parent::__construct($subject);

		$this->form = $this->subject->proto()->makeForm();
	}

	/**
	 * @return Form
	 */
	public function getForm(): Form
	{
		return $this->form;
	}

	public function getTemplatePath(): string
	{
		return $this->template;
	}

	public function getConsistency(): array
	{
		// Still there are no rules in this view
		return array();
	}

	/**
	 * @return ListFilter
	 */
	public function getFilter(): ListFilter
	{
		return $this->filter;
	}

	/**
	 * @return array
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}

	public function toArray(): array
	{
		$array = array(
			'columns' => []
		);

		foreach ($this->getColumns() as $name => $column) {
			$array['columns'][$name] = $column->toArray();
		}

		return $array;
	}

	public function loadXML($file)
	{
		$this->recursiveLoadXML($file);
		$this->loadFilterConfiguration($file);
		$this->reconfigureForm();
	}

	private function recursiveLoadXML($file)
	{
		$xml = new SimpleXMLWrapper($file, 0, true);

		if ($xml->hasAttribute("parent")) {
			$parentPath = ConfigurationPathResolver::me()->
				resolvePath($xml->getAttribute("parent"));
			$this->recursiveLoadXML($parentPath);
		}

		$this->parseTemplateElement($xml);
		$this->parseColumnTypes($xml);
		$this->parseColumns($xml);
		$this->parseControls($xml);
		$this->parseHandlers($xml);
	}

	private function loadFilterConfiguration($file)
	{
		$this->filter = ListFilter::create($this->subject)->loadXML($file);
	}

	private function parseColumnTypes(SimpleXMLWrapper $xml)
	{
		if ($xml->columnTypes[0]) {
			foreach ($xml->columnTypes[0] as $typeNode) {
				$name = $typeNode->getAttribute("name");
				$class = $typeNode->getAttribute("class");

				if (strtolower($typeNode->getAttribute("default")) == "true") {
					$this->defaultColumnType = $name;
				}

				Assert::classExists($class, "Unknown column class: {$class}");

				$this->columnTypes[$name] = $class;
			}
		}
	}

	private function parseColumns(SimpleXMLWrapper $xml)
	{
		if ($xml->columns[0]) {
			// Renew actions
			$this->columns = array();

			foreach ($xml->columns[0] as $columnNode) {
				if ($type = $columnNode->getAttribute("type")) {
					Assert::isIndexExists(
						$this->columnNode,
						$type,
						"Don't know about column type: {$type}"
					);

					$column = new $this->elementTypes[$type];
				} else {
					$column = $this->spawnDefaultColumn();
				}

				$column->parse($columnNode);
				$this->columns[$column->getName()] = $column;
			}
		}
	}

	/**
	 * @return ColumnBase
	 * @throws WrongArgumentException
	 */
	private function spawnDefaultColumn(): ColumnBase
	{
		Assert::isNotNull(
			$this->defaultColumnType,
			"Default column type is not defined yet."
		);

		return new $this->columnTypes[$this->defaultColumnType];
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	private function parseControls(SimpleXMLWrapper $xml)
	{
		if ($xml->controls[0]) {
			// Renew controls
			$this->controls = array();

			foreach ($xml->controls[0] as $appearenceNode) {
				$appiarence = $appearenceNode->getName();

				$this->controls[$appiarence] = array();

				if ($appearenceNode[0]) {
					foreach ($appearenceNode as $controlNode) {
						$control = $this->parseControl($controlNode);
						$this->controls[$appiarence][$control->getActionName()] = $control;
					}
				}
			}
		}
	}

	private function parseTemplateElement(SimpleXMLWrapper $xml)
	{
		try {
			$this->template = TemplateElementParser::parse($xml, $this->subject);
		} catch (MissingElementException $e) {
			/* Do nothing */
		}
	}

	private function reconfigureForm()
	{
		// Drop unused primitives
		foreach ($this->form->getPrimitiveNames() as $name) {
			if (!isset($this->columns[$name])
				&& $name != "id" // Very special case. Never drop ID
			) {
				$this->form->drop($name);
			}
		}
	}
}