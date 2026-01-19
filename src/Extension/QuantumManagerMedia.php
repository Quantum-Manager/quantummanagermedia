<?php

namespace Joomla\Plugin\System\QuantumManagerMedia\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\QuantumManager\Administrator\Helper\QuantummanagerHelper;
use Joomla\Event\SubscriberInterface;
use SimpleXMLElement;

class QuantumManagerMedia extends CMSPlugin implements SubscriberInterface
{

	protected $app;

	protected $db;

	protected $autoloadLanguage = true;

	protected $install_quantummanager = false;

	public function __construct(&$subject, $config = [])
	{
		parent::__construct($subject, $config);

		if (file_exists(JPATH_SITE . '/administrator/components/com_quantummanager/services/provider.php'))
		{
			$this->install_quantummanager = true;
		}
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterRoute'              => 'onAfterRoute',
			'onBeforeRender'            => 'onBeforeRender',
			'onContentPrepareForm'      => 'onContentPrepareForm',
			'onAjaxQuantummanagermedia' => 'onAjax',
		];
	}

	public function onAfterRoute(): void
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
				(
					$this->app->input->get('option') === 'com_media'
					&& $this->app->input->get('view') === 'images'
				)
				|| (
					$this->app->input->get('option') === 'com_media'
					&& $this->app->input->get('asset') === 'com_acym'
				)
			)
			|| $this->app->input->getString('qm', '0') === '1'
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

	public function onBeforeRender(): void
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

		if ($this->app->input->get('option') === 'com_acym')
		{
			HTMLHelper::_('script', 'plg_system_quantummanagermedia/modalasym.js', [
				'version'  => filemtime(__FILE__),
				'relative' => true
			]);
		}
	}

	public function onContentPrepareForm(PrepareFormEvent $event): void
	{

		if (!$this->install_quantummanager)
		{
			return;
		}

		$form        = $event->getForm();
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
					QuantummanagerHelper::preparePath('root', false, $scope->id);
				}
			}

			FormHelper::addFieldPrefix('JPATHRU\\Libraries\\Fields\\QuantumAccessibleMedia');

			$xml = $form->getXml();
			$this->replaceFieldMedia($xml);
		}

		if ($this->app->isClient('administrator'))
		{
			$this->addCssForButtonImage();
		}
	}

	public function onAjax(): void
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

	private function replaceFieldMedia(SimpleXMLElement &$node): void
	{
		$childNodes = $node->children();

		if ($node->getName() === 'field')
		{
			foreach ($node->attributes() as $a => $b)
			{
				if (((string) $a === 'type' && (string) $b === 'media'))
				{
					$enablemediapreview     = (int) $this->params->get('enablemediapreview', 1);
					$node['addfieldprefix'] = 'JPATHRU\\Libraries\\Fields\\QuantumUploadImage';
					$node['type']           = 'Quantumuploadimage';
					$node['directory']      = $this->params->get('enablemediapath', '');
					$node['dropAreaHidden'] = !$enablemediapreview;
				}

				if (((string) $a === 'type' && (string) $b === 'accessiblemedia'))
				{
					$enablemediapreview     = (int) $this->params->get('enablemediapreview', 1);
					$node['addfieldprefix'] = 'JPATHRU\\Libraries\\Fields\\QuantumAccessibleMedia';
					$node['type']           = 'Quantumaccessiblemedia';
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

	private function addCssForButtonImage(): void
	{
		if (
			$this->app->getDocument() === null
			|| $this->app->getDocument()->getType() !== 'html'
		)
		{
			return;
		}

		Factory::getApplication()->getLanguage()->load('plg_editors-xtd_image', JPATH_ADMINISTRATOR);
		$label = Text::_('PLG_IMAGE_BUTTON_IMAGE');

		/** @var WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->addInlineStyle(<<<EOT
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

		$wa->addInlineScript("window.QuantumwindowPluginMediaLang = { label: '" . $label . "'};");
		HTMLHelper::_('script', 'plg_system_quantummanagermedia/largeforimagebutton.js', [
			'version'  => filemtime(__FILE__),
			'relative' => true
		]);
	}

	private function accessCheck(): bool
	{

		if ($this->app->isClient('administrator'))
		{
			return true;
		}

		if (!(int) QuantummanagerHelper::getParamsComponentValue('front', 0))
		{
			return false;
		}

		if (Factory::getApplication()->getIdentity()->id === 0)
		{
			return false;
		}

		return true;
	}

}
