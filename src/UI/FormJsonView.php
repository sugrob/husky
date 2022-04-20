<?php

namespace Husky\UI;

use http\Exception;
use Husky\Configuration\Component\WebForm\Element\ElementCommon;
use Husky\Configuration\Component\WebForm\Element\ElementHidden;
use Husky\Configuration\View\FormView\FormConfiguration;
use Husky\Configuration\View\ListView\ListConfiguration;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Main\Flow\Model;
use OnPHP\Main\UI\View\JsonView;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;

class FormJsonView extends StatefulJsonView
{
	const VIEW_NAME = "form";

	/**
	 * @inheritDoc
	 */
	public function renderModel(Model $model = null) {
		$renderedModel = parent::renderModel($model);

		$scope = array();

		$formConfiguration = $this->getSelfViewConfigurationFromModel($model);
		$this->renderControls($formConfiguration, $scope);
		$scope['configuration']['subject'] = base64_encode(get_class($model->get("subject")));
		$scope['configuration']['handlers'] = $formConfiguration->getHandlers();
		$this->renderWebForm($formConfiguration, $scope);
		$this->renderData($model, $scope);

		return $renderedModel->
			set(
				"configuration",
				array(
					"views" => array(
						self::VIEW_NAME => $scope["configuration"]
					)
				)
			)->
			set(
				"data",
				array(self::VIEW_NAME => $scope["data"])
			);
	}

	protected function renderData(Model $model, &$out)
	{
		$out["data"] = array(
			"subject" => []
		);

		if ($model->has("form")) {
			$form = $model->get("form");
			$formConfiguration = $this->getSelfViewConfigurationFromModel($model);
			$webForm = $formConfiguration->getWebForm();

			$elements = $webForm->getElements();
			$webFormElementRenderer = WebFormElementValueRenderer::create();

			if (!$webForm->hasElement("id")) {
				/**
				 * In case of "id" field is not present in XML, we need
				 * add this value to JSON data
				 */
				$idElement = new ElementHidden();
				$idElement->setName("id");
				$elements[] = $idElement;
			}

			$result = array();

			foreach ($elements as $element) {
				$result[$element->getName()] = $webFormElementRenderer->render($form, $element);
			}

			$out["data"]["subject"] = $result;
		}
	}

	protected function renderControls(FormConfiguration $viewConfiguration, &$out)
	{
		if ($controls = $viewConfiguration->getControls()) {
			foreach ($controls as $appiarence => $array) {
				foreach ($array as $actionName => $control) {
					$out["configuration"]["controls"][$appiarence][$actionName] = $control->render();
				}
			}
		}
	}

	protected function renderWebForm(FormConfiguration $viewConfiguration, &$out)
	{
		$webForm = $viewConfiguration->getWebForm();

		if ($markup = $webForm->getMarkup()) {
			$out["configuration"]["markup"] = $markup;
		}

		if ($elements = $webForm->getElements()) {
			foreach ($elements as $name => $element) {
				$out["configuration"]["elements"][$name] = $element->toArray();
			}
		}
	}

	/**
	 * @param Model $model
	 * @return FormConfiguration
	 * @throws MissingElementException
	 * @throws WrongArgumentException
	 */
	protected function getSelfViewConfigurationFromModel(Model $model) {
		Assert::isTrue($model->has("viewConfigurations"));

		$viewConfigurations = $model->get("viewConfigurations");

		Assert::isIndexExists(
			$viewConfigurations,
			self::VIEW_NAME,
			"Model doesn't contain List view configuration"
		);

		return $viewConfigurations[self::VIEW_NAME];
	}

	protected function getViewName()
	{
		return self::VIEW_NAME;
	}
}