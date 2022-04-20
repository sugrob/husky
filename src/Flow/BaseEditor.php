<?php

namespace Husky\Flow;

use Husky\Command\EditorCommandChain;
use Husky\Configuration\EditorConfiguration;
use Husky\Configuration\View\IEditableViewConfiguration;
use Husky\Configuration\View\IStaticTemplateView;
use Husky\UI\AppView;
use Husky\UI\PrerenderableModelView;
use OnPHP\Core\Base\Identifiable;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\BaseException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Filter;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Form\Primitive;
use OnPHP\Main\DAO\DAOConnected;
use OnPHP\Main\Flow\CommandChain;
use OnPHP\Main\Flow\Controller;
use OnPHP\Main\Flow\HttpRequest;
use OnPHP\Core\Base\Assert;
use OnPHP\Main\Flow\ModelAndView;
use OnPHP\Main\UI\View\PhpViewResolver;
use OnPHP\Main\UI\View\View;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use sugrob\OnPHP\Toolkit\Flow\ObjectRelation;
use sugrob\OnPHP\Acl\Flow\ActionAccessResolver;

class BaseEditor implements Controller
{
	const DEFAULT_SORT = 'desc';
	const DEFAULT_LIST_LIMIT = 25;

	/**
	 * @var EditorConfiguration
	 */
	protected $configuration;
	
	/**
	 * @var ActionAccessResolver
	 */
	protected $actionAccessResolver;

	protected $subject;

	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @var View
	 */
	protected $view;

	/**
	 * @var Action
	 */
	protected $action;
	
	/**
	 * @var ObjectRelation
	 */
	protected $relation;

	/**
	 * @return BaseEditor
	 */
	public static function create()
	{
		return new self;
	}

	public function __construct()
	{
		$this->form = Form::create()->
			add(
				Primitive::clazz('subject')
			)->
			add(
				Primitive::string('relation')->
					optional()
			)->
			add(
				Primitive::string('listTemplate')->
					addImportFilter(Filter::textImport())->
					optional()
			)->
			add(
				Primitive::string('formTemplate')->
					addImportFilter(Filter::textImport())->
					optional()
			)->
			add(
				Primitive::boolean('ajax')->
					setRequired(false)->
					setDefault(false)
			)->
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
			);
		
		$this->actionAccessResolver = new ActionAccessResolver();
	}

	public function handleRequest(HttpRequest $request)
	{
		$this->prepare($request);

		$this->configuration = EditorConfiguration::create($this->subject)->
			load();

		$this->action = $this->getAction($request);

		$this->view = $this->configuration->getView($this->action->getViewName());
		$viewConfiguration = $this->configuration->getViewConfiguration($this->action->getViewName());

		$rootModel = ConsistentModel::create()->
			set("serviceUrl", $_SERVER['DOCUMENT_URI'])->
			set("subject", $this->subject)->
			set("action", $this->action)->
			set("configuration", $this->configuration)->
			set("viewConfigurations", $this->configuration->getViewConfigurations());

		$chain = $this->spawnCommandChain();

		$context = CommandContext::create()->
			setViewConfiguration($viewConfiguration)->
			setIsAjax($this->form->getValue('ajax'));

		if ($editForm = $this->getEditForm()) {
			$context->setForm($editForm);
		}

		if ($this->relation) {
			$context->setRelation($this->relation);
		}

		try {
			$model = $chain->run($request, $context, $rootModel);
		} catch(BaseException $e) {
			$model = $rootModel;

			$this->processError($e);

			if ($editForm) {
				foreach ($editForm->getErrors() as $name => $error) {
					$model->addError();
				}
			}
		}

		$mav = ModelAndView::create()->setModel($model);

		if ($mav->getView() && $mav->getView() instanceof RedirectView)
			return $mav;

		if (!$context->isAjax()) {
			$model->
				set('resources', $this->configuration->getResources());
		}

		if ($context->isAjax()) {
			$mav->setView($this->view);
		} else if ($viewConfiguration instanceof IStaticTemplateView) {
			if ($this->view instanceof PrerenderableModelView) {
				$mav->getModel()->
				set(
					"modelData",
					$this->view->renderModel($mav->getModel())
				);
			} else {
				throw new WrongArgumentException("View ".get_class($this->view)." must implement PrerenderableModelView");
			}

			$mav->setView(
				new AppView(
					$viewConfiguration->getTemplatePath(),
					PhpViewResolver::create()
				)
			);
		} else {
			Assert::isUnreachable("Don't know how to render view");
		}

		return $mav;
	}

	protected function prepare(HttpRequest $request)
	{
		$form = $this->form->
			import($request->getGet())->
			importMore($request->getPost())->
			importMore($request->getAttached());

		if ($relString = $form->getValue('relation')) {
			try {
				$this->relation = ObjectRelation::createFromString($relString);
			} catch (WrongArgumentException $e){}
		}

		if ($class = $form->getValue("subject")) {
			/* it's ok, let's use it */
		} elseif ($this->relation) {
			$class = $this->relation->getChildClass();
		} else {
			Assert::isUnreachable ('Undefined subject');
		}

		Assert::classExists($class);

		$this->subject = new $class;

		$this->assertSubjectIsValid($this->subject);
	}
	
	protected function getAction(HttpRequest $request)
	{
		$actions = $this->configuration->getActions();

		Assert::isGreater(
			count($actions),
			0,
			"EditorConfiguration must contain at least one action"
		);

		$prm = Primitive::choice('action')->
			setList(
				array_combine(
					array_keys($actions),
					array_keys($actions)
				)
			);

		foreach ($actions as $name => $action) {
			if ($action->getDefault()) {
				$prm->setDefault($name);
			}
		}

		Form::create()->
			add($prm)->
			import($request->getGet())->
			importMore($request->getPost())->
			importMore($request->getAttached());

		Assert::isNotEmpty(
			$actionName = $prm->getValueOrDefault(),
			"Action is undefined"
		);

		return $actions[$actionName];
	}
	
	/**
	 * @param string $action
	 * @return boolean
	 */
	protected function isAllowedAction($action)
	{
		if (isset($this->commandMap[$action])) {
			$context = AdminAclContext::dao()->
				getByClassName(get_class($this->subject));
			
			return $this->actionAccessResolver->
				isAllowedAction($action, $context);
		}

		return false;
	}
	
	/**
	 * @param string $action
	 * @return boolean
	 */
	protected function isAllowedRight(AclRight $right)
	{
		$context = AdminAclContext::dao()->
			getByClassName(get_class($this->subject));
			
		return $this->actionAccessResolver->
			isAllowedRight($right, $context);
	}

	/**
	 * @param \Exception $e
	 * @throws BaseException
	 */
	protected function processError(\Exception $e)
	{
		$form = $this->getEditForm();

		$viewConfiguration = $this->configuration->getViewConfiguration($this->action->getViewName());

		if ($viewConfiguration instanceof IEditableViewConfiguration) {
			$rules = $viewConfiguration->getRules();

			foreach ($rules as $name => $rule) {
				if ($form->ruleExists($name)
					&& stristr($e->getMessage(), $rule["marker"])
				){
					$form->markWrong($name);
					return;
				}
			}
		}

		throw $e;
	}

	/**
	 * @return Form|null
	 */
	protected function getEditForm()
	{
		if ($viewConfiguration =
			$this->configuration->getViewConfiguration($this->action->getViewName())
		) {
			if ($viewConfiguration instanceof  IEditableViewConfiguration) {
				return $viewConfiguration->getForm();
			}
		}
	}

	/**
	 * @return EditorCommandChain
	 */
	protected function spawnCommandChain()
	{
		$chain = EditorCommandChain::create();

		foreach ($this->action->getCommands() as $command) {
			$chain->add(new $command($this->subject));
		}

		return $chain;
	}

	/**
	 * @param $subject
	 * @throws WrongArgumentException
	 */
	protected function assertSubjectIsValid($subject)
	{
		Assert::isTrue(
			$this->subject instanceof Identifiable
			&& $this->subject instanceof Prototyped
			&& $this->subject instanceof DAOConnected,
			'Wrong subject class'
		);
	}
}