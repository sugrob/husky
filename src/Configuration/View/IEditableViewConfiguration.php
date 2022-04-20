<?php

namespace Husky\Configuration\View;

use OnPHP\Core\Form\Form;

interface IEditableViewConfiguration
{
	/**
	 * @return Form
	 */
	public function getForm(): Form;

	public function getConsistency(): array;
}