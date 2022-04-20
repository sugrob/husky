<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Identifiable;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\BaseException;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Form\Primitive;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentDropCommand;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;


class MoveCommand extends ListCommand implements IContextualCommand
{
	public function __construct(Prototyped $subject)
	{
		parent::__construct($subject);

		$this->form = Form::create()->
			add(
				Primitive::set('ids')->
					required()
			);
	}

	/**
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel
	{
		$form = $this->form->
			import($httpRequest->getGet())->
			importMore($httpRequest->getPost());

		$model = ConsistentModel::create();

		echo "<pre>";
		print_r($form->getValue('ids'));
		echo "</pre>";die;

		if ($this->subject instanceof Sortable) {
			try {
				echo "<pre>";
				print_r($form->get('ids'));
				echo "</pre>";
//				if (!$form->getValue('p'))
//					throw new WrongStateException('Items for moving is empty');
//
//				$dao = $request->getSubject()->dao();
//
//				foreach ($form->getValue('p') as $id => $position) {
//					$object = $dao->getById((int)$id)->setPosition($position);
//					$dao->save($object);
//				}

			} catch (BaseException $e){
//				$model->
//					set('successful', false)->
//					set(
//						'error',
//						$e->getMessage()
//					);
			}
		} else {
//			$model->
//				set('successful', false)->
//				set(
//					'error',
//					'Операция сортировки не поддерживается объектом данного типа'
//				);
		}

		return $model;
	}
}

?>