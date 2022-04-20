<?php

namespace Husky\Command;

use OnPHP\Core\Base\Assert;
use OnPHP\Core\Base\Prototyped;
use OnPHP\Main\DAO\DAOConnected;

class BaseEditorCommand
{
	/**
	 * @var Prototyped
	 */
	protected $subject;

	/**
	 * @param Prototyped $subject
	 * @return BaseEditorCommand
	 */
	public static function create(Prototyped $subject)
	{
		return new static($subject);
	}

	/**
	 * BaseEditorCommand constructor.
	 * @param DAOConnected $subject
	 */
	public function __construct(Prototyped $subject)
	{
		Assert::isInstance($subject, DAOConnected::class);
		$this->subject = $subject;
	}
}