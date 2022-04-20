<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Form\Form;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Base\Sortable\Sortable;
use sugrob\OnPHP\Toolkit\Flow\ConsistentAddCommand;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;

class AddCommand extends BaseEditorCommand implements IContextualCommand
{
	/**
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel
	{
		$model = ConsistentModel::create();

		$form = $context->getForm();

		Assert::isTrue($form->exists('id'));

		$form->import($httpRequest->getPost());

		$this->obtainNextPosition();

		$this->setOneToManyReference($httpRequest, $form);

		$this->runBeforeInsertTriggers($httpRequest, $context);

		$model = ConsistentAddCommand::create()->
			run(
				$this->subject,
				$form,
				$httpRequest
			);

		$form->importValue("id", $this->subject->getId());
		$model->set("form", $form);

		if ($model->isSuccessful()) {
			$this->runAfterInsertTriggers($httpRequest, $context);
			$this->setOneToOneReference($httpRequest);
		}

		return $model;
	}

	protected function setOneToManyReference(
		HttpRequest $request,
		Form $form
	) {
		if ($request->hasGetVar('reference')
			&& $request->getGetVar('reference')
		) {
			try {
				$ref = ConcreteReference::make($request->getGetVar('reference'));

				if ($ref instanceof ConcreteReference
					&& ($parent = $ref->getParent()) instanceof Identifiable
					&& $ref->getParent()->getId()
				) {
					if ($ref->getRelationId() == MetaRelation::ONE_TO_MANY) {
						$name = $ref->getChildProperty();

						if ($form->exists($name)
							&& !$form->getValue($name)
						) {
							$form->importValue($name, $parent);
						}

					} elseif ($ref->getRelationId() == MetaRelation::ONE_TO_ONE) {
						$invertedRef = ConcreteReference::invert($ref);

						if ($invertedRef instanceof ConcreteReference
							&& ($parent = $invertedRef->getParent()) instanceof Identifiable
							&& $invertedRef->getParent()->getId()
						) {
							$name = $invertedRef->getChildProperty();

							if ($form->exists($name)
								&& !$form->getValue($name)
							) {

								$form->importValue($name, $parent);
							}
						}
					}
				}
			} catch (BaseException $e){}
		}
	}

	protected function setOneToOneReference(
		HttpRequest $request
	) {
		if ($this->subject->getId()
			&& $request->hasGetVar('reference')
			&& $request->getGetVar('reference')
		) {
			try {
				$ref = ConcreteReference::make($request->getGetVar('reference'));

				if ($ref instanceof ConcreteReference
					&& ($parent = $ref->getParent()) instanceof Identifiable
					&& $ref->getParent()->getId()
					&& $ref->getRelationId() == MetaRelation::ONE_TO_ONE
				) {
					$name = $ref->getParentProperty();
					$parent = $ref->getParent();
					$proto = $parent->proto();

					if ($proto->isPropertyExists($name)) {
						$p = $proto->getPropertyByName($name);

						if ($p->getRelationId() == MetaRelation::ONE_TO_ONE) {
							$setter = $proto->
								getPropertyByName($name)->
								getSetter();

							$parent->{$setter}($this->subject);

							$parent->dao()->take($parent);
						}
					}
				}
			} catch (BaseException $e){}
		}
	}

	protected function obtainNextPosition()
	{
		if ($this->subject instanceof Sortable) {
			$max = Criteria::create($this->subject->dao())->
				setProjection(Projection::max('position', 'max'))->
				getCustom('max');

			$next = $max + 1;

			$this->subject->setPosition($next);
		}
	}

	protected function runBeforeInsertTriggers(HttpRequest $httpRequest, CommandContext $context)
	{
//		$triggers = $request->getTriggers();
//
//		if (isset($triggers[SCETriggerContext::BEFORE_INSERT])
//			&& ($list = $triggers[SCETriggerContext::BEFORE_INSERT])
//		) {
//			foreach ($list as $field => $fieldTriggers) {
//				foreach ($fieldTriggers as $trMethod) {
//					$context = SCETriggerContext::beforeInsert(
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

	protected function runAfterInsertTriggers(HttpRequest $httpRequest, CommandContext $context)
	{
//		$triggers = $request->getTriggers();
//
//		if (isset($triggers[SCETriggerContext::AFTER_INSERT])
//			&& ($list = $triggers[SCETriggerContext::AFTER_INSERT])
//		) {
//			foreach ($list as $field => $fieldTriggers) {
//				foreach ($fieldTriggers as $trMethod) {
//					$context = SCETriggerContext::afterInsert(
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