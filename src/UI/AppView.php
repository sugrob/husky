<?php

namespace Husky\UI;

use Husky\Flow\Action;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Main\Flow\Model;
use OnPHP\Main\UI\View\JsonView;
use OnPHP\Main\UI\View\SimplePhpView;
use sugrob\OnPHP\Toolkit\Flow\CumulativeModel;
use phpDocumentor\Reflection\DocBlock\Tags\Reference\Reference;

class AppView extends SimplePhpView
{
	public function render(/* Model */ $model = null) {
		$appModel = CumulativeModel::create()->
			set('configuration', $this->renderAppConfiguration($model))->
			set('state', $this->renderState($model));

		Assert::isTrue($model->has('configuration'));

		$views = $model->get('configuration')->getViews();

		foreach ($views as $name => $view) {
			if ($view instanceof PrerenderableModelView) {
				$viewModel = $view->renderModel($model, true);
			} else {
				throw new WrongArgumentException("View ".get_class($view)." must implement PrerenderableModelView");
			}

			$appModel->merge($viewModel);
		}

		return parent::render($appModel);
	}

	private function renderAppConfiguration(Model $model)
	{
		$configuration = array();

		Assert::isTrue($model->has('serviceUrl'));

		$configuration['serviceUrl'] = $model->get('serviceUrl');

//		if ($vc = $model->get('viewConfigurations')) {
//			foreach ($vc as $name => $vcItem) {
//				$configuration['views'][$name] = $vcItem->toArray();
//			}
//		}

		return $configuration;
	}

	private function renderState(Model $model)
	{
		$state = array();

		Assert::isTrue($model->has('action'));
		Assert::isInstance($model->get('action'), Action::class);

		$state['subject'] = get_class($model->get('subject'));
		$state['action'] = $model->get('action')->getName();
		$state['viewName'] = $model->get('action')->getViewName();
		$state['views'] = array();

		$views = $model->get('configuration')->getViews();

		foreach ($views as $name => $view) {
			$state['views'][$name] = array();
		}

		return $state;
	}
}