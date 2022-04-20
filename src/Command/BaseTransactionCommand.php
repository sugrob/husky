<?php

namespace Husky\Command;

use OnPHP\Core\DB\DBPool;
use OnPHP\Main\DAO\StorableDAO;

class BaseTransactionCommand extends BaseEditorCommand implements ITransactionCommand
{
	public function getDaoList()
	{
		return array(
			$this->subject->dao()
		);
	}

	public function begin()
	{
		$this->daosWalk(function(StorableDAO $dao) {
			$db = DBPool::getByDao($dao);

			if (!$db->inTransaction()) {
				$db->begin();
			}
		});
	}

	public function commit()
	{
		$this->daosWalk(function(StorableDAO $dao) {
			$db = DBPool::getByDao($dao);

			if ($db->inTransaction()) {
				$db->commit();
			}
		});
	}

	public function rollback()
	{
		$this->daosWalk(function(StorableDAO $dao) {
			$db = DBPool::getByDao($dao);

			if ($db->inTransaction()) {
				$db->rollback();
			}
		});
	}

	private function daosWalk(callable $callback)
	{
		if ($daoList = $this->getDaoList()) {
			foreach ($daoList as $dao) {
				$callback($dao);
			}
		}
	}
}