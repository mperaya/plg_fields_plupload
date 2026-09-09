(function (window, document, $) {
	'use strict';

	window.PluploadFieldMultiple = {
		init: function (config) {
			$(function () {
				var id = config.id;
				var error = $('#' + id + '_upload_error');
				var create = $('#' + id + '_create_directory');
				var overwrite = $('#' + id + '_overwrite');
				var discardDuplicate = $('#' + id + '_discard_duplicate');
				var duplicateFile = null;
				var uploadsAllowed = false;
				var responseError = function (response) { return window.PluploadField.responseError(response, config.fallbackError); };
				var showError = function (message, canCreate) {
					error.text(message || config.fallbackError).removeClass('d-none').show();
					if (canCreate) { create.removeClass('d-none').show().css('display', 'block'); } else { create.addClass('d-none').hide(); }
				};
				var reset = function () {
					uploadsAllowed = false; duplicateFile = null; overwrite.prop('disabled', false).add(discardDuplicate).addClass('d-none').hide();
					error.addClass('d-none').text('').hide(); create.addClass('d-none').hide().prop('disabled', false);
					if (uploader) {
						uploader.settings.url = uploader.settings.url.replace(/([?&])overwrite=1(?:&|$)/, '$1').replace(/[?&]$/, '');
						uploader.setOption('filters', {prevent_duplicates: config.preventDuplicates});
					}
				};
				var uploaderElement = $('#' + id + '_uploader');
				uploaderElement.plupload({
					runtimes: 'html5', url: config.url, chunk_size: '1mb', rename: true, dragdrop: true,
					sortable: true, unique_names: false, multi_selection: false, prevent_duplicates: config.preventDuplicates,
					multiple_queues: true, buttons: {browse: true, start: true, stop: true},
					filters: {max_file_size: config.maxFileSize + 'mb', prevent_duplicates: config.preventDuplicates, mime_types: config.mimeTypes}
				});
				var uploader = uploaderElement.plupload('getUploader');
				window.PluploadField.bindPickerFilter('#' + id + '_uploader .plupload_add', config.mimeExtensions, function () {
					showError(config.filterError);
				});
				var start = $('#' + id + '_start');
				uploader.bind('BeforeUpload', function (up) { if (!uploadsAllowed) { up.stop(); return false; } });
				uploader.bind('Error', function (up, errorData) {
					if (errorData.file) { errorData.file.status = plupload.FAILED; }
					up.stop(); up.splice(); var message = errorData.code === plupload.FILE_EXTENSION_ERROR ? config.filterError : (responseError(errorData.response) || errorData.message);
					showError(message);
				});
				uploader.bind('error', function (up, errorData) { if (errorData.code !== plupload.FILE_EXTENSION_ERROR) { return; } });
				uploader.bind('FilesAdded', function (up, files) {
					uploadsAllowed = false;
					duplicateFile = null;
					overwrite.add(discardDuplicate).prop('disabled', false).addClass('d-none').hide();
					start.button('disable'); error.addClass('d-none').text('').hide(); create.addClass('d-none').hide();
					$.ajax({url: up.settings.url + '&action=check_directory', method: 'POST', dataType: 'json', cache: false}).done(function (response) {
						var data = window.PluploadField.responseData(response);
						if (!data || !data.success) { var details = data && data.data ? data.data : data; var canCreate = details && (details.can_create === true || details.can_create === 1 || details.can_create === '1'); showError(responseError(response), canCreate || responseError(response) === config.pathNotFound); return; }
						var checkFile = function (index) {
							if (index >= files.length) { uploadsAllowed = true; start.button('enable'); return; }
							$.ajax({url: up.settings.url, method: 'POST', data: {action: 'check_file', name: files[index].name}, dataType: 'json', cache: false}).done(function (fileResponse) {
								var fileData = window.PluploadField.responseData(fileResponse); if (fileData && fileData.success) { checkFile(index + 1); return; }
								var details = fileData && fileData.data ? fileData.data : fileData;
								if (details && (details.can_overwrite === true || details.can_overwrite === 1 || details.can_overwrite === '1')) { duplicateFile = files[index]; showError(config.existsContinue); overwrite.removeClass('d-none').show(); discardDuplicate.removeClass('d-none').show(); return; }
								showError(responseError(fileResponse));
							}).fail(function (xhr) { showError(responseError(xhr.responseText)); });
						};
						checkFile(0);
					}).fail(function (xhr) { showError(responseError(xhr.responseText)); });
				});
				uploader.bind('FileUploaded', function (up, file, response) {
					var message = responseError(response.response);
					if (!message && config.preventDuplicates) {
						up.setOption('filters', {prevent_duplicates: false});
					}
					if (message) { file.status = plupload.FAILED; up.stop(); up.splice(); showError(message); }
				});
				window[id + '_overwriteFile'] = function (event) { event.preventDefault(); uploadsAllowed = true; overwrite.add(discardDuplicate).prop('disabled', true).hide(); error.addClass('d-none').hide(); if (!uploader.settings.url.includes('overwrite=1')) { uploader.settings.url += '&overwrite=1'; } uploader.start(); };
				window[id + '_discardDuplicate'] = function (event) { event.preventDefault(); if (duplicateFile) { uploader.removeFile(duplicateFile); } duplicateFile = null; uploadsAllowed = true; overwrite.add(discardDuplicate).prop('disabled', false).hide(); error.addClass('d-none').text('').hide(); start.button('enable'); };
				create.on('click', function () { create.prop('disabled', true); $.ajax({url: uploader.settings.url + '&action=create_directory', method: 'POST', dataType: 'json', cache: false}).done(function (response) { var data = window.PluploadField.responseData(response); if (data && data.success) { uploadsAllowed = true; error.addClass('d-none').hide(); create.addClass('d-none').hide(); start.button('enable'); } else { showError(responseError(response)); } }).fail(function (xhr) { showError(responseError(xhr.responseText)); }).always(function () { create.prop('disabled', false); }); });
				var clear = $('<button>' + config.clearLabel + '</button>').button({icons: {primary: 'ui-icon-trash'}}).button('disable').appendTo('#' + id + ' .plupload_buttons');
				clear.on('click', function () { reset(); uploader.splice(); $('#' + id + ' .plupload_filelist_content').html(''); clear.button('disable'); $('#' + id + '_modal-update').modal('hide'); return true; });
				uploader.bind('QueueChanged', function () { uploader.files.length ? clear.button('enable') : clear.button('disable'); });
				uploader.bind('StateChanged', function () { uploader.state === plupload.STARTED ? clear.hide() : clear.show(); });
				$('#' + id + '_modal-update').on('show.bs.modal', function () { if (!uploader.files.length) { reset(); } });
				$('#' + id + '_modal-update').on('hidden.bs.modal', function () { reset(); uploader.stop(); uploader.splice(); $('#' + id + ' .plupload_filelist_content').html(''); clear.button('disable'); });
			});
		}
	};
}(window, document, window.jQuery));
