<?php
/**
 * @package PLUpload for Joomla
 * @copyright  (C) 2021 Manuel P. Ayala. All rights reserved
 * @license    GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 */

defined('_JEXEC') or die;

$value = $field->value;

if ($value == '')
{
	return;
}

if (is_array($value))
{
	$value = implode(', ', $value);
}

echo htmlentities($value);
