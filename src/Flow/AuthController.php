<?php

namespace Husky\Flow;

use Husky\Classes\Business\Admin;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Form\Filter;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Form\Primitive;
use OnPHP\Core\Logic\Expression;
use OnPHP\Main\Flow\Controller;
use OnPHP\Main\Flow\HttpRequest;
use OnPHP\Main\Flow\Model;
use OnPHP\Main\Flow\ModelAndView;
use OnPHP\Main\Net\HttpUrl;
use PHPUnit\Util\Exception;
use sugrob\OnPHP\Acl\Auth\AuthManager;
use sugrob\OnPHP\Acl\Base\IAclUser;
use sugrob\OnPHP\Acl\Exception\BadLoginException;
use sugrob\OnPHP\Acl\Exception\BadPasswordException;
use sugrob\OnPHP\Acl\Exception\InvalidPasswordException;
use sugrob\OnPHP\Acl\Exception\UserNotActivatedException;
use sugrob\OnPHP\Acl\Exception\UserNotFoundException;
use sugrob\OnPHP\Acl\Exception\UserRemovedException;

class AuthController implements Controller
{
	const LOGOUT_ACTION = "logout";
	const LOGIN_ACTION  = "login";

	/**
	 * @var Form
	 */
	protected $form = null;

	/**
	 * @var HttpUrl
	 */
	protected $referer = null;

	public function __construct()
	{
		$this->form = Form::create()->
			add(
				Primitive::choice('action')->
					setList(
						array(
							self::LOGIN_ACTION => self::LOGIN_ACTION,
							self::LOGOUT_ACTION => self::LOGOUT_ACTION
						)
					)->
					setDefault(self::LOGIN_ACTION)->
					optional()
			)->
			add(
				Primitive::string('adminLogin')->
					setImportFilter(Filter::textImport())->
					setMax(64)->
					required()
			)->
			add(
				Primitive::string('adminPassword')->
					setImportFilter(Filter::textImport())->
					setMin(6)->
					setMax(64)->
					required()
			)->
			add(
				Primitive::string('referer')->
					optional()
			)->
			addRule('invalidPassword', Expression::isTrue(true))->
			addRule('otherError', Expression::isTrue(true));

	}

	/**
	 * @return ModelAndView
	 */
	public function handleRequest(HttpRequest $request)
	{
		$form = $this->form;
		$authManager = AuthManager::me();

		if (!$authManager->getNullUser() instanceof IAclUser) {
			$authManager->setUserClass(Admin::create());
		}

		$form->import($request->getPost());
		$form->importOneMore('action', $request->getGet());

		$mav = ModelAndView::create()->setModel(Model::create());

		$action = $form->getValueOrDefault('action');

		switch ($action) {
			case self::LOGOUT_ACTION:
				if ($authManager::isAuth()) {
					$authManager::logout();
				}
				break;

			case self::LOGIN_ACTION:
				{
					if ($authManager->isAuth()) {
						//return $mav->setView(RedirectView::create(ADMIN_URL.'index.php'));
					}

					if (!$form->getErrors()) {
						try {
							if (
								$authManager->auth(
									$form->getValue('adminLogin'),
									$form->getValue('adminPassword')
								)
							) {
								return $mav->setView(RedirectView::create(APP_URL . 'index.php'));
							}
						} catch (InvalidPasswordException $e) {
							$form->markWrong("invalidPassword");
						} catch (BadPasswordException $e) {
						} catch (BadLoginException $e) {
						} catch (UserNotFoundException $e) {
						} catch (UserNotActivatedException $e) {
						} catch (UserRemovedException $e) {
							$form->markWrong("otherError");
						}
					}
				}
				break;
			default:
				throw new WrongArgumentException("Unknown action: " . $action);
		}

		$mav->getModel()->set('form', $form);

		return $mav->setView('auth');
	}
}