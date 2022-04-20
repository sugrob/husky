<?php

namespace Husky\Configuration;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Instantiatable;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Core\Base\Singleton;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Exception\WrongArgumentException;
use ReflectionClass;

final class ConfigurationPathResolver extends Singleton implements Instantiatable
{
	const BASE_CONFIGURATION_PATH_ALIAS = "base";
	const USER_CONFIGURATION_PATH_ALIAS = "user";

	private $paths = array();

	/**
	 * @return ConfigurationPathResolver
	 * @throws MissingElementException
	 */
	public static function me()
	{
		return Singleton::getInstance(self::class);
	}

	/**
	 * ConfigurationPathResolver constructor.
	 * @throws MissingElementException
	 * @throws WrongArgumentException
	 */
	protected function __construct()
	{
		if (!defined("HUSKY_BASE_EDITOR_CONFIGURATION")) {
			throw new MissingElementException('HUSKY_BASE_EDITOR_CONFIGURATION - is not defined');
		}

		$this->addIncludePath(self::BASE_CONFIGURATION_PATH_ALIAS, HUSKY_BASE_CONFIGURATION_PATH);

		if (!defined("HUSKY_USER_CONFIGURATION_PATH")) {
			throw new MissingElementException('HUSKY_USER_CONFIGURATION_PATH - is not defined');
		}

		$this->addIncludePath(self::USER_CONFIGURATION_PATH_ALIAS, HUSKY_USER_CONFIGURATION_PATH);
	}

	/**
	 * @param string $key
	 * @param string $path
	 * @return ConfigurationPathResolver
	 * @throws WrongArgumentException
	 */
	public function addIncludePath(string $key, string $path): ConfigurationPathResolver
	{
		if (is_dir($path)
			&& is_readable($path)
		) {
			$this->paths[$key] = rtrim($path, DIRECTORY_SEPARATOR);

			return $this;
		}

		throw new WrongArgumentException("Include path is not a valid dir: ".$path);
	}

	public function resolvePath(string $template, array $externalVars = []): string
	{
		foreach ($this->paths as $key => $path) {
			$externalVars[$key] = $path;
		}

		$path = preg_replace_callback(
			"/\{([\w]+)\}/",
			function ($matches) use ($externalVars) {
				if (isset($matches[1]) && isset($externalVars[$matches[1]])) {
					return $externalVars[$matches[1]];
				} else {
					throw new WrongArgumentException(
						"Wrong include file pattern. "
						."Don't know about variable: '".$matches[1]."'"
					);
				}
			},
			$template
		);

		$path = str_replace(array('\\', '/'),DIRECTORY_SEPARATOR, $path);

		Assert::isTrue(
			is_file($path) && is_readable($path),
			"Couldn't resolve include path {$path}."
		);

		return $path;
	}

	/**
	 * @param Prototyped $subject
	 */
	public function resolveEditorConfigurationPath(Prototyped $subject)
	{
		$ref = new ReflectionClass($subject);

		foreach ($this->paths as $path) {
			$path = rtrim($path, DIRECTORY_SEPARATOR)
				. DIRECTORY_SEPARATOR
				. $ref->getShortName()
				. DIRECTORY_SEPARATOR
				. "editor.xml";

			if (file_exists($path)) {
				return $path;
			}
		}

		if (!defined("HUSKY_BASE_EDITOR_CONFIGURATION")) {
			throw new WrongArgumentException(
				"HUSKY_BASE_EDITOR_CONFIGURATION is not defined"
			);
		}

		return HUSKY_BASE_EDITOR_CONFIGURATION;
	}
}