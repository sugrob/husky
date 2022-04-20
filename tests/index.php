<?php

require '../../config.inc.php';

session_name(SESSION_NAME);
\OnPHP\Core\Base\Session::start();

$controller = \Husky\Flow\BaseEditor::create();
$mav = $controller->handleRequest(\OnPHP\Main\Flow\HttpRequest::createFromGlobals());

$view = $mav->getView();
$model = $mav->getModel();

if (!$view instanceof View) {
	$viewPath = $view;

	\OnPHP\Core\Base\Assert::isTrue(
		is_readable($viewPath),
		"Template path is not readeble"
	);

	$view = \OnPHP\Main\UI\View\PhpViewResolver::create()->resolveViewName($viewPath);
}

$view->render($model);