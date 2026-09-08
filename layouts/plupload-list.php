<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

extract($displayData);

if (empty($value) || empty($download_access)) {
    return;
}

$params = (object) [
    'action' => 'download',
    'scope' => $scope,
    'upload_path' => $upload_path,
    'file_path' => $file_path ?? '',
    'file_name' => $value,
    'token' => Session::getFormToken(),
];

$query = base64_encode(urlencode(json_encode($params, JSON_THROW_ON_ERROR)));
$url = 'index.php?option=com_ajax&plugin=plupload&group=fields&format=raw&params=' . $query;
?>
<a class="tbody-icon hasTooltip" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
    title="<?php echo htmlspecialchars(Text::_('PLG_FIELDS_PLUPLOAD_DOWNLOAD'), ENT_QUOTES, 'UTF-8'); ?>">
    <span class="icon-download" aria-hidden="true"></span>
    <span class="visually-hidden"><?php echo Text::_('PLG_FIELDS_PLUPLOAD_DOWNLOAD'); ?></span>
</a>
