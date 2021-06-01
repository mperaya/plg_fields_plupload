<?php
/**
 * Plupload Plugin
 *
 * @copyright  (C) 2020 Manuel P. Ayala. All rights reserved
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
