<?php

namespace Husky\Command;

use Husky\Command\BaseEditorCommand;
use Husky\Command\IContextualCommand;
use Husky\Flow\CommandContext;
use OnPHP\Core\Base\Session;
use OnPHP\Main\Flow\HttpRequest;
use OnPHP\Main\Util\ClassUtils;
use sugrob\OnPHP\Toolkit\Flow\ConsistentModel;

class ExportTempUploadedFilesCommand  extends BaseEditorCommand implements IContextualCommand
{
	/**
	 * @param HttpRequest $httpRequest
	 * @param CommandContext $context
	 * @return ConsistentModel
	 */
	public function run(HttpRequest $httpRequest, CommandContext $context): ConsistentModel
	{
		$model = ConsistentModel::create();

		try {
			if ($this->subject->getId() == null) {
//				$temp = Session::get(SmartCommandUploadFile::TEMP_UPLOAD_SESSION_VAR);
				$temp = Session::get('TEMP_UPLOAD_SESSION_VAR');
				$class = ClassUtils::getShortClassName($this->subject);

				if (!empty($temp[$class])) {
					$proto = $this->subject->proto();

					foreach ($temp[$class] as $name => $fileData) {
						if (!empty($fileData['url'])
							&& is_readable($fileData['path'])
						) {
							$property = $proto->getPropertyByName($name);
							$setter = $property->getSetter();
							$this->subject->{$setter}($fileData['url']);
						}
					}
				}
			}
		} catch (WrongArgumentException $e) {}

		/**
		 * @TODO Fill model
		 */

		return $model;
	}
}

?>