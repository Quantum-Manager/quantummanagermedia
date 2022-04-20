<?php
/**
 * @package    quantummanagermedia
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Version;

defined('_JEXEC') or die;

/**
 * Quantummanagermedia script file.
 *
 * @package     A package name
 * @since       1.0
 */
class plgSystemQuantummanagermediaInstallerScript
{

	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, JAdapterInstance $adapter) {

		if (!(new Version())->isCompatible('4.0'))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery( true );
			$query->update( '#__extensions' )->set( 'enabled=1' )->where( 'type=' . $db->q( 'plugin' ) )->where( 'element=' . $db->q( 'quantummanagermedia' ) );
			$db->setQuery( $query )->execute();
		}

	}

}
