<?php namespace Joomla\Plugin\System\QuantumManagerMedia\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\QuantumManager\Administrator\Helper\QuantummanagerHelper;
use SimpleXMLElement;

class QuantumManagerMedia extends CMSPlugin
{

	protected $app;

	protected $db;

	protected $autoloadLanguage = true;

	protected $install_quantummanager = false;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if (file_exists(JPATH_SITE . '/administrator/components/com_quantummanager/services/provider.php'))
		{
			$this->install_quantummanager = true;
		}

	}

	public function onAfterRoute()
	{
		if (!$this->install_quantummanager)
		{
			return;
		}

		if (!$this->accessCheck())
		{
			return;
		}

		if (
			(
				$this->app->input->get('option') === 'com_media' &&
				$this->app->input->get('view') === 'images'
			) ||
			$this->app->input->getString('qm', '0') === '1'
		)
		{
			$data           = $this->app->input->getArray();
			$data['option'] = 'com_ajax';
			$data['plugin'] = 'quantummanagermedia';
			$data['format'] = 'html';
			$data['tmpl']   = 'component';
			unset($data['qm']);

			$this->app->redirect('index.php?' . http_build_query($data));
		}


	}

	public function onBeforeRender()
	{
		if (!$this->install_quantummanager)
		{
			return;
		}

		if (!$this->accessCheck())
		{
			return;
		}

		HTMLHelper::_('stylesheet', 'com_quantummanager/modalhelper.css', [
			'version'  => filemtime(__FILE__),
			'relative' => true
		]);

	}

	/**
	 * Adds addition meta title
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	public function onContentPrepareForm($form, $data)
	{

		if (!$this->install_quantummanager)
		{
			return;
		}

		$component   = $this->app->input->get('option');
		$view        = $this->app->input->get('view');
		$enableMedia = (int) $this->params->get('enablemedia', 1);

		if ($this->accessCheck() && $enableMedia)
		{
			$enableMediaComponents = $this->params->get('enablemediaadministratorcomponents', ['com_content.article', 'com_content.form']);

			if (!is_array($enableMediaComponents))
			{
				$enableMediaComponents = ['com_content.article', 'com_content.form'];
			}

			if (!in_array($component . '.' . $view, $enableMediaComponents, true))
			{
				return;
			}

			$scopes = QuantummanagerHelper::getAllScope();
			foreach ($scopes as $scope)
			{
				if ($scope->id === 'images')
				{
					QuantummanagerHelper::preparePath($scope->path, false, $scope->id);
				}
			}

			$xml = $form->getXml();
			$this->replaceFieldMedia($xml);

		}

		if ($this->app->isClient('administrator'))
		{
			$this->addCssForButtonImage();
		}

	}

	public function onAjaxQuantummanagermedia()
	{
		if (!$this->install_quantummanager)
		{
			return;
		}

		if (!$this->accessCheck())
		{
			return;
		}

		QuantummanagerHelper::loadlang();
		$layout = new FileLayout('default', JPATH_ROOT . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
				'plugins', 'system', 'quantummanagermedia', 'tmpl'
			]));
		echo $layout->render();
	}

	/**
	 * @param                     $base
	 * @param   SimpleXMLElement  $node
	 */
	protected function replaceFieldMedia(SimpleXMLElement &$node)
	{
		$childNodes = $node->children();

		if ($node->getName() === 'field')
		{
			foreach ($node->attributes() as $a => $b)
			{
				if (((string) $a === 'type' && (string) $b === 'media'))
				{
					$enablemediapreview     = (int) $this->params->get('enablemediapreview', 1);
					$node['addfieldprefix'] = 'JPATHRU\\Libraries\\Fields\\Field\\QuantumUploadImageField';
					$node['type']           = 'QuantumUploadImageField';
					$node['directory']      = $this->params->get('enablemediapath', '');
					$node['dropAreaHidden'] = !$enablemediapreview;
				}

				if (((string) $a === 'type' && (string) $b === 'accessiblemedia'))
				{
					$enablemediapreview     = (int) $this->params->get('enablemediapreview', 1);
					$node['addfieldprefix'] = 'JPATHRU\\Libraries\\Fields\\Field\\QuantumAccessibleMedia';
					$node['type']           = 'QuantumAccessibleMedia';
					$node['directory']      = $this->params->get('enablemediapath', '');
					$node['dropAreaHidden'] = !$enablemediapreview;
				}
			}
		}

		if (count($childNodes) > 0)
		{
			foreach ($childNodes as $chNode)
			{
				$this->replaceFieldMedia($chNode);
			}
		}
	}

	protected function addCssForButtonImage()
	{
		if (
			($this->app->getDocument() === null) ||
			$this->app->getDocument()->getType() !== 'html'
		)
		{
			return;
		}

		Factory::getLanguage()->load('plg_editors-xtd_image', JPATH_ADMINISTRATOR);
		$label = Text::_('PLG_IMAGE_BUTTON_IMAGE');
		Factory::getDocument()->addStyleDeclaration(<<<EOT
@media screen and (min-width: 1540px) {
	.mce-window[aria-label="{$label}"] {
		top: 10% !important;
		left: calc((100% - 1400px)/2) !important;
		width: 1400px !important;
		height: 80% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-reset
	{
		width: 100% !important;
		height: 100% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-window-body {
		width: 100% !important;
		height: calc(100% - 96px) !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-foot {
		width: 100% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-foot .mce-container-body {
		width: 100% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-foot .mce-container-body .mce-widget {
		left: auto !important;
		right: 18px !important;
	}
}

@media screen and (max-width: 1540px) {
	.mce-window[aria-label="{$label}"] {
		left: 2% !important;
		right: 0 !important;
		width: 95% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-reset
	{
		width: 100% !important;
		height: 100% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-window-body {
		width: 100% !important;
		height: calc(100% - 96px) !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-foot {
		width: 100% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-foot .mce-container-body {
		width: 100% !important;
	}
	
	.mce-window[aria-label="{$label}"] .mce-foot .mce-container-body .mce-widget {
		left: auto !important;
		right: 18px !important;
	}
}

@media screen and (max-height: 700px) {

	.mce-window[aria-label="{$label}"] {
		top: 2% !important;
		height: 95% !important;
	}
		
	.mce-window[aria-label="{$label}"] .mce-window-body {
		height: calc(100% - 96px) !important;
	}
			
}


EOT
		);

		Factory::getDocument()->addScriptDeclaration("window.QuantumwindowPluginMediaLang = { label: '" . $label . "'};");
		HTMLHelper::_('script', 'plg_system_quantummanagermedia/largeforimagebutton.js', [
			'version'  => filemtime(__FILE__),
			'relative' => true
		]);
	}


	protected function accessCheck()
	{

		if ($this->app->isClient('administrator'))
		{
			return true;
		}

		// проверяем на включенность параметра

		if (!(int) QuantummanagerHelper::getParamsComponentValue('front', 0))
		{
			return false;
		}

		// проверяем что пользователь авторизован
		if (Factory::getUser()->id === 0)
		{
			return false;
		}

		return true;
	}

}
