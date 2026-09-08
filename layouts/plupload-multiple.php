<?php
/**
 * @package   PLUpload for Joomla
 * @copyright (C) 2022 Manuel P. Ayala. All rights reserved
 * @license   GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

extract($displayData);

/**
 * Render options
 * -----------------
 * @var   sring   $upload_path		Base dir for any upload/download operation.
 * @var   integer $multiple_uploads	If defined, shows a multiple file upload dialog.
 * @var   boolena $prevent_duplicates	If defined, prevents upload same file twice.
 * @var   string  $upload_field		Input an existing field name in your form which stores existing
 *					file paths with read/write permissions for the web server user.
 *					If the field not exists or is blank, default path will be used.
 * @var   array   $mime_types		Allowed Mime types and file extensions list
 * @var   array   $mime_extensions	Allowed file extensions
 * @var   integer $max_file_size	Max. file size allowed to upload in MegaBytes.
 *					Set to zero means no limit.
 * @var   integer $width		Width in viewport units (vh) of the upload file
 *					selection window.
 * @var   integer $height		Height in viewport units (vh) of the upload file
 *					selection window.
 * @var   array   $upload_groups		User groups allowed to upload files.
 * @var   array   $download_groups	User groups allowed to download files.
 * @var   integer $listview		A bitwise binary mask for show only certain field parts in list
 *					views.
 * @var   string  $viewtype		Type of view. Must be equal to 'list' if the field is added to
 *					a custom list type form definition.
 */

if (!empty($itemid)) {
	$id = $id . '_' . $itemid;
} else {
	$id = $id . '_0';
}

$token = Session::getFormToken();

$params = new stdClass();
$params->action = 'upload';
$params->scope = $scope;
$params->upload_path = $upload_path;
$params->storage_mode = $storage_mode;
$params->media_subpath = $media_subpath;
$params->token = $token;
$uploadUrl = 'index.php?option=com_ajax&plugin=plupload&group=fields&format=json&params='
	. base64_encode(urlencode(json_encode($params)));

$modaloptions = array(
		'title'       => Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_UPLOAD_FILES'),
		'backdrop'    => 'static',
		'keyboard'    => false,
		'closeButton' => true,
		);
if (!empty($width)) $modaloptions['modalWidth'] = $width;
if (!empty($height)) $modaloptions['bodyHeight'] = $height;

$body =
'<div id="' . $id . '_uploader" class="pluploader_uploader">' .  "\n" .
'	' . Text::_('PLG_FIELDS_PLUPLOAD_BROWSER_NOT_SUPPORTED') .  "\n" .
'</div>' .  "\n" .
'<div class="plupload-overwrite-row d-flex gap-2 mt-2">' .
'<div id="' . $id . '_upload_error" class="alert alert-danger py-2 mb-0 flex-grow-1 d-none" role="alert" aria-live="assertive"></div>' .
'<button id="' . $id . '_overwrite" type="button" class="btn btn-danger plupload-overwrite d-none flex-shrink-0" onclick="window[\'' . $id . '_overwriteFile\'](event); return false;">' . Text::_('PLG_FIELDS_PLUPLOAD_FILE_EXIST_OVERWRITE') . '</button>' .
'<button id="' . $id . '_discard_duplicate" type="button" class="btn btn-success plupload-discard-duplicate d-none flex-shrink-0" onclick="window[\'' . $id . '_discardDuplicate\'](event); return false;"><span class="fas fa-ban icon-white" aria-hidden="true"></span> ' . Text::_('PLG_FIELDS_PLUPLOAD_FILE_EXIST_DISCARD') . '</button>' .
'</div>' . "\n" .
'<button id="' . $id . '_create_directory" type="button" class="btn btn-warning d-none mt-2">'
. Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_CREATE') . '</button>' . "\n";

$modalHTML  = HTMLHelper::_(
	'bootstrap.renderModal',
	$id . '_modal-update',
	$modaloptions,
	$body
);
// The text field.
?>
<div class="plupload">
	<input type="hidden"
		name="<?php echo $name; ?>"
		id="<?php echo $id; ?>"
		value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
		readonly="readonly"
		class="novalidate"
	/>
<?php if($upload_access || $download_access) : ?>
<?php echo $modalHTML; ?>
	<script type="text/javascript">
	window.PluploadFieldMultiple.init({
		id: <?php echo json_encode($id); ?>,
		url: <?php echo json_encode($uploadUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		maxFileSize: <?php echo json_encode($max_file_size); ?>,
		preventDuplicates: <?php echo $prevent_duplicates ? 'true' : 'false'; ?>,
		mimeTypes: [<?php echo $mime_types; ?>],
		mimeExtensions: <?php echo json_encode($mime_extensions ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		clearLabel: <?php echo json_encode(Text::_('JLIB_FORM_BUTTON_CLEAR'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		fallbackError: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_UNKNOWN_ERR'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		filterError: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_FILE_FILTER_ERR'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		pathNotFound: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_NOT_FOUND'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		existsContinue: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_FILE_EXIST_CONTINUE'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
	});
	</script>
	<button
		type="button"
		id="<?php echo $id; ?>_pickfiles"
		class="btn btn-primary btn-select data-state-<?php echo $this->escape($value ?? ''); ?> novalidate"
		data-bs-target="#<?php echo $id; ?>_modal-update"
		data-bs-toggle="modal"
		title="<?php echo Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_MULTIPLE_UPLOADS_LABEL'); ?>"
	>
		<?php echo Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_MULTIPLE_UPLOADS_LABEL'); ?>
	</button>
<?php endif; ?>
</div>
