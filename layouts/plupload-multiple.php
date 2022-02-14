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

extract($displayData);

$params = new stdClass();
$params->scope = $scope;
$params->upload_path = $upload_path;
$params->upload_field = $upload_field;

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
'</div>' .  "\n";

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
<?php if($access) : ?>
<?php echo $modalHTML; ?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$("#<?php echo $id; ?>_uploader").plupload({
			// General settings
			runtimes: 'html5',
			url: 'index.php?option=com_ajax&plugin=plupload&group=fields&format=json&params=<?php echo base64_encode(urlencode(json_encode($params))); ?>',
			chunk_size: '1mb',
			rename: true,
			dragdrop: true,
			sortable: true,
			unique_names: false,
			multi_selection: false,
			prevent_duplicates: <?php echo ($prevent_duplicates) ? 'true' : 'false'; ?>,
			multiple_queues: true,
			buttons: {
				browse: true,
				start: true,
				stop: true
			},
			filters: {
				max_file_size: '<?php echo $max_file_size; ?>mb',
				prevent_duplicates: <?php echo ($prevent_duplicates) ? 'true' : 'false'; ?>,
				mime_types: [
					<?php echo $mime_types; ?>
				],
			}
		});
		var $<?php echo $id; ?>_uploader = $('#<?php echo $id; ?>_uploader').plupload('getUploader');
		// Add Clear Button
		var $<?php echo $id; ?>_button = $("<button><?php echo Text::_('JLIB_FORM_BUTTON_CLEAR'); ?></button>").button({icons: {primary: "ui-icon-trash"}}).button("disable").appendTo('.plupload_buttons');

		// Clear Button Action
		$<?php echo $id; ?>_button.click(function() {
			$<?php echo $id; ?>_uploader.splice();
			$("#<?php echo $id; ?> .plupload_filelist_content").html('');
			$<?php echo $id; ?>_button.button("disable");
			$('#<?php echo $id; ?>_modal-update').modal('hide');
			return true;
		});
		// Clear Button Toggle Enabled
		$<?php echo $id; ?>_uploader.bind('QueueChanged', function () {
			if($<?php echo $id; ?>_uploader.files.length > 0) {
				$<?php echo $id; ?>_button.button("enable");
			} else {
				$<?php echo $id; ?>_button.button("disable");
			}
		});
		// Clear Button Toggle Hidden
		$<?php echo $id; ?>_uploader.bind('StateChanged', function () {
			if($<?php echo $id; ?>_uploader.state === plupload.STARTED) {
				$<?php echo $id; ?>_button.hide();
			} else {
				$<?php echo $id; ?>_button.show();
			}
		});
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
