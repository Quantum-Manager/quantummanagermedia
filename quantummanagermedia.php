<?php
/**
 * @package    quantummanagermedia
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseDriver;

/**
 * Quantummanagermedia plugin.
 *
 * @package  quantummanagermedia
 * @since    1.0
 */
class plgSystemQuantummanagermedia extends CMSPlugin
{

	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0
	 */
	protected $app;


	/**
	 * Database object
	 *
	 * @var    DatabaseDriver
	 * @since  1.0
	 */
	protected $db;


	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;


	protected $install_quantummanager = false;


	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		if (file_exists(JPATH_SITE . '/administrator/components/com_quantummanager/quantummanager.php'))
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

		$data = $this->app->input->getArray();

		HTMLHelper::_('stylesheet', 'com_quantummanager/modalhelper.css', [
			'version'  => filemtime(__FILE__),
			'relative' => true
		]);

	}


	/**
	 * Adds addition meta title
	 *
	 * @param   JForm  $form  The form to be altered.
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

		JLoader::register('QuantummanagerHelper', JPATH_SITE . '/administrator/components/com_quantummanager/helpers/quantummanager.php');

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

			Form::addFieldPath(JPATH_ROOT . '/libraries/lib_fields/fields/quantumuploadimage');

			if(QuantummanagerHelper::isJoomla4())
			{
				$form->postProcess($data);
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

		JLoader::register('QuantummanagerHelper', JPATH_ROOT . '/administrator/components/com_quantummanager/helpers/quantummanager.php');
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
					$enablemediapath    = $this->params->get('enablemediapath', '');
					$enablemediapreview = (int) $this->params->get('enablemediapreview', 1);
					$path               = '';

					if (!empty($enablemediapath))
					{
						$path = 'images/' . $enablemediapath;
					}

					$node['addfieldpath']   = '/libraries/lib_fields/fields/quantumuploadimage';
					$node['type']           = 'quantumuploadimage';
					$node['directory']      = $path;
					$node['dropAreaHidden'] = !$enablemediapreview;
				}

				if (((string) $a === 'type' && (string) $b === 'accessiblemedia'))
				{
					$enablemediapath    = $this->params->get('enablemediapath', '');
					$enablemediapreview = (int) $this->params->get('enablemediapreview', 1);
					$path               = '';

					if (!empty($enablemediapath))
					{
						$path = 'images/' . $enablemediapath;
					}

					$node['addfieldpath']   = '/libraries/lib_fields/fields/quantumaccessiblemedia';
					$node['type']           = 'quantumaccessiblemedia';
					$node['directory']      = $path;
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
		JLoader::register('QuantummanagerHelper', JPATH_ADMINISTRATOR . '/components/com_quantummanager/helpers/quantummanager.php');

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
