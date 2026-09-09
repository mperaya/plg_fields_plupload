<?php
/**
 * @package   PLUpload for Joomla
 * @copyright (C) 2022 Manuel P. Ayala. All rights reserved
 * @license   GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 */

namespace Mayala\Plugin\Fields\Plupload\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\MeterField;

/**
 * Provides a meter bar.
 */
class PluploadmeterField extends MeterField
{
	protected $type = 'Pluploadmeter';

	/**
	 * Width.
	 * @var    integer
	 */
	protected $width;

	/**
	 * Color.
	 * @var    string
	 */
	protected $color;


//
//	/**
//	 * Name of the layout being used to render the field
//	 *
//	 * @var    string
//	 * @since  3.7
//	 */
//	protected $layout = 'meter';
//
//	protected function getRenderer($layoutId = 'default')
//	{
//		$this->loadLanguage();
//		
//		$renderer = parent::getRenderer($layoutId);
//		
////		if ($layoutId == $this->layout) {
//			$renderer->addIncludePath(JPATH_PLUGINS . '/fields/plupload/layouts');
////		}
//
//		return $renderer;
//	}
}
