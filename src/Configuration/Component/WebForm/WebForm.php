<?php

namespace Husky\Configuration\Component\WebForm;

use Husky\Configuration\Component\WebForm\Element\ElementBase;
use Husky\Configuration\Component\WebForm\Element\ElementString;
use Husky\Configuration\Parser\MarkupParser;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\ClassNotFoundException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Logic\Expression;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class WebForm
{
	/**
	 * @var Prototyped
	 */
	protected $subject;

	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * Pairs: name => class
	 * @var array
	 */
	private $elementTypes = array();

	/**
	 * @var string
	 */
	protected $defaultElementType;

	/**
	 * Pairs: name => some instance of ElementBase
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Assoc array: name => (marker => $marker, message => $errorMessage)
	 * @var array
	 */
	protected $exceptions = array();

	/**
	 * Assoc array: name => array(type => $type, message => $errorMessage)
	 * @var array
	 */
	protected $errorMessages = array();

	/**
	 * @var array
	 */
	protected $markup = array();

	/**
	 * @return WebForm
	 */
	public static function create(Prototyped $subject)
	{
		return new self($subject);
	}

	/**
	 * WebForm constructor.
	 * @param Prototyped $subject
	 */
	public function __construct(Prototyped $subject)
	{
		$this->subject = $subject;
		$this->form = $this->subject->proto()->makeForm();
	}

	/**
	 * @return Form
	 */
	public function getForm(): Form
	{
		return $this->form;
	}

	/**
	 * @return array
	 */
	public function getElements(): array
	{
		return $this->elements;
	}

	/**
	 * @param ElementBase $element
	 * @return WebForm
	 */
	public function addElement(ElementBase $element)
	{
		$this->elements[$element->getName()] = $element;
		return $this;
	}

	/**
	 * @param $name string
	 * @return bool
	 */
	public function hasElement(string $name): bool
	{
		return isset($this->elements[$name]);
	}

	/**
	 * @param string $name
	 * @return WebForm
	 */
	public function dropElement(string $name)
	{
		if ($this->hasElement($name)) {
			unset($this->elements[$name]);
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExceptions(): array
	{
		return $this->exceptions;
	}

	/**
	 * @return array
	 */
	public function getErrorMessages(): array
	{
		return $this->errorMessages;
	}

	/**
	 * @return array
	 */
	public function getMarkup(): array
	{
		return $this->markup;
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @return WebForm
	 * @throws WrongArgumentException
	 */
	public function parse(SimpleXMLWrapper $xml): WebForm
	{
		if ($xml->form[0]) {
			// Methods call order is important
			$this->parseElementTypes($xml->form);
			$this->parseElements($xml->form);
			$this->parseExceptions($xml->form);
			$this->parseErrorMessages($xml->form);
			$this->parseMarkup($xml->form);
		}

		return $this;
	}

	public function reconfigureForm()
	{
		// Drop unused primitives
		foreach ($this->form->getPrimitiveNames() as $name) {
			if (!isset($this->elements[$name])
				&& $name != "id" // Very special case. Never drop ID
			) {
				$this->form->drop($name);
			}
		}

		foreach ($this->exceptions as $name => $rule) {
			$this->form->
				addRule($name, Expression::isTrue(true))->
				addWrongLabel($name, $rule["message"]);
		}

		foreach ($this->errorMessages as $name => $message) {
			if ($message["type"] == "wrong") {
				$this->form->addWrongLabel($name, $message["message"]);
			} else if ($message["type"] == "missing") {
				$this->form->addWrongLabel($name, $message["message"]);
			} else {
				throw new WrongArgumentException("Unknown error type: ".$message["type"]);
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws ClassNotFoundException
	 */
	private function parseElementTypes(SimpleXMLWrapper $xml)
	{
		if ($xml->elementTypes[0]) {
			foreach ($xml->elementTypes[0] as $typeNode) {
				$name = $typeNode->getAttribute("name");
				$class = $typeNode->getAttribute("class");

				if (strtolower($typeNode->getAttribute("default")) == "true") {
					$this->defaultElementType = $name;
				}

				Assert::classExists($class, "Unknown form element class: {$class}");

				$this->elementTypes[$name] = $class;
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	private function parseElements(SimpleXMLWrapper $xml)
	{
		if ($xml->elements[0]) {
			// Renew elements
			$this->elements = array();

			foreach ($xml->elements[0] as $elementNode) {
				if ($type = $elementNode->getAttribute("viewer")) {
					Assert::isIndexExists(
						$this->elementTypes,
						$type,
						"Don't know about element type: {$type}"
					);

					$element = new $this->elementTypes[$type];
				} else {
					$element = $this->spawnDefaultElement();
				}

				$element->parse($elementNode);
				$this->elements[$element->getName()] = $element;
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	private function parseExceptions(SimpleXMLWrapper $xml)
	{
		if ($xml->consistency[0] && $xml->consistency->exceptions[0]) {
			// Renew exceptions
			$this->exceptions = array();

			foreach ($xml->consistency->exceptions[0] as $eNode) {
				$name = $eNode->getAttribute("name");
				$marker = $eNode->getAttribute("marker");
				$errorMessage = (string)$eNode;

				Assert::isNotNull($name, "Rule name is null");
				Assert::isNotNull($marker, "Rule marker is null");
				Assert::isNotEmpty($errorMessage, "Rule error message is empty");

				$this->exceptions[$name] = array("marker" => $marker, "message" => $errorMessage);
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	private function parseErrorMessages(SimpleXMLWrapper $xml)
	{
		if ($xml->consistency[0] && $xml->consistency->errors[0]) {
			// Renew messages
			$this->errorMessages = array();

			foreach ($xml->consistency->errors[0] as $errorNode) {
				$name = $errorNode->getAttribute("name");
				$type = $errorNode->getAttribute("type");
				$errorMessage = (string)$errorNode;

				Assert::isNotNull($name, "Error message name is null");
				Assert::isTrue(
					in_array($type, array("wrong", "missing")),
					"Unexpected error message type"
				);
				Assert::isNotEmpty($errorMessage, "Error message text is empty");

				$this->errorMessages[$name] = array("type" => $type, "message" => $errorMessage);
			}
		}
	}

	/**
	 * @return ElementBase
	 * @throws WrongArgumentException
	 */
	private function spawnDefaultElement(): ElementBase
	{
		Assert::isNotNull(
			$this->defaultElementType,
			"Default element type is not defined yet."
		);

		return new $this->elementTypes[$this->defaultElementType];
	}

	/**
	 * @param SimpleXMLWrapper $xml markup node
	 * @throws WrongArgumentException
	 */
	private function parseMarkup(SimpleXMLWrapper $xml)
	{
		if ($xml->markup[0]) {
			// Renew markup
			$this->markup = MarkupParser::parse($xml);
		}
	}
}