<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Form\Form;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentEditCommand;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;

class EditCommand extends BaseEditorCommand implements IContextualCommand
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

		$form->
			importOne('id', $httpRequest->getGet())->
			importMore($httpRequest->getPost());

		$this->importOneToManyRelation($form, $httpRequest);

		$model = ConsistentEditCommand::create()->
			run(
				$this->subject,
				$form,
				$httpRequest
			);

		$form->dropAllErrors();

		return $model;
	}

	protected function importOneToManyRelation(
		Form $form,
		HttpRequest $request
	) {
		if ($request->hasGetVar('relation')
			&& ($relStr = $request->getGetVar('relation'))
		) {
			try {
				$rel = ObjectRelation::createFromString($relStr);

				if ($rel instanceof ObjectRelation
					&& ($parent = $rel->getParent()) instanceof Identifiable
					&& $rel->getParent()->getId()
				) {
					if ($rel->isOneToMany()) {

						$name = $rel->getChildProperty();

						if ($form->exists($name)
							&& !$form->getValue($name)
						) {
							$form->importValue($name, $parent);
						}

					} elseif ($rel->isOneToOne()) {
						$invertedRef = ObjectRelation::invert($rel);

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
					} elseif ($rel->isManyToMany()) {
						// @TODO... is it real case?
					}
				}
			} catch (BaseException $e){}
		}
	}
}