<?php

namespace Husky\UI;

use OnPHP\Main\Flow\Model;

interface PrerenderableModelView
{
	/**
	 * @param Model $model
	 * @return Model
	 */
	public function renderModel(Model $model = null);
}