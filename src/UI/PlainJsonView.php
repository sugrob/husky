<?php

namespace Husky\UI;

use OnPHP\Main\Flow\Model;
use OnPHP\Main\UI\View\JsonView;

class PlainJsonView extends JsonView implements PrerenderableModelView
{
	/**
	 * @inheritDoc
	 */
	public function render($model = null, $silent = false)
	{
		return parent::render(
			$this->renderModel($model)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function renderModel(Model $model = null)
	{
		$renderedModel = Model::create();
		return $renderedModel;
	}
}