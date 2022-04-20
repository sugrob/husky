<?php

namespace Husky\Logic;

use Husky\Configuration\Component\ListFilter\ListFilter;
use Husky\Configuration\Component\WebForm\Element\ElementBase;
use Husky\Configuration\Component\WebForm\Element\ElementBaseSearch;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Logic\Expression;
use OnPHP\Core\OSQL\DBField;
use OnPHP\Main\Criteria\Criteria;

class ListFilterQueryBuilder
{
	/**
	 * @var Prototyped
	 */
	private $subject;

	/**
	 * @var Form
	 */
	private $form;

	/**
	 * @var array
	 */
	private $logicList = array();

	public function __construct(Prototyped $subject)
	{
		$this->subject = $subject;
	}

	public function addParametersToCriteria(Criteria $criteria, ListFilter $listFilter, array $scope)
	{
		$this->makeForm($listFilter);
		$this->importForm($scope);
		$this->fillLogicList($listFilter, $scope);

		foreach ($scope as $name => $value) {
			$this->addExpressionToCriteria($criteria, $name);
		}
	}

	/**
	 * @param ListFilter $listFilter
	 * @throws MissingElementException
	 */
	private function makeForm(ListFilter $listFilter)
	{
		$this->form = $this->subject->proto()->makeForm();
		$filterElements = $listFilter->getElements();

		foreach ($this->form->getPrimitiveNames() as $primitiveName) {
			if (!array_key_exists($primitiveName, $filterElements)) {
				$this->form->drop($primitiveName);
			}
		}
	}

	/**
	 * @param array $scope
	 */
	private function importForm(array $scope)
	{
		$variables = array();

		foreach ($scope as $key => $item) {
			if (is_array($item) && isset($item['value'])) {
				$variables[$key] = $item['value'];
			} else {
				$variables[$key] = $item;
			}
		}

		$this->form->import($variables);
	}

	/**
	 * @param ListFilter $listFilter
	 * @param array $scope
	 * @throws WrongArgumentException
	 */
	private function fillLogicList(ListFilter $listFilter, array $scope)
	{
		$elements = $listFilter->getElements();

		foreach ($scope as $name => $item) {
			$logic = new FilterLogicEnumeration(FilterLogicEnumeration::EQUALS);
			$filterElement = $elements[$name];

			if ($filterElement instanceof ElementBaseSearch) {
				$allowRequestLogic = false;

				foreach ($filterElement->getExpressions() as $expr) {
					if ($expr['default']) {
						$logic = $logic->createByName($expr['logic']);

						if ($allowRequestLogic) break;
					}

					if ($item['logic'] == $expr['logic']) {
						$allowRequestLogic = true;
					}
				}
			}

			if (is_array($item) && isset($item['logic']) && $allowRequestLogic) {
				$logic = $logic->createByName($item['logic']);
			}

			$this->logicList[$name] = $logic;
		}
	}

	private function addExpressionToCriteria(Criteria $criteria, $name)
	{
		if ($this->form->exists($name)
			&& isset($this->logicList[$name])
			&& $this->logicList[$name] instanceof FilterLogicEnumeration
		) {
			$logic = $this->logicList[$name];

			if ($this->subject->proto()->isPropertyExists($name)) {
				try {
					$expression = $this->buidExpression($name);
					$criteria->add($expression);
				} catch (WrongArgumentException $e) {
					/**
					 * Seems user tried to use wrong arguments. Do nothing.
					 */
				}
			} else {
				throw new WrongArgumentException("Property $name doesn't exists");
			}
		}
	}

	private function buidExpression($propertyName)
	{
		$table = $this->subject->dao()->getTable();
		$property = $this->subject->proto()->getPropertyByName($propertyName);
		$value = $this->form->getValue($propertyName);

		return FilterExpressionBuilder::build($this->subject, $propertyName, $value, $this->logicList[$propertyName]);
	}
}