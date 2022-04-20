<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Form\FormUtils;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ConsistentSaveCommand;

class SaveCommand extends BaseTransactionCommand implements IContextualCommand
{
	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel
	{
		$form = $context->getForm();

		Assert::isTrue($form->exists('id'));

		$form->import($httpRequest->getPost());

		$this->runBeforeUpdateTriggers($httpRequest, $context);

		$model = ConsistentSaveCommand::create()->
			run(
				$this->subject,
				$form,
				$httpRequest
			);

		$model->set("form", $form);

		if ($model->isSuccessful()) {
			FormUtils::object2form($this->subject, $form);
			$this->runAfterUpdateTriggers($httpRequest, $context);
		}

		$model->
			set('id', $this->subject->getId());


		return $model;
	}

	protected function runBeforeUpdateTriggers(HttpRequest $httpRequest, CommandContext $context)
	{
//		$triggers = $request->getTriggers();
//
//		if (isset($triggers[SCETriggerContext::BEFORE_UPDATE])
//			&& ($list = $triggers[SCETriggerContext::BEFORE_UPDATE])
//		) {
//			foreach ($list as $field => $fieldTriggers) {
//				foreach ($fieldTriggers as $trMethod) {
//					$context = SCETriggerContext::beforeUpdate(
//						$request->getSubject(),
//						$field,
//						$form
//					);
//
//					Delegate::create(
//						$trMethod,
//						array($context)
//					)->run();
//				}
//			}
//		}
	}

	protected function runAfterUpdateTriggers(HttpRequest $httpRequest, CommandContext $context)
	{
//		$triggers = $request->getTriggers();
//
//		if (isset($triggers[SCETriggerContext::AFTER_UPDATE])
//			&& ($list = $triggers[SCETriggerContext::AFTER_UPDATE])
//		) {
//			foreach ($list as $field => $fieldTriggers) {
//				foreach ($fieldTriggers as $trMethod) {
//					$context = SCETriggerContext::afterUpdate(
//						$request->getSubject(),
//						$field
//					);
//
//					Delegate::create(
//						$trMethod,
//						array($context)
//					)->run();
//				}
//			}
//		}
	}
}