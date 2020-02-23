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
use Joomla\CMS\HTML\HTMLHelper;
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


	public function onAfterRoute()
	{
		$app = Factory::getApplication();
		if($app->isClient('administrator'))
		{
			if ($app->input->get('option') == 'com_media'
				&& $app->input->get('view') == 'images')
			{
				$data = $app->input->getArray();
				$data['option'] = 'com_ajax';
				$data['plugin'] = 'quantummanagermedia';
				$data['format'] = 'html';
				$data['tmpl'] = 'component';

				$app->redirect('index.php?' . http_build_query($data));
			}
		}

	}


	public function onBeforeRender()
	{

		$app = Factory::getApplication();
		if ($app->isClient('administrator'))
		{
			$data = $app->input->getArray();

			HTMLHelper::_('stylesheet', 'com_quantummanager/modalhelper.css', [
				'version' => filemtime(__FILE__),
				'relative' => true
			]);
		}

	}


	public function onAjaxQuantummanagerbutton()
	{
		$app = Factory::getApplication();
		if($app->isClient('administrator'))
		{
			JLoader::register('QuantummanagerHelper', JPATH_ROOT . '/administrator/components/com_quantummanager/helpers/quantummanager.php');
			QuantummanagerHelper::loadlang();
			$layout = new FileLayout('default', JPATH_ROOT . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
					'plugins', 'system', 'quantummanagermedia', 'tmpl'
				]));
			echo $layout->render();
		}
	}

}
