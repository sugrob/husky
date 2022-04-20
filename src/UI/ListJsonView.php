<?php

namespace Husky\UI;

use Husky\Command\ListCommand;
use Husky\Configuration\View\ListView\Column\ColumnCommon;
use Husky\Configuration\View\ListView\ListConfiguration;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Main\Flow\Model;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;

class ListJsonView extends StatefulJsonView
{
	const VIEW_NAME = "list";

	/**
	 * @inheritDoc
	 */
	public function renderModel(Model $model = null)
	{
		$renderedModel = parent::renderModel($model);

		$scope = array();
		$state = array();

		$listConfiguration = $this->getSelfViewConfigurationFromModel($model);

		$this->renderControls($listConfiguration, $scope);
		$scope['configuration']['subject'] = base64_encode(get_class($model->get("subject")));
		$scope['configuration']['handlers'] = $listConfiguration->getHandlers();
		$this->renderFilter($listConfiguration, $scope);
		$this->renderColumns($listConfiguration, $scope);
		$this->renderData($model, $scope);
		$this->renderStateData($model, $state);

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

	protected function renderStateData(Model $model, &$out)
	{
		parent::renderStateData($model, $out);

		$scope = $model->getList();

		$stateData = array(
			'o' => 'id',
			's' => ListCommand::DEFAULT_SORT,
			'count' => 0,
			'limit' => ListCommand::DEFAULT_LIST_LIMIT,
			'page' => 1,
			//'filter' => json_encode(array()), //@TODO add real filter data or even better do not send anything
		);

		if ($model->has("listParameters")) {
			$stateData = array_merge(
				$stateData,
				$model->get("listParameters"));
		}

		$out["views"][$this->getViewName()] = $stateData;
	}

	protected function renderData(Model $model, &$out)
	{
		$out["data"] = array("objectList" => []);

		if ($model->has("queryResult")
			&& ($queryResult = $model->get("queryResult"))
		) {
			$out["data"]["objectList"] = array();

			$gridConfiguration = $this->getSelfViewConfigurationFromModel($model);
			$columns = $gridConfiguration->getColumns();

			if (!isset($column["id"])) {
				$columns["id"] = ColumnCommon::create()->setName("id");
			}

			$columnRenderer = ListColumnValueRenderer::create();

			foreach ($queryResult->getList() as $item) {
				$row = array();

				foreach ($columns as $column) {
					$row[$column->getName()] = $columnRenderer->render($item, $column);
				}

				$out["data"]["objectList"][] = $row;
			}
		}
	}

	protected function renderColumns(ListConfiguration $viewConfiguration, &$out)
	{
		if ($columns = $viewConfiguration->getColumns()) {
			foreach ($columns as $name => $column) {
				$out["configuration"]["columns"][$name] = $column->toArray();
			}
		}
	}

	protected function renderControls(ListConfiguration $viewConfiguration, &$out)
	{
		if ($controls = $viewConfiguration->getControls()) {
			foreach ($controls as $appiarence => $array) {
				foreach ($array as $actionName => $control) {
					$out["configuration"]["controls"][$appiarence][$actionName] = $control->render();
				}
			}
		}
	}

	protected function renderFilter(ListConfiguration $viewConfiguration, &$out)
	{
		if ($filter = $viewConfiguration->getFilter()) {
			$out["configuration"]["filter"]["form"] = array("markup" => [], "elements" => [], "controls" => []);

			if ($markup = $filter->getMarkup()) {
				$out["configuration"]["filter"]["form"]["markup"] = $markup;
			}

			if ($elements = $filter->getElements()) {
				foreach ($elements as $name => $element) {
					$out["configuration"]["filter"]["form"]["elements"][$name] = $element->toArray();
				}
			}

			if ($controls = $filter->getControls()) {
				foreach ($controls as $appiarence => $array) {
					foreach ($array as $actionName => $control) {
						$out["configuration"]["filter"]["form"]["controls"][$appiarence][$actionName] = $control->render();
					}
				}
			}

			if ($handlers = $filter->getHandlers()) {
				$out["configuration"]["filter"]["form"]["handlers"] = $handlers;
			}
		}
	}

	/**
	 * @param Model $model
	 * @return ListConfiguration
	 * @throws MissingElementException
	 * @throws WrongArgumentException
	 */
	protected function getSelfViewConfigurationFromModel(Model $model)
	{
		Assert::isTrue($model->has("viewConfigurations"));

		$viewConfigurations = $model->get("viewConfigurations");

		Assert::isIndexExists(
			$viewConfigurations,
			self::VIEW_NAME,
			"Model doesn't contain Grid view configuration"
		);

		return $viewConfigurations[self::VIEW_NAME];
	}

	protected function getViewName()
	{
		return self::VIEW_NAME;
	}

	function parceErrors(ConsistentModel $model)
	{
		// TODO: Implement parceErrors() method.
	}
}