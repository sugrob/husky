<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Form\Form;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentEditCommand;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;

class CopyCommand extends BaseEditorCommand implements IContextualCommand
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

		$model = ConsistentEditCommand::create()->
			run(
				$this->subject,
				$form,
				$httpRequest
			);

		$form->get("id")->clean();

		$form->dropAllErrors();

		return $model;
	}
}