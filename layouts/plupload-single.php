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

extract($displayData);

$params = new stdClass();
$params->scope = $scope;
$params->upload_path = $upload_path;
$params->upload_field = $upload_field;

$form = Form::getInstance($id . '_form',
'<form>' .
'	<field name="' . $id . '_meter"' .
'		type="meter"' .
'		label="PLG_FIELDS_PLUPLOAD_METER_LABEL"' .
'		description="PLG_FIELDS_PLUPLOAD_METER_DESC"' .
'		default="0"' .
'		max="100"' .
'		width="0"' .
'	/>' .
'</form>'
);

$attributes = array(
	$disabled ? 'disabled' : '',
	$readonly ? 'readonly' : '',
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
'</div>' .  "\n";

$modalHTML  = HTMLHelper::_(
	'bootstrap.renderModal',
	$id . '_modal-update',
	$modaloptions,
	$body
);
// The text field.
?>

<div class="input-group plupload">
	<input type="text"
		name="<?php echo $name; ?>"
		id="<?php echo $id; ?>"
		value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
		readonly="readonly"
		class="form-control field-input-name novalidate <?php echo $class; ?>" 
		<?php echo trim(implode(" ", $attributes)) . "\n"; ?>
	/>
<?php if($access) : ?>
<?php echo $modalHTML; ?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		var <?php echo $id; ?>_uploader = new plupload.Uploader({
			// General settings
			runtimes: 'html5',
			browse_button : '<?php echo $id; ?>_pickfiles', // you can pass an id...
			container: document.getElementById('<?php echo $id; ?>_container'), // ... or DOM Element itself
			url: 'index.php?option=com_ajax&plugin=plupload&group=fields&format=json&params=<?php echo base64_encode(urlencode(json_encode($params))); ?>',
			chunk_size: '1mb',
			rename: true,
			multi_selection: false,
			multiple_queues: false,
			max_file_count: 1,
			filters: {
				max_file_size: '<?php echo $max_file_size; ?>mb',
				mime_types: [
					<?php echo $mime_types; ?>
				],
			},
			init: {
				PostInit: function() {
					<?php echo $id; ?>_initialize();
				},
				FilesAdded: function(up, files) {
					document.body.onfocus = null;
					$('#<?php echo $id; ?>_cancel').removeClass('hide');
					up.start();
				},
				ChunkUploaded: async function(up, file, result) {
					response = await JSON.parse(result.response);
					if(response.success === false) {
						<?php echo $id; ?>_uploader.stop();
						location.reload();
					}
				},
				UploadProgress: function(up, file) {
					var barwidth = 100;
					document.getElementById('<?php echo $id; ?>_meter-desc').getElementsByTagName('small')[0].innerHTML = file.percent + '%';
					$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress div.progress-bar').attr('aria-valuenow', (file.percent * barwidth / 100) + '%');
					$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress div.progress-bar').css('width', (file.percent * barwidth / 100) + '%');
				},
				FileUploaded: async function(up, file, result) {
					response = await JSON.parse(result.response);
					if(response.success === true) {
						$('#<?php echo $id; ?>_cancel').addClass('hide');
						$('#<?php echo $id; ?>_close').removeClass('hide');
						$('#<?php echo $id; ?>_modal-update').modal('hide');
						<?php echo $id; ?>_updateMediaField(file.name, '<?php echo $id; ?>');
						return false;
					}
				},
				Error: function(up, err) {
					if (err.code !== 0) {
						$('#<?php echo $id; ?>_cancel').addClass('hide');
						$('#<?php echo $id; ?>_close').removeClass('hide');
						$('#<?php echo $id; ?>_modal-update').modal('hide');
						alert("\nError #" + err.code + ": " + err.message);
						location.reload();
					}
				}
			}
		});

		<?php echo $id; ?>_uploader.init();

		async function <?php echo $id; ?>_filecancel() {
			document.body.onfocus = null;
			filefield = await $('#<?php echo $id; ?>_container div.moxie-shim input[id^=html5_]');
			if (filefield.length <= 1) {
				$('#<?php echo $id; ?>_modal-update').modal('hide');
			}
		}

		$('#<?php echo $id; ?>_container div.moxie-shim input[id^=html5_]').on('change', function(event){
			files = event.target.files;
			alert("hay");
		});

		function <?php echo $id; ?>_initialize() {
			document.body.onfocus = <?php echo $id; ?>_filecancel;

			document.getElementById('<?php echo $id; ?>_meter-desc').getElementsByTagName('small')[0].innerHTML = '';
			$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress').attr('data-value', 0);
			$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress div.bar').css('width', 0);
			$('#<?php echo $id; ?>_cancel').addClass('hide');
			$('#<?php echo $id; ?>_close').addClass('hide');

			$('#<?php echo $id; ?>_uploader').files = null;
//			$('#<?php echo $id; ?>_filelist').innerHTML = '';
			$('#<?php echo $id; ?>_uploader').start;

		};

		$('#<?php echo $id; ?>_cancel').click(
			function () {
				<?php echo $id; ?>_uploader.stop();
			}
		);
	});

	function <?php echo $id; ?>_updateMediaField(value, id) {
		var $ = jQuery.noConflict();
		var old_value = $("#" + id).val();
		if (old_value != value) {
			var $elem = $("#" + id);
			$elem.val(value);
			if (value == '') {
				$('#' + id + '_clear').attr('disabled', 'disabled');
			} else {
				$('#' + id + '_clear').removeAttr('disabled');
			}
			$elem.trigger("change");
			if (typeof($elem.get(0).onchange) === "function") {
				$elem.get(0).onchange();
			}
		}
	};
	</script>
	<button type="button"
		id="<?php echo $id; ?>_clear"
		class="btn btn-primary btn-select hasTooltip"
		title="<?php echo Text::_('JLIB_FORM_BUTTON_CLEAR'); ?>"
		onclick="<?php echo $id; ?>_updateMediaField('','<?php echo $id; ?>'); return false;"
		<?php echo empty($value) ? ' disabled' : ''; ?>
		>
		<span class="icon-remove icon-white" aria-hidden="true"></span>
	</button>
	<button
		type="button"
		id="<?php echo $id; ?>_pickfiles"
		class="btn btn-primary btn-select data-state-<?php echo $this->escape($value ?? ''); ?> novalidate"
		data-bs-target="#<?php echo $id; ?>_modal-update"
		data-bs-toggle="modal" 
		title="<?php echo Text::_('JLIB_FORM_BUTTON_SELECT'); ?>" 
	>
		<span class="icon-edit icon-white" aria-hidden="true"></span>
	</button>
<?php endif; ?>
</div>
