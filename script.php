<?php
/**
 * @package Plupload Plugin for Joomla! 3.9
 * @copyright  (C) 2021 Manuel P. Ayala. All rights reserved
 * @license    GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 */

defined('_JEXEC') or die;

/**
 * Installation class to perform additional changes during install/uninstall/update
 */
class PlgFieldsPluploadInstallerScript extends JInstallerScript
{
	/**
	 * Extension script constructor.
	 */
	public function __construct()
	{
		// Define the minumum versions to be supported.
		$this->minimumJoomla = '3.8';
		$this->minimumPhp    = '7.0';

		$this->deleteFiles = array(
			// Delete files
		);
	}
}
