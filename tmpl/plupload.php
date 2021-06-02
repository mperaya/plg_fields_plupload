<?php
/**
 * Plupload Plugin
 *
 * @copyright  (C) 2021 Manuel P. Ayala. All rights reserved
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
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
