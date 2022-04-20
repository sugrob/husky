<?php

namespace Husky\Configuration;

use Husky\Flow\Action;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Exception\ClassNotFoundException;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Base\Assert;
use OnPHP\Main\UI\View\View;
use OnPHP\Main\Util\ClassUtils;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class EditorConfiguration extends ProtoConfiguration {
	protected $views = array();
	protected $viewConfigurtions = array();
	protected $actions = array();
	protected $resources = array();
	
	public static function create($subject) 
	{
		return new self($subject);
	}

	/**
	 * @return array
	 */
	public function getActions(): array
	{
		return $this->actions;
	}

	/**
	 * @return array
	 */
	public function getResources(): array
	{
		return $this->resources;
	}

	/**
	 * @return array
	 */
	public function getViews(): array
	{
		return $this->views;
	}

	public function getView($name): View
	{
		Assert::isIndexExists($this->views, $name, "Know's nothing about view: {$name}");
		return $this->views[$name];
	}

	public function getViewConfigurations()
	{
		return $this->viewConfigurtions;
	}

	public function viewConfigurationExists(string $name)
	{
		return isset($this->viewConfigurtions[$name]);
	}

	public function getViewConfiguration(string $name)
	{
		return isset($this->viewConfigurtions[$name])
			? $this->viewConfigurtions[$name]
			: null;
	}

	/**
	 * @return EditorConfiguration
	 * @throws MissingElementException
	 * @throws WrongArgumentException
	 */
	public function load(): EditorConfiguration
	{
		$configurationFile = ConfigurationPathResolver::me()->
			resolveEditorConfigurationPath($this->subject);

		$this->loadXML($configurationFile);

		return $this;
	}

	public function loadXML($file)
	{
		$this->recursiveLoadXML($file);
	}

	private function recursiveLoadXML($file)
	{
		$xml = new SimpleXMLWrapper($file, 0, true);

		if ($xml->hasAttribute("parent")) {
			$parentPath = ConfigurationPathResolver::me()->resolvePath($xml->getAttribute("parent"));
			$this->recursiveLoadXML($parentPath);
		}

		// Methods call order is important
		$this->parseViews($xml);
		$this->parseActions($xml);
		$this->parseResources($xml);
	}

	/**
	 * @return Prototyped
	 */
	protected function getSubject()
	{
		return $this->subject;
	}

	/**
	 * @param string $name
	 * @throws WrongArgumentException
	 */
	private function assertViewIndexExist($name)
	{
		Assert::isIndexExists(
			$this->views,
			$name,
			"Know's nothing about view: {$name}"
		);
	}

	/**
	 * @param SimpleXMLWrapper $xml views node
	 * @throws ClassNotFoundException
	 */
	private function parseViews(SimpleXMLWrapper $xml)
	{
		if ($xml->views[0]) {
			// Renew views
			$this->views = array();

			foreach ($xml->views[0] as $viewNode) {
				$name = $viewNode->getAttribute("name");
				$class = $viewNode->getAttribute("class");

				Assert::classExists(
					$class,
					"Know's nothing about view class: {$class}"
				);

				$this->views[$name] = new $class;

				if ($viewNode->include) {
					$configuratonClass = $viewNode->include->getAttribute("configuration");

					Assert::classExists(
						$configuratonClass,
						"View configuration class is not exist: {$configuratonClass}"
					);

					$viewConfiguration = new $configuratonClass($this->subject);

					Assert::isInstance($viewConfiguration, ProtoConfiguration::class);

					$path = $viewNode->include->getAttribute("path");
					$path = $this->resolvePath($path);

					$viewConfiguration->loadXML($path);
					$this->viewConfigurtions[$name] = $viewConfiguration;
				}
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml actions node
	 * @throws WrongArgumentException
	 * @throws ClassNotFoundException
	 */
	private function parseActions(SimpleXMLWrapper $xml)
	{
		if ($xml->actions[0]) {
			// Renew actions
			$this->actions = array();

			$defaultFound = false;

			foreach ($xml->actions[0] as $actionNode) {
				$name = $actionNode->getAttribute("name");
				$viewName = $actionNode->getAttribute("view");
				$aclRightId = $actionNode->getAttribute("aclRightId");
				$default = $actionNode->getAttribute("default") == "true";

				Assert::isFalse(
					($defaultFound && $default),
					"Only one default action expected"
				);

				$this->assertViewIndexExist($viewName);

				$action = Action::create()->
					setName($name)->
					setViewName($viewName)->
					setAclRightId($aclRightId)->
					setDefault($default);

				if ($actionNode->commands[0]) {
					foreach ($actionNode->commands[0] as $commandNode) {
						$commandClass = $commandNode->getAttribute("class");

//						Assert::classExists(
//							$commandClass,
//							"Know's nothing about command class: {$commandClass}"
//						);

						$action->addCommand($commandClass);
					}
				}

				$this->actions[$name] = $action;
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	private function parseResources(SimpleXMLWrapper $xml)
	{
		if ($xml->resources[0]) {
			// Renew views
			$this->references = array("js" => [], "css" => []);

			foreach ($xml->references as $res) {
				$type = $res->getName();
				$src = $res->getAttribute("src");

				Assert::isNotEmpty($src,"Empty resource src");

				$this->resources[$type][] = $src;
			}
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
//	protected function parseIncludes(SimpleXMLWrapper $xml)
//	{
//		if ($xml->includes[0]) {
//			Assert::isTrue(
//				defined("HUSKY_USER_CONFIGURATION_PATH"),
//				"Includesfound but HUSKY_USER_CONFIGURATION_PATH is not defined"
//			);
//
//			Assert::isTrue(
//				(
//					is_dir(HUSKY_USER_CONFIGURATION_PATH)
//					&& is_readable(HUSKY_USER_CONFIGURATION_PATH)
//				),
//				"HUSKY_USER_CONFIGURATION_PATH is not properly defined"
//			);
//
//			$userPath = rtrim(HUSKY_USER_CONFIGURATION_PATH, "/");
//
//			$patternVariables = array(
//				'userPath' => $userPath,
//				'subjectClass' => $this->getSubjectShortName()
//			);
//
//			foreach ($xml->includes[0] as $include) {
//				$componentName = $include->getAttribute("component");
//				$parserClass = $include->getAttribute("parser");
//
//				Assert::classExists(
//					$parserClass,
//					"Parser class is not exist: {$parserClass}"
//				);
//
//				$parser = new $parserClass;
//
//				$file = $include->getAttribute("file");
//				$this->parseFilePathPattern($file, $patternVariables);
//
//				Assert::isTrue(
//					is_file($file) && is_readable($file),
//					"Configuration file not found: {$file}"
//				);
//
//				$this->loadComponentConfiguration($parser, $componentName, $file);
//			}
//		}
//	}

	private function resolvePath($path)
	{
		return ConfigurationPathResolver::me()->
			resolvePath(
				$path,
				["subject" => ClassUtils::getShortClassName($this->subject)]
			);
	}
}