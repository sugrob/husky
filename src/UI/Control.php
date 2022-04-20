<?php

namespace Husky\UI;

class Control
{
	protected $actionName;
	protected $appearance;
	protected $eventName;
	protected $renderer;
	protected $rendererOptions = array();
	protected $label;

	/**
	 * @return Control
	 */
	public static function create()
	{
		return new self;
	}

	/**
	 * @return string
	 */
	public function getActionName()
	{
		return $this->actionName;
	}

	/**
	 * @param string $actionName
	 * @return Control
	 */
	public function setActionName($actionName): Control
	{
		$this->actionName = $actionName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAppearance()
	{
		return $this->appearance;
	}

	/**
	 * @param string $appearance
	 * @return Control
	 */
	public function setAppearance($appearance): Control
	{
		$this->appearance = $appearance;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEventName()
	{
		return $this->eventName;
	}

	/**
	 * @param string $eventName
	 * @return Control
	 */
	public function setEventName($eventName): Control
	{
		$this->eventName = $eventName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}

	/**
	 * @param string $renderer
	 * @return Control
	 */
	public function setRenderer($renderer): Control
	{
		$this->renderer = $renderer;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRendererOptions(): array
	{
		return $this->rendererOptions;
	}

	/**
	 * @param array $rendererOptions
	 * @return Control
	 */
	public function setRendererOptions(array $rendererOptions): Control
	{
		$this->rendererOptions = $rendererOptions;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param string $label
	 * @return Control
	 */
	public function setLabel($label): Control
	{
		$this->label = $label;

		return $this;
	}

	public function render()
	{
		return array(
			"appearance" => $this->appearance,
			"actionName" => $this->actionName,
			"eventName" => $this->eventName,
			"renderer" => $this->renderer,
			"rendererOptions" => $this->rendererOptions,
			"label" => $this->label,
		);
	}
}