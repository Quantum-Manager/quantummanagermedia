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


	/**
	 * Adds addition meta title
	 *
	 * @param  JForm $form The form to be altered.
	 * @param  mixed $data The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	function onContentPrepareForm($form, $data)
	{
		$app = Factory::getApplication();
		$component = $app->input->get('option');
		$view = $app->input->get('view');
		$enableMedia = (int)$this->params->get('enablemedia', 1);
		$enablemediapath = $this->params->get('enablemediapath', 'images');
		$enablemediapreview = (int)$this->params->get('enablemediapreview', 1);

		if ($app->isClient('administrator') && $enableMedia)
		{
			$enableMediaComponents = $this->params->get('enablemediaadministratorcomponents', ['com_content.article']);
			if(in_array($component . '.' . $view, $enableMediaComponents))
			{
				if($component !== 'com_content')
				{
					foreach ($form->getFieldsets() as $fieldset)
					{
						foreach ($form->getFieldset($fieldset->name) as $field)
						{
							$type = $field->__get('type');
							$name  = $field->__get('fieldname');
							$group = $field->__get('group');

							if (strtolower($type) === 'media')
							{
								$form->setFieldAttribute($name, 'type', 'quantumuploadimage', $group);
								$form->setFieldAttribute($name, 'addfieldpath', '/libraries/lib_fields/fields/quantumuploadimage', $group);
								$form->setFieldAttribute($name, 'directory', $enablemediapath, $group);
								$form->setFieldAttribute($name, 'dropAreaHidden', '1', $group);
								$form->setFieldAttribute($name, 'uploadAreaHidden', $enablemediapreview, $group);
							}

						}
					}
				}
				else
				{
					$xml = $form->getXml();
					$this->fixForComContent($xml);
					$form->load($xml->asXML());
				}

			}
		}

		return true;
	}


	public function onAjaxQuantummanagermedia()
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


	/**
	 * @param $base
	 * @param SimpleXMLElement $node
	 */
	protected function fixForComContent(SimpleXMLElement &$node)
	{
		$childNodes = $node->children();
		$enablemediapath = $this->params->get('enablemediapath', 'images');
		$enablemediapreview = (int)$this->params->get('enablemediapreview', 1);

		if($node->getName() === 'field')
		{
			foreach($node->attributes() as $a => $b)
			{
				if((string)$a === 'type' && (string)$b === 'media')
				{
					$node['addfieldpath'] = '/libraries/lib_fields/fields/quantumuploadimage';
					$node['type'] = 'quantumuploadimage';
					$node['directory'] = $enablemediapath;
					$node['dropAreaHidden'] = 1;
					$node['uploadAreaHidden'] = $enablemediapreview;
				}
			}
		}

		if (count($childNodes) > 0)
		{
			foreach ($childNodes as $chNode)
			{
				$this->fixForComContent($chNode);
			}
		}
	}


}
