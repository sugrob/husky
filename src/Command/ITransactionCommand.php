<?php

namespace Husky\Command;

use Husky\Flow\EditorRequest;

interface ITransactionCommand
{
	public function begin();

	public function commit();
	
	public function rollback();

	public function getDaoList();
}