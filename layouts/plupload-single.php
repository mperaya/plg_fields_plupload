<?php
/**
 * @package PLUpload for Joomla
 * @copyright  (C) 2021 Manuel P. Ayala. All rights reserved
 * @license    GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;

extract($displayData);

// Load de plugin stylesheet
HtmlHelper::stylesheet('media/plg_fields_plupload/css/plupload.css');

// Load the modal behavior script.
HtmlHelper::_('behavior.modal');

// Include jQuery
HTMLHelper::_('jquery.framework');

HtmlHelper::_('script', 'media/mediafield-mootools.min.js', array('version' => 'auto', 'relative' => true, 'framework' => true));

HtmlHelper::script('media/plg_fields_plupload/js/moxie.min.js');
HtmlHelper::script('media/plg_fields_plupload/js/plupload.min.js');

HtmlHelper::script('media/plg_fields_plupload/js/i18n/' . $lang . '.js');

$params = new stdClass();
$params->scope = $scope;
$params->upload_path = $upload_path;
$params->upload_field = $upload_field;

$form = Form::getInstance($id . '_form',
'<form>
	<field name="' . $id . '_meter"
		type="meter"
		label="PLG_FIELDS_PLUPLOAD_METER_LABEL"
		description="PLG_FIELDS_PLUPLOAD_METER_DESC"
		default="0"
		min="0"
		max="' . $width . '"
	/>
</form>'
);
// The text field.
?>
<div class="input-prepend input-append">
	<input type="text"
		name="<?php echo $name; ?>"
		id="<?php echo $id; ?>"
		value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
		readonly="readonly"
		class="novalidate"
		/>
<?php if($access) : ?>
	<button id="<?php echo $id; ?>_pickfiles" 
		class="btn modal novalidate" 
		data-toggle="modal" 
		data-target="#<?php echo $id; ?>_modal-update" 
		title="<?php echo Text::_('JLIB_FORM_BUTTON_SELECT'); ?>"
		>
			<?php echo Text::_('JLIB_FORM_BUTTON_SELECT'); ?>
	</button>
	<div class="btn-group novalidate" style="width: 0; margin: 0">
		<div id="<?php echo $id; ?>_modal-update" 
			tabindex="-1" 
			class="modal hide fade plupload" 
			aria-hidden="true" 
			style="display: none; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px;"
			>
			<div class="modal-header plupload-header">
				<button 
					type="button" 
					class="close novalidate" 
					data-dismiss="modal" 
					aria-label="<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>"
					>
					<span aria-hidden="true">×</span>
				</button>
				<h3><?php echo Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_UPLOAD_FILES'); ?></h3>
			</div>
			<div class="modal-body plupload-body overflow-hidden">
				<div class="row-fluid">
					<div id="<?php echo $id; ?>_meter" class="plupload-meter">
						<?php echo $form->renderField($id . '_meter'); ?>
					</div>
					<div id="<?php echo $id; ?>_container" class="hide plupload-container">
						<?php echo Text::_('PLG_FIELDS_PLUPLOAD_BROWSER_NOT_SUPPORTED'); ?>
					</div>
					<div id="<?php echo $id; ?>_filelist" class="hide plupload-filelist">
						<?php echo Text::_('JCANCEL'); ?>
					</div>
				</div>
				<br style="clear: both" />
			</div>
			<div class="modal-footer plupload-footer">
				<button id="<?php echo $id; ?>_cancel" 
					type="button" 
					class="btn btn-danger novalidate plupload-cancel hide" 
					data-dismiss="modal" 
					aria-label="<?php echo Text::_('JCANCEL'); ?>"
					>
					<span aria-hidden="true"><?php echo Text::_('JCANCEL'); ?></span>
				</button>
				<button id="<?php echo $id; ?>_close" 
					type="button" 
					class="btn btn-success novalidate plupload-close hide" 
					data-dismiss="modal" 
					aria-label="<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>"
					>
					<span aria-hidden="true"><?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?></span>
				</button>
			</div>
		</div>
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
				]
			},
			init: {
				PostInit: function() {
					<?php echo $id; ?>_initialize();
				},
				FilesAdded: function(up, files) {
					document.body.onfocus = null;
					$('#<?php echo $id; ?>_cancel').removeClass('hide');

					plupload.each(files, function(file) {
						document.getElementById(
							'<?php echo $id; ?>_filelist').innerHTML
								+= '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
					});
					up.start();
				},
				UploadProgress: function(up, file) {
					var barwidth = <?php echo $width; ?>;
					document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + '%</span>';
					$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress').attr('data-value', (file.percent * barwidth / 100));
					$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress div.bar').css('width', (file.percent * barwidth / 100));
				},
				FileUploaded: function(up, file, result) {
					$('#<?php echo $id; ?>_cancel').addClass('hide');
					$('#<?php echo $id; ?>_close').removeClass('hide');
					jInsertFieldValue(file.name, '<?php echo $id; ?>'); return false;
					$('#<?php echo $id; ?>_modal-update').modal('hide');
				},
				Error: function(up, err) {
					$('#<?php echo $id; ?>_cancel').addClass('hide');
					$('#<?php echo $id; ?>_close').removeClass('hide');
					$('#<?php echo $id; ?>_modal-update').modal('hide');
					alert("\nError #" + err.code + ": " + err.message);
					<?php echo $id; ?>_initialize();
				}
			}
		});

		<?php echo $id; ?>_uploader.init();

		function <?php echo $id; ?>_filecancel() {
			filefield = $('#<?php echo $id; ?>_container div.moxie-shim input[id^=html5_]');

			if (filefield.length <= 1) {
				document.body.onfocus = null;
				$('#<?php echo $id; ?>_modal-update').modal('hide');
			} else {
				document.body.onfocus = null;
			}
			document.body.onfocus = null;
		}
		
		$('#<?php echo $id; ?>_cancel').click(
			function () {
				<?php echo $id; ?>_uploader.stop();
			}
		);
	
		function <?php echo $id; ?>_initialize() {
			$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress').attr('data-value', 0);
			$('#<?php echo $id; ?>_meter div.control-group div.controls div.progress div.bar').css('width', 0);
			$('#<?php echo $id; ?>_cancel').addClass('hide');
			$('#<?php echo $id; ?>_close').addClass('hide');

			$('#<?php echo $id; ?>_uploader').files = null;
			$('#<?php echo $id; ?>_filelist').innerHTML = '';
			$('#<?php echo $id; ?>_uploader').start;
			document.body.onfocus = <?php echo $id; ?>_filecancel;
		};
		
		$('#<?php echo $id; ?>_modal-update').on('show.bs.modal', function(e) {
			$('#<?php echo $id; ?>_uploader').refresh;
			<?php echo $id; ?>_initialize();
		});
	});
		</script>
	</div>
	<a class="btn hasTooltip"
		title="<?php echo Text::_('JLIB_FORM_BUTTON_CLEAR'); ?>"
		href="#"
		onclick="jInsertFieldValue('', '<?php echo $id; ?>'); return false;"
		>
		<span class="icon-remove" aria-hidden="true"></span>
	</a>
<?php endif; ?>
</div>
