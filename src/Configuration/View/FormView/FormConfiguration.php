<?php

namespace Husky\Configuration\View\FormView;

use Husky\Configuration\Parser\TemplateElementParser;
use Husky\Configuration\View\IEditableViewConfiguration;
use Husky\Configuration\View\BaseViewConfiguration;
use Husky\Configuration\Component\WebForm\WebForm;
use Husky\Configuration\ConfigurationPathResolver;
use Husky\Configuration\View\IStaticTemplateView;
use Husky\UI\Control;
use OnPHP\Core\Exception\MissingElementException;
use OnPHP\Core\Form\Form;
use OnPHP\Core\Exception\WrongArgumentException;
use sugrob\OnPHP\Toolkit\Xml\SimpleXMLWrapper;

class FormConfiguration
	extends BaseViewConfiguration
	implements IEditableViewConfiguration, IStaticTemplateView
{
	/**
	 * @var Form
	 */
	protected $form;

	/**
	 * @var WebForm
	 */
	protected $webForm;
	protected $template;

	/**
	 * @param $subject
	 * @return FormConfiguration
	 */
	public static function create($subject)
	{
		return new self($subject);
	}

	public function __construct($subject)
	{
		parent::__construct($subject);

		$this->form = $this->subject->proto()->makeForm();
		$this->webForm = new WebForm($this->subject);
	}

	/**
	 * @return Form
	 */
	public function getForm(): Form
	{
		return $this->form;
	}

	public function getConsistency(): array
	{
		return array(
			'exceptions' => $this->webForm->getExceptions(),
			'errors' => $this->webForm->getErrorMessages()
		);
	}

	/**
	 * @return WebForm
	 */
	public function getWebForm(): WebForm
	{
		return $this->webForm;
	}

	public function getTemplatePath(): string
	{
		return $this->template;
	}

	public function toArray(): array
	{
		return array();
	}

	public function loadXML($file)
	{
		$this->recursiveLoadXML($file);
		$this->webForm->reconfigureForm();
	}

	private function recursiveLoadXML($file)
	{
		$xml = new SimpleXMLWrapper($file, 0, true);

		if ($xml->hasAttribute("parent")) {
			$parentPath = ConfigurationPathResolver::me()->resolvePath($xml->getAttribute("parent"));
			$this->recursiveLoadXML($parentPath);
		}

		$this->parseTemplateElement($xml);
		$this->parseControls($xml);
		$this->parseWebForm($xml);
		$this->parseHandlers($xml);
	}

	private function parseWebForm(SimpleXMLWrapper $xml)
	{
		if ($xml->form[0]) {
			// Methods call order is important
			$this->webForm->parse($xml);
		}
	}

	/**
	 * @param SimpleXMLWrapper $xml
	 * @throws WrongArgumentException
	 */
	private function parseControls(SimpleXMLWrapper $xml)
	{
		if ($xml->controls[0]) {
			// Renew controls
			$this->controls = array();

			foreach ($xml->controls[0] as $appearenceNode) {
				$appiarence = $appearenceNode->getName();

				$this->controls[$appiarence] = array();

				if ($appearenceNode[0]) {
					foreach ($appearenceNode as $controlNode) {
						$control = $this->parseControl($controlNode);
						$this->controls[$appiarence][$control->getActionName()] = $control;
					}
				}
			}
		}
	}

	private function parseTemplateElement(SimpleXMLWrapper $xml)
	{
		try {
			$this->template = TemplateElementParser::parse($xml, $this->subject);
		} catch (MissingElementException $e) {
			/* Do nothing */
		}
	}
}