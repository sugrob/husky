<?php

namespace Husky\Flow;

use Husky\Configuration\View\BaseViewConfiguration;
use OnPHP\Core\Form\Form;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;

class CommandContext
{
	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @var BaseViewConfiguration
	 */
	protected $viewConfiguration;

	/**
	 * @var bool
	 */
	protected $isAjax = false;

	/**
	 * @var ObjectRelation
	 */
	protected $relation;

	/**
	 * @return CommandContext
	 */
	public static function create(): CommandContext
	{
		return new self;
	}

	/**
	 * @return Form
	 */
	public function getForm(): Form
	{
		return $this->form;
	}

	/**
	 * @param Form $form
	 * @return CommandContext
	 */
	public function setForm(Form $form): CommandContext
	{
		$this->form = $form;
		return $this;
	}

	/**
	 * @return BaseViewConfiguration
	 */
	public function getViewConfiguration(): BaseViewConfiguration
	{
		return $this->viewConfiguration;
	}

	/**
	 * @param BaseViewConfiguration $viewConfiguration
	 * @return CommandContext
	 */
	public function setViewConfiguration(BaseViewConfiguration $viewConfiguration): CommandContext
	{
		$this->viewConfiguration = $viewConfiguration;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isAjax(): bool
	{
		return $this->isAjax;
	}

	/**
	 * @param bool $orly
	 * @return CommandContext
	 */
	public function setIsAjax(bool $orly): CommandContext
	{
		$this->isAjax = $orly;
		return $this;
	}

	/**
	 * @return ObjectRelation|null
	 */
	public function getRelation()
	{
		return $this->relation;
	}

	/**
	 * @param ObjectRelation $relation
	 * @return CommandContext
	 */
	public function setRelation(ObjectRelation $relation): CommandContext
	{
		$this->relation = $relation;
		return $this;
	}
}