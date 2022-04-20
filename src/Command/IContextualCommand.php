<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use OnPHP\Core\Exception\BaseException;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;

interface IContextualCommand
{
	/**
	 * @throws BaseException
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel;
}