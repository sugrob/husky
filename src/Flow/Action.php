<?php

namespace Husky\Flow;

use OnPHP\Main\UI\View\View;

class Action
{
	/**
	 * @var string
	 */
	protected $viewName;
	protected $name;
	protected $aclRightId;
	protected $commands = array();
	protected $default = false;

	/**
	 * @return Action
	 */
	public static function create()
	{
		return new self;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return Action
	 */
	public function setName(string $name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * @return array
	 */
	public function addCommand($commandClass)
	{
		return $this->commands[] = $commandClass;
	}

	/**
	 * @return array
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	/**
	 * @param array $commands
	 */
	public function setCommands(array $commands)
	{
		$this->commands = $commands;
	}

	/**
	 * @return string
	 */
	public function getViewName(): string
	{
		return $this->viewName;
	}

	/**
	 * @param string $viewName
	 * @return Action
	 */
	public function setViewName(string $viewName)
	{
		$this->viewName = $viewName;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAclRightId()
	{
		return $this->aclRightId;
	}

	/**
	 * @param $aclRightId
	 * @return Action
	 */
	public function setAclRightId($aclRightId)
	{
		$this->aclRightId = $aclRightId;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getDefault(): bool
	{
		return $this->default;
	}

	/**
	 * @param bool $default
	 * @return Action
	 */
	public function setDefault(bool $default)
	{
		$this->default = $default;

		return $this;
	}
}