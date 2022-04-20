<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\ObjectNotFoundException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Filters\JsonDecoderFilter;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Form\Primitive;
use OnPHP\Core\Logic\Expression;
use OnPHP\Core\OSQL\OrderBy;
use OnPHP\Core\OSQL\QueryResult;
use OnPHP\Main\Base\NamedTree;
use OnPHP\Main\Criteria\Criteria;
use OnPHP\Main\Flow\HttpRequest;
use OnPHP\Main\Flow\Model;
use sugrob\OnPHP\Toolkit\Base\Sortable\Sortable;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;
use Husky\Logic\ListFilterQueryBuilder;

class ListCommand extends BaseEditorCommand implements IContextualCommand
{
	const DEFAULT_SORT = 'desc';
	const DEFAULT_LIST_LIMIT = 25;

	/**
	 * @var Form
	 */
	protected $listerForm;

	public function __construct(Prototyped $subject)
	{
		parent::__construct($subject);

		$this->listerForm = Form::create()->
			add(
				Primitive::choice('s')->
					setList(array('asc'=>'asc', 'desc'=>'desc'))->
					setRequired(false)->
					setDefault(self::DEFAULT_SORT)
			)->
			add(
				Primitive::string('o')->
					setRequired(false)
			)->
			add(
				Primitive::integer('page')->
					setMin(1)->
					setRequired(false)->
					setDefault(1)
			)->
			add(
				Primitive::integer('limit')->
					setRequired(false)->
					setDefault(self::DEFAULT_LIST_LIMIT)
			)->
			add(
				Primitive::string('filter')->
					setRequired(false)
			);
	}

	/**
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel
	{
		$model = ConsistentModel::create();

		$this->configureListerForm($httpRequest);

		$this->listerForm->
			import($httpRequest->getGet())->
			importMore($httpRequest->getPost());

		$criteria = $this->makeCriteria($context);

//		echo "<pre>";
//		print_r($context->getViewConfiguration()->getFilter());
//		echo "</pre>";
//		die;
		if ($filter = $this->listerForm->getValue("filter")) {
			$queryBuilder = new ListFilterQueryBuilder($this->subject);
			$queryBuilder->addParametersToCriteria(
				$criteria,
				$context->getViewConfiguration()->getFilter(),
				json_decode($filter, true)
			);

//			echo $criteria->toString();
//			die;
		}

		try {
			$this->subject->dao()->uncacheLists();
			$result = $criteria->getResult();
		} catch (ObjectNotFoundException $e) {
			$result = QueryResult::create();
		}

		$params = $this->getListParameters((int)$result->getCount());

		$this->addListToModel($model, $context, $result);

		$model->
//			set('filter', $filter)->
			set('listParameters', $params);

//		if (!$context->isAjax()) {
			$model->
				set('objectList', $result->getList())->
				set('listForm', $this->listerForm)->
				set('criteria', $criteria)->
				set('queryResult', $result);
//		}

		return $model;
	}

	protected function getListParameters($count = 0)
	{
		return array(
			'o' => $this->listerForm->getValueOrDefault('o'),
			's' => $this->listerForm->getActualChoiceValue('s'),
			'count' => $count,
			'limit' => $this->listerForm->getValueOrDefault('limit'),
			'page' => $this->listerForm->getValueOrDefault('page'),
		);
	}

	/**
	 * @param CommandContext $context
	 * @return Criteria
	 */
	protected function makeCriteria(CommandContext $context)
	{
		$form = $this->listerForm;

		$order = OrderBy::create($form->getValueOrDefault('o'));

		if ($form->getValueOrDefault('s') == 'asc') {
			$order->asc();
		} else {
			$order->desc();
		}

		$limit = $form->getValueOrDefault('limit');
		$page = $form->getValueOrDefault('page');

		$criteria = Criteria::create($this->subject->dao())->
			setOffset(($page - 1) * $limit)->
			setLimit($limit);

//		if ($request->getListGroupBy()) {
//			$groupOrder = OrderBy::create($request->getListGroupBy()->getGroupByProperty());
//
//			if ($request->getListGroupBy()->isGroupOrderDirectionAsc()) {
//				$groupOrder->asc();
//			} else {
//				$groupOrder->desc();
//			}
//
//			$criteria->addOrder($groupOrder);
//		}

		$criteria->addOrder($order);

		if ($relation = $context->getRelation()) {
			if ($relation->isOneToOne()) {
				// @TODO need test it. Maybe this case is not work properly every time
				$relation = ObjectRelation::invert($relation);
			}

			if ($relation->getParent()->getId()) {
				$criteria->
					add(
						Expression::eq(
							$relation->getChildProperty(),
							$relation->getParent()->getId()
						)
					);

				if($this->subject instanceof NamedTree
					&& $relation->getChildProperty() !== 'parent'
				) {
					// Select only root nodes if parent is not set
					$criteria->
						add(
							Expression::isNull('parent')
						);
				}
			} elseif($this->subject instanceof NamedTree) {
				// Select only root nodes if relation (and parent) is not set
				$criteria->
					add(
						Expression::isNull(
							$relation->getChildProperty()
						)
					);
			} else {
				throw new WrongArgumentException(
					"Wrong value for {$relation->getChildField()}:"
						. $relation->getParent()->getId()
				);
			}
		}

		return $criteria;
	}

	protected function configureListerForm()
	{
		if ($this->subject instanceof Sortable) {
			$this->listerForm->get('o')->setDefault('position');
		} else {
			$this->listerForm->get('o')->setDefault('id');
		}
	}

	protected function addListToModel(
		Model $model,
		CommandContext $context,
		QueryResult $result
	) {
		$list = $result->getList();
//		$groups = array();

		$model->set('list', $list);

//		if ($context->getListGroupBy() instanceof SCEListGroupBy) {
//
//			$groupToStringMethod = $context->getListGroupBy()->getToStringMethod();
//
//			foreach ($list as $item) {
//				$parentObject = MethodResolver::resolveObject(
//						$item,
//						$context->getListGroupBy()->getGroupByProperty()
//					);
//
//				$groupName = "";
//
//				if ($parentObject) {
//					$groupName = MethodResolver::resolveMethod(
//						$parentObject,
//						$groupToStringMethod
//					)->run();
//
//					if (isset($groups[$parentObject->getId()])) {
//						$groups[$parentObject->getId()]["childrenIds"][] = $item->getId();
//					} else {
//						$groups[$parentObject->getId()] = array(
//							"id" => $parentObject->getId(),
//							"name" => $groupName,
//							"childrenIds" => array($item->getId())
//						);
//					}
//				}
//			}
//		}
//
//		$model->set('groups', array_values($groups));
	}
}