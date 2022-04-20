<?php

namespace Husky\Command;

use Husky\Flow\CommandContext;
use Husky\Flow\EditorRequest;
use OnPHP\Core\Base\Assert;
use OnPHP\Core\DB\DBPool;
use OnPHP\Core\Exception\BaseException;
use OnPHP\Core\Exception\DatabaseException;
use OnPHP\Core\Exception\IOException;
use OnPHP\Core\Exception\WrongArgumentException;
use OnPHP\Core\Exception\WrongStateException;
use OnPHP\Main\DAO\DAOConnected;
use OnPHP\Main\Flow\HttpRequest;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;
use ITransactionCommand;

class EditorCommandChain
{
	protected $chain = array();
	protected $executed = array();

	/**
	 * @return EditorCommandChain
	 */
	public static function create()
	{
		return new self;
	}

	/**
	 * @param IContextualCommand $command
	 * @return EditorCommandChain
	 */
	public function add(IContextualCommand $command)
	{
		$this->chain[] = $command;
		return $this;
	}

	public function walk($callback) {
		foreach ($this->chain as $command) {
			$callback($command);
		}
	}

	/**
	 * @param HttpRequest $request
	 * @param CommandContext $context
	 * @param ConsistentModel $model
	 * @return ConsistentModel
	 * @throws DatabaseException
	 * @throws IOException
	 * @throws WrongArgumentException
	 * @throws WrongStateException
	 */
	public function run(
		HttpRequest $request,
	    CommandContext $context,
		ConsistentModel $model
	): ConsistentModel
	{
		Assert::isTrue(
			($size = count($this->chain)) > 0,
			'Сommand chain is empty'
		);

		try {
			$executed = array();

			for ($i = 0; $i < $size; ++$i) {
				$command = &$this->chain[$i];

				if ($command instanceof ITransactionCommand) {
					$command->begin();
				}

				$commandModel = $command->run($request, $context);
				$this->executed[] = $command;
				$model->merge($commandModel);

				if (!$commandModel->isSuccessful()) {
					$this->rollback();
					return $model;
				}
			}

		} catch (BaseException $e) {
			$this->rollback();
			throw $e;
		}

		$this->commit();

		return $model;
	}

	private function commit()
	{
		foreach ($this->executed as $command) {
			if ($command instanceof ITransactionCommand) {
				$command->commit();
			}
		}
	}

	private function rollback()
	{
		foreach ($this->executed as $command) {
			if ($command instanceof ITransactionCommand) {
				$command->rollback();
			}
		}
	}


}