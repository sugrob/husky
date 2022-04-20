<?php

namespace Husky\UI;

use OnPHP\Main\Flow\Model;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;

abstract class ConsistentJsonView extends PlainJsonView
{
	/**
	 * @param Model|null $model
	 * @return Model
	 */
	public function renderModel(Model $model = null) {
		$renderedModel = parent::renderModel($model);

		$scope = array();
		$this->processConsistentData($model, $scope);

		return $renderedModel->
			set(
				"consistency",
				$scope
			);
	}

	protected function processConsistentData(ConsistentModel $model, &$out)
	{
		$out["successful"] = $model->isSuccessful();

		if (!$model->isSuccessful() && $model->getErrors()) {
			$out["errors"] = $model->getErrors();

			if ($errors = $model->getErrors()) {
				$formConfiguration = $this->getSelfViewConfigurationFromModel($model);
				$consistencyData = $formConfiguration->getConsistency();

				foreach ($errors as $name => $error) {
					if ($consistencyData["errors"][$name]) {
						$out["errors"][$name] = $consistencyData["errors"][$name];
					}
				}
			}
		}
	}
}