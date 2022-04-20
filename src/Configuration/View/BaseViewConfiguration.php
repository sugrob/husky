<?php

namespace Husky\Configuration\View;

use Husky\Configuration\Parser\HandlersParser;
use Husky\Configuration\ProtoConfiguration;
use Husky\UI\Control;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;
use PHPUnit\Util\Exception;

abstract class BaseViewConfiguration extends ProtoConfiguration
{
	protected $controls = array();
	protected $handlers = array();

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

	/**
	 * @param SimpleXMLWrapper $controlNode
	 * @return Control
	 */
	protected function parseControl(SimpleXMLWrapper $controlNode) {
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

			if ($controlNode->renderer[0]) {
				foreach ($controlNode->renderer[0] as $rendererOption) {
					$options[$rendererOption->getName()] = (string)$rendererOption;
				}
			}

			unset($options["call"]);

			if ($options) {
				$control->setRendererOptions($options);
			}
		}

		if ($label = (string)$controlNode->label) {
			$control->setLabel($label);
		}

		return $control;
	}
}