<?php
/**
 * @package PLUpload for Joomla
 * @copyright  (C) 2021 Manuel P. Ayala. All rights reserved
 * @license    GNU Affero General Public License Version 3; http://www.gnu.org/licenses/agpl-3.0.txt 
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

extract($displayData);

HtmlHelper::stylesheet('media/plg_fields_plupload/css/jquery-ui.min.css');
HtmlHelper::stylesheet('media/plg_fields_plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css');

// Load the modal behavior script.
HtmlHelper::_('behavior.modal');

// Include jQuery
HTMLHelper::_('jquery.framework');

HtmlHelper::_('script', 'media/mediafield-mootools.min.js', array('version' => 'auto', 'relative' => true, 'framework' => true));

HtmlHelper::script('media/plg_fields_plupload/js/jquery-ui.min.js');
HtmlHelper::script('media/plg_fields_plupload/js/plupload.full.min.js');
HtmlHelper::script('media/plg_fields_plupload/js/jquery.ui.plupload/jquery.ui.plupload.min.js');

HtmlHelper::script('media/plg_fields_plupload/js/i18n/' . $lang . '.js');

$params = new stdClass();
$params->scope = $scope;
$params->upload_path = $upload_path;
$params->upload_field = $upload_field;

// The text field.
?>
<div class="input-prepend input-append">
	<input type="hidden"
		name="<?php echo $name; ?>"
		id="<?php echo $id; ?>"
		value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
		readonly="readonly"
		class="novalidate"
		/>
<?php if($access) : ?>
	<button id="<?php echo $id; ?>_pickfiles"
		class="btn modal" 
		data-toggle="modal" 
		data-target="#<?php echo $id; ?>_modal-update" 
		title="<?php echo Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_MULTIPLE_UPLOADS_LABEL'); ?>"
		>
			<?php echo Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_MULTIPLE_UPLOADS_LABEL'); ?>
	</button>
	<div class="btn-group" style="width: 0; margin: 0">
		<div id="<?php echo $id; ?>_modal-update"
		     tabindex="-1"
		     class="modal hide fade"
		     aria-hidden="true" 
		     style="display: none; width: <?php echo $width; ?>px; height: <?php echo $height; ?>px;"
		     >
			<div class="modal-header">
				<button type="button" 
					class="close novalidate" 
					data-dismiss="modal" 
					aria-label="<?php echo Text::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>"
					>
					<span aria-hidden="true">×</span>
				</button>
				<h3><?php echo Text::_('PLG_FIELDS_PLUPLOAD_PARAMS_UPLOAD_FILES'); ?></h3>
			</div>
			<div class="jform_params_makeUpdate_modal-body">
				<div style="float: left; margin-right: 20px">
					<div id="<?php echo $id; ?>_uploader" 
					     style="width: <?php echo $width; ?>px; height: <?php echo $height; ?>px;"
					     >
						<?php echo Text::_('PLG_FIELDS_PLUPLOAD_BROWSER_NOT_SUPPORTED'); ?>
					</div>
				</div>
				<br style="clear: both" />

				<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Setup html5 version
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
							]
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
			</div>
		</div>
	</div>
<?php endif; ?>
</div>
