<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Identifiable;
use OnPHP\Core\Exception\BaseException;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentDropCommand;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;

class DropCommand extends BaseTransactionCommand implements IContextualCommand
{
	/**
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel
	{
		$form = $context->getForm();

		Assert::isTrue($form->exists('id'));

		$form->
			import($httpRequest->getPost())->
			importMore($httpRequest->getGet());

		$form->markGood('id');

		$this->dropRelation($httpRequest);

		return ConsistentDropCommand::create()->
			run(
				$this->subject,
				$form,
				$httpRequest
			);
	}

	protected function dropRelation(HttpRequest $request)
	{
		if ($request->hasGetVar('relation')
			&& ($relStr = $request->getGetVar('relation'))
		) {
			try {
				$relation = ObjectRelation::createFromString($relStr);

				if ($relation instanceof ObjectRelation
					&& $relation->isOneToOne()
					&& ($parent = $relation->getParent()) instanceof Identifiable
					&& $relation->getParent()->getId()
				) {
					$protoProperty = $parent->proto()->
						getPropertyByName($relation->getParentProtoProperty());

					$dropper = $protoProperty->getDropper();

					$parent->{$dropper}();
					$parent->dao()->save($parent);
				}
			} catch (BaseException $e){}
		}
	}
}