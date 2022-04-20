<?php

namespace Husky\UI;

use Husky\Configuration\Component\WebForm\Element\ElementHidden;
use Husky\Flow\Action;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Main\Flow\Model;

abstract class StatefulJsonView extends ConsistentJsonView
{
	abstract protected function getViewName();

	/**
	 * @param Model|null $model
	 * @return Model
	 */
	public function renderModel(Model $model = null) {
		$renderedModel = parent::renderModel($model);

		$scope = array();
		$this->renderStateData($model, $scope);

		return $renderedModel->
			set(
				"state",
				$scope
			);
	}

	protected function renderStateData(Model $model, &$out)
	{
		Assert::isInstance($model->get("subject"), Prototyped::class);

		$out["viewName"] = $this->getViewName();
//		$out["subject"] = base64_encode(get_class($model->get("subject")));
		$out["subject"] = get_class($model->get("subject"));
		$out["identifier"] = null;

		if ($model->has('action') && $model->get('action') instanceof Action) {
			$out["action"] = $model->get('action')->getName();
		}

		if ($model->has("id") && is_numeric($model->get("id"))) {
			$out["identifier"] = $model->get("id");
		}
	}
}