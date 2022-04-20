<?php

namespace Husky\Command\BaseCommand;

use Husky\Command\BaseTransactionCommand;
use OnPHP\Main\DAO\DAOConnected;

abstract class StorableCommand extends BaseTransactionCommand implements IContextualCommand
{
	/**
	 * @var DAOConnected
	 */
	protected $subject;

	public static function create(DAOConnected $subject)
	{
		return new static($subject);
	}

	public function getDaoList()
	{
		return array(
			$this->subject->dao()
		);
	}
}