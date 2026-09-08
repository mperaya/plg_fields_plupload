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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;

use Mayala\Plugin\Fields\Plupload\Field\PluploadmeterField;

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
 *
 * For use this field in listviews, with the capability of download the file uploaded if you have defined a
 * $upload_field for store the individual paths to every record file, you need to call a custom render for the field
 * to allow distinguish between every list row data.
 * For It yo need to get the form field object and call the render method with the layout and an array of options to
 * pass to it to make things work.
 * @var   integer $listview		A bitwise binary mask for show only certain field parts in list
 *					views.
 * @var   string  $viewtype		Type of view. Must be equal to 'list' if the field is added to
 *					a custom list type form definition.
 * @var   string  $filepath		For use this field in listviews you must populate this var with the
 *					$upload_field field value of every database record shown.
 * @var   string  $itemid		The id of the database record of object shown.
 *
 * Example:	echo $field->render($field->layout, ['listview' => (!$disabled ? 4 : 0), 'viewtype' => 'list', 'itemid' => $item->id, 'filepath' => $item->file_path]);
 */

$isBackend = Factory::getApplication()->getName() == 'administrator';
$isSuperUser = (bool) (Factory::getApplication()->getIdentity()?->get('isRoot') ?? false);

$buttons = '';
$buttonClass = "btn btn-primary btn-select";

if (!empty($viewtype) && ($viewtype == 'list')) {
	$buttonClass = "tbody-icon";
	$class = trim($class . ' list'??'');
	switch ($listview) {
		case 1:
		case 2:
		case 4:
			$buttons = ' buttons1';
			break;
		case 3:
		case 5:
		case 6:
			$buttons = ' buttons2';
			break;
	}
} else {
	$viewtype = '';
	$listview = 7;
}
//if (!empty($itemid)) {
if (!empty($itemid) || ($itemid = Factory::getApplication()->getInput ()->get('id'))) {
	$id = $id . '_' . $itemid;
} else {
	$id = $id . '_0';
}

//die();
//echo print_r("b@".$readonly."#",true);

$token = Session::getFormToken();

$params = new stdClass();
$params->action = 'upload';
$params->scope = $scope;
$params->upload_path = $upload_path;
$params->storage_mode = $storage_mode;
$params->media_subpath = $media_subpath;
$params->token = $token;
$createParams = clone $params;
$createParams->action = 'create_directory';
$createUrl = 'index.php?option=com_ajax&plugin=plupload&group=fields&format=json&params='
	. base64_encode(urlencode(json_encode($createParams)));
$uploadUrl = 'index.php?option=com_ajax&plugin=plupload&group=fields&format=json&params='
	. base64_encode(urlencode(json_encode($params)));
if (!$isBackend) {
	$createUrl = Route::_($createUrl);
	$uploadUrl = Route::_($uploadUrl);
}

$deleteparams = new stdClass();
$deleteparams->action = 'delete';
$deleteparams->scope = $scope;
$deleteparams->upload_path = $upload_path;
$deleteparams->storage_mode = $storage_mode;
$deleteparams->media_subpath = $media_subpath;
if (isset($filepath)) {
	$deleteparams->upload_path .= '/' . $filepath;
}
$deleteparams->file_name = $value;
$deleteparams->token = $token;

$downloadparams = new stdClass();
$downloadparams->action = 'download';
$downloadparams->scope = $scope;
$downloadparams->upload_path = $upload_path;
$downloadparams->storage_mode = $storage_mode;
$downloadparams->media_subpath = $media_subpath;
if (isset($filepath)) {
	$downloadparams->upload_path .= '/' . $filepath;
}
$downloadparams->file_name = $value;
$downloadparams->token = $token;
$actionUrl = 'index.php?option=com_ajax&plugin=plupload&group=fields&format=json';
if (!$isBackend) {
	$actionUrl = Route::_($actionUrl);
}

$form = Form::getInstance(
	$id . '_form',
	'<form id="' . $id . '_form">' .
	'	<field name="' . $id . '_meter"' .
	'		type="pluploadmeter"' .
	'		addfieldprefix="Mayala\Plugin\Fields\Plupload\Field"' .
	'		label="PLG_FIELDS_PLUPLOAD_METER_LABEL"' .
	'		description="PLG_FIELDS_PLUPLOAD_METER_DESC"' .
	'		default="0"' .
	'		width="0"' .
	'		max="100"' .
	'		min="0"' .
	'		step="1"' .
	'		active="true"' .
	'	/>' .
	'</form>'
);

$attributes = array(
	$disabled ? 'disabled' : '',
	$readonly ? 'readonly' : '',
//	'onchange="' . $id . '_changeMediaField()"',
	$onchange ? ' onchange="' . $onchange . '"' : ' onchange="Joomla.submitbutton(\'' . Factory::getApplication()->input->get('view') . '.apply\');"',
	$required ? 'required aria-required="true"' : '',
);

$footer =
'<button id="' . $id . '_cancel" ' . "\n" .
'	type="button" ' . "\n" .
'	class="btn btn-danger novalidate plupload-cancel hide" ' . "\n" .
'	data-bs-dismiss="modal" ' . "\n" .
'	aria-label="' . Text::_('JCANCEL') . '" ' . "\n" .
'	>' . "\n" .
'	' . Text::_('JCANCEL') . "\n" .
'</button>' . "\n" .
'<button id="' . $id . '_close" ' . "\n" .
'	type="button" ' . "\n" .
'	class="btn btn-success novalidate plupload-close hide" ' . "\n" .
'	data-bs-dismiss="modal" ' . "\n" .
'	aria-label="' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . '"' . "\n" .
'	>' . "\n" .
'	' . Text::_('JLIB_HTML_BEHAVIOR_CLOSE') . "\n" .
'</button>' . "\n";

$footer =
'<button id="' . $id . '_overwrite" type="button" class="btn btn-danger novalidate plupload-overwrite d-none" onclick="window[\'' . $id . '_overwriteFile\'](event); return false;">' .
Text::_('PLG_FIELDS_PLUPLOAD_FILE_EXIST_OVERWRITE') . '</button>' . "\n" . $footer;

$modaloptions = array(
		'title'       => Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_UPLOAD_FILES'),
		'backdrop'    => 'static',
		'keyboard'    => false,
		'closeButton' => false,
		'footer'      => $footer,
		);

if (!empty($width)) $modaloptions['modalWidth'] = $width;
if (!empty($height)) $modaloptions['bodyHeight'] = $height;

$body =
'<div id="' . $id . '_meter" class="plupload-meter">' .  "\n" .
'	' . $form->renderField($id . '_meter') .  "\n" .
'</div>' .  "\n" .
'<div id="' . $id . '_container" class="hide plupload-container">' .  "\n" .
'	' . Text::_('PLG_FIELDS_PLUPLOAD_BROWSER_NOT_SUPPORTED') .  "\n" .
'</div>' .  "\n" .
'<div id="' . $id . '_upload_error" class="alert alert-danger d-none mt-2" role="alert" aria-live="assertive"></div>' . "\n" .
'<button id="' . $id . '_create_directory" type="button" class="btn btn-warning d-none mt-2" onclick="window[\'' . $id . '_createDirectory\'](event); return false;">'
. Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_CREATE') . '</button>' . "\n";

//die();

$modalHTML  = HTMLHelper::_(
	'bootstrap.renderModal',
	$id . '_modal-update',
	$modaloptions,
	$body
	);


$deleteModalHTML = '';
$deleteWarningHTML = '';

if ($isSuperUser) {
$deleteModalHTML = HTMLHelper::_(
	'bootstrap.renderModal',
	$id . '_modal-delete',
	[
		'title'       => Text::_('PLG_FIELDS_PLUPLOAD_DELETE_CONFIRM_TITLE'),
		'backdrop'    => 'static',
		'keyboard'    => false,
		'closeButton' => false,
		'footer'      =>
			'<button type="button" class="btn btn-primary" data-bs-dismiss="modal" autofocus><span class="icon-cancel" aria-hidden="true"></span> ' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_CANCEL') . '</button>' .
			'<button type="button" class="btn btn-danger plupload-delete-remove" data-bs-toggle="modal" data-bs-target="#' . $id . '_modal-delete-warning" onclick="window[\'' . $id . '_confirmDelete\'](); return false;"><span class="icon-trash icon-white" aria-hidden="true"></span> ' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_REMOVE') . '</button>' .
			'<button type="button" class="btn btn-success plupload-delete-keep" data-bs-dismiss="modal" onclick="window[\'' . $id . '_keepFile\'](); return false;"><span class="fas fa-ban icon-white" aria-hidden="true"></span> ' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_KEEP') . '</button>',
	],
	'<p>' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_CONFIRM_TEXT') . '</p>'
);

$deleteWarningHTML = HTMLHelper::_(
	'bootstrap.renderModal',
	$id . '_modal-delete-warning',
	[
		'title'       => Text::_('PLG_FIELDS_PLUPLOAD_DELETE_WARNING_TITLE'),
		'backdrop'    => 'static',
		'keyboard'    => false,
		'closeButton' => false,
		'footer'      =>
			'<button type="button" class="btn btn-primary" data-bs-dismiss="modal" autofocus><span class="icon-cancel" aria-hidden="true"></span> ' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_CANCEL') . '</button>' .
			'<button type="button" class="btn btn-danger plupload-delete-remove" data-bs-dismiss="modal" onclick="window[\'' . $id . '_deleteFile\'](); return false;"><span class="icon-trash icon-white" aria-hidden="true"></span> ' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_ACCEPT') . '</button>',
	],
	'<p>' . Text::_('PLG_FIELDS_PLUPLOAD_DELETE_WARNING_TEXT') . '</p>'
);
}
// The text field.
//$access = true;
?>
<div class="input-group plupload<?php echo $buttons; ?>">
<?php if ($viewtype == 'list' && ($listview & 1)) : ?>
	<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>
<?php else : ?>
	<input type="text"
		name="<?php echo $name; ?>"
		id="<?php echo $id; ?>"
		value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
		readonly="readonly"
		class="form-control field-input-name novalidate <?php echo $class; ?> <?php echo ($listview & 1) ? '': 'hidden'?>"
		<?php echo trim(implode(" ", $attributes)??'') . "\n"; ?>
	/>
<?php endif; ?>
<?php if($upload_access || $download_access) : ?>
<?php echo $modalHTML; ?>
	<?php echo $deleteModalHTML; ?>
	<?php echo $deleteWarningHTML; ?>
	<script type="text/javascript">
	window.PluploadFieldSingle.init({
		id: <?php echo json_encode($id); ?>,
		url: <?php echo json_encode($uploadUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		createUrl: <?php echo json_encode($createUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		actionUrl: <?php echo json_encode($actionUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		deleteParams: <?php echo json_encode($deleteparams, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		downloadParams: <?php echo json_encode($downloadparams, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		maxFileSize: <?php echo json_encode($max_file_size); ?>,
		mimeTypes: [<?php echo $mime_types; ?>],
		mimeExtensions: <?php echo json_encode($mime_extensions ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		isSuperUser: <?php echo $isSuperUser ? 'true' : 'false'; ?>,
		fallbackError: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_UNKNOWN_ERR'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		filterError: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_FILE_FILTER_ESC_ERR'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		pathNotFound: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_NOT_FOUND'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		pathCreating: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_CREATING'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		createRequestError: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD_PATH_CREATE_REQUEST_ERR'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		existsContinue: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_FILE_EXIST_CONTINUE'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
		discardLabel: <?php echo json_encode(Text::_('PLG_FIELDS_PLUPLOAD_FILE_EXIST_DISCARD'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
	});
	</script>
<?php // if ($listview & 2) : ?>
	<button type="button"
		id="<?php echo $id; ?>_clear"
		class="<?php echo $buttonClass; ?> hasTooltip <?php echo ($listview & 2) ? '': 'hidden'?>"
		title="<?php echo Text::_('JLIB_FORM_BUTTON_CLEAR'); ?>"
		onclick="<?php echo $id; ?>_updateMediaField('','<?php echo $id; ?>'); return false;"
		<?php if ($isSuperUser) : ?>
		data-bs-toggle="modal"
		data-bs-target="#<?php echo $id; ?>_modal-delete"
		<?php endif; ?>
		<?php echo empty($value) ? ' disabled' : ''; ?>
		>
		<span class="icon-remove icon-white" aria-hidden="true"></span>
	</button>
<?php // endif; ?>
<?php // if ($listview & 4) : ?>
	<button type="button"
		id="<?php echo $id; ?>_download"
		class="<?php echo $buttonClass; ?> hasTooltip <?php echo ($listview & 4) ? '': 'hidden'?>"
		title="<?php echo Text::_('PLG_FIELDS_PLUPLOAD_DOWNLOAD'); ?>"
		onclick="<?php echo $id; ?>_downloadMediaField(); return false;"
		<?php echo empty($value) ? ' disabled' : ''; ?>
		>
		<span class="icon-download icon-white" aria-hidden="true"></span>
	</button>
<?php // endif; ?>
<?php // if ($listview & 2) : ?>
	<button
		type="button"
		id="<?php echo $id; ?>_pickfiles"
		class="<?php echo $buttonClass; ?> data-state-<?php echo $this->escape($value ?? ''); ?> novalidate <?php echo ($listview & 2) ? '': 'hidden'?>"
		data-bs-target="#<?php echo $id; ?>_modal-update"
		data-bs-toggle="modal"
		title="<?php echo Text::_('PLG_FIELDS_PLUPLOAD_UPLOAD'); ?>"
		<?php echo empty($value) ? '' : ' disabled'; ?>
	>
		<span class="icon-upload icon-white" aria-hidden="true"></span>
	</button>
<?php // endif; ?>
<!--	<button
		type="button"
		id="<?php // echo $id; ?>_pickfiles"
		class="<?php echo $buttonClass; ?> data-state-<?php // echo $this->escape($value ?? ''); ?> novalidate"
		data-bs-target="#<?php // echo $id; ?>_modal-update"
		data-bs-toggle="modal"
		title="<?php // echo Text::_('JLIB_FORM_BUTTON_SELECT'); ?>"
	>
		<span class="icon-edit icon-white" aria-hidden="true"></span>
	</button>-->
<?php endif; ?>
</div>
