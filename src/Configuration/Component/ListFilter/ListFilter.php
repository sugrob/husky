<?php

namespace Husky\Configuration\Component\ListFilter;

use Husky\Configuration\Component\WebForm\WebForm;
use Husky\Configuration\ConfigurationPathResolver;
use Husky\Configuration\Parser\HandlersParser;
use Husky\UI\Control;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Form;
use sugrob\OnPHP\Toolkit\ClosureResolver;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class ListFilter
{
	/**
	 * @var Prototyped
	 */
	private $subject;

	/**
	 * @var WebForm
	 */
	private $webForm;

	private $controls = array();

	private $handlers = array();

	/**
	 * @param $subject
	 * @return ListFilter
	 */
	public static function create(Prototyped $subject): ListFilter
	{
		return new self($subject);
	}

	public function __construct(Prototyped $subject)
	{
		$this->subject = $subject;
		$this->webForm = WebForm::create($subject);
	}

	/**
	 * @return array
	 */
	public function getElements(): array
	{
		return $this->webForm->getElements();
	}

	/**
	 * @return array
	 */
	public function getMarkup(): array
	{
		return $this->webForm->getMarkup();
	}

	/**
	 * @return array
	 */
	public function getControls(): array
	{
		return $this->controls;
	}

	/**
	 * @return array
	 */
	public function getHandlers(): array
	{
		return $this->handlers;
	}

	public function loadXML($file)
	{
		$this->recursiveLoadXML($file);
		$this->reconfigureForm();

		return $this;
	}

	private function recursiveLoadXML($file)
	{
		$xml = new SimpleXMLWrapper($file, 0, true);

		if ($xml->hasAttribute("parent")) {
			$parentPath = ConfigurationPathResolver::me()->
				resolvePath($xml->getAttribute("parent"));
			$this->recursiveLoadXML($parentPath);
		}

		$this->webForm->parse($xml->filter);

		$this->parseControls($xml->filter);
		$this->parseHandlers($xml->filter);
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
						$actionName = $controlNode->getAttribute("action");

						$control = Control::create()->
							setActionName($actionName);

						if ($controlNode->event
							&& ($event = $controlNode->event->getAttribute("name"))
						) {
							$control->setEventName($event);
						}

						if ($controlNode->renderer
							&& ($renderer = $controlNode->renderer->getAttribute("call"))
						) {
							$control->setRenderer($renderer);
							$options = $controlNode->renderer->getAttributes();
							unset($options["call"]);

							if ($options) {
								$control->setRendererOptions($options);
							}
						}

						if ($label = (string)$controlNode->label) {
							$control->setLabel($label);
						}

						$this->controls[$appiarence][$actionName] = $control;
					}
				}
			}
		}
	}

	private function reconfigureForm()
	{
		$form = $this->webForm->getForm();

		// Drop unused primitives
//		foreach ($form->getPrimitiveNames() as $name) {
//			if (!isset($this->elements[$name])) {
//				$form->drop($name);
//			}
//		}

		foreach ($this->webForm->getElements() as $name => $element) {
			if (!$form->exists($name)) {
				// Create form field if search field is delegated and
				// not present in form yet
				if (strstr($name, ClosureResolver::METHOD_SEPARATOR)) {
					// In case when property is foreign
					try {
						if ($delegate = ClosureResolver::resolveMethod($this->subject, $name)) {
							$object = MethodResolver::resolveObject($this->subject, $name);

							$parts = explode(ClosureResolver::METHOD_SEPARATOR, $name);
							$propertyName = array_pop($parts);

							if ($object instanceof Prototyped
								&& $object->proto()->isPropertyExists($propertyName)
							) {
								if ($this->subject->proto()->isPropertyExists($propertyName)) {
									// Create field by proto
									$object->proto()->
										getPropertyByName($propertyName)->
											fillForm($form, get_class($object));

									// Rename field
									$form->
										add(
											$form->
												get(get_class($object).$propertyName)->
													setName($name)
										);
								} else {
									// Create field by proto
									$object->proto()->
										getPropertyByName($propertyName)->
											fillForm($form);

									//rename field
									$form->
										add(
											$form->
											get($propertyName)->
											setName($name)
										)->
										drop($propertyName);
								}


							} else {
								// Create simple string field
								$form->add(Primitive::string($name));
							}
						}
					} catch (WrongArgumentException $e) {}
				} else {
					// @TODO - implement creation of common fields
				}
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	protected function parseHandlers(SimpleXMLWrapper $xml)
	{
		try {
			// Renew handlers
			$this->handlers = HandlersParser::parse($xml);
		} catch (MissingElementException $e) {
			// Inherit from parent
		}
	}
}