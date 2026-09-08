(function (window, document, $) {
	'use strict';

	window.PluploadFieldSingle = {
		init: function (config) {
			$(function () {
				var id = config.id;
				var error = $('#' + id + '_upload_error');
				var create = $('#' + id + '_create_directory');
				var overwrite = $('#' + id + '_overwrite');
				var cancel = $('#' + id + '_cancel');
				var cancelOriginalHtml = cancel.html();
				var pickerError = false;

				function restoreCancelButton() {
					cancel.removeClass('btn-success plupload-discard-duplicate').addClass('btn-danger plupload-cancel').html(cancelOriginalHtml);
				}

				function buildActionUrl(params) {
					return config.actionUrl + '&params=' + window.btoa(encodeURIComponent(JSON.stringify(params)));
				}

				function showError(message, canCreate) {
					error.text(message || config.fallbackError).removeClass('d-none').show();
					if (canCreate) {
						create.removeClass('d-none').show().css('display', 'block');
					} else {
						create.addClass('d-none').hide();
					}
				}

				function resetView() {
					pickerError = false;
					restoreCancelButton();
					$('#' + id + '_meter-desc small').first().text('');
					$('#' + id + '_meter .progress').attr('data-value', 0).find('.progress-bar').css('width', 0);
					error.addClass('d-none').text('').hide();
					create.addClass('d-none').hide();
					overwrite.addClass('d-none').hide().prop('disabled', false);
					$('#' + id + '_cancel, #' + id + '_close').addClass('hide');
				}

				function responseError(response) {
					return window.PluploadField.responseError(response, config.fallbackError);
				}

				function startUpload() {
					var queued = uploader.files.filter(function (file) {
						return file.status !== plupload.DONE;
					});
					if (!queued.length) {
						return;
					}
					queued.forEach(function (file) {
						if (file.status === plupload.FAILED) {
							file.status = plupload.QUEUED;
						}
					});
					uploader.stop();
					uploader.refresh();
					setTimeout(function () { uploader.start(); }, 0);
				}

				var uploader = new plupload.Uploader({
					runtimes: 'html5',
					browse_button: id + '_pickfiles',
					container: document.getElementById(id + '_container'),
					url: config.url,
					chunk_size: '1mb',
					rename: true,
					multi_selection: false,
					multiple_queues: false,
					max_file_count: 1,
					filters: {max_file_size: config.maxFileSize + 'mb', mime_types: config.mimeTypes},
					init: {
						PostInit: function () { initialize(); },
						FilesAdded: function (up, files) {
							resetView();
							cancel.removeClass('hide');
							$.ajax({url: up.settings.url + '&action=check_directory', method: 'POST', dataType: 'json', cache: false})
								.done(function (response) {
									var data = window.PluploadField.responseData(response);
									if (!data || !data.success) {
										var details = data && data.data ? data.data : data;
										var canCreate = details && (details.can_create === true || details.can_create === 1 || details.can_create === '1');
										showError(responseError(response), canCreate || responseError(response) === config.pathNotFound);
										return;
									}
									$.ajax({url: up.settings.url, method: 'POST', data: {action: 'check_file', name: files[0].name}, dataType: 'json', cache: false})
										.done(function (fileResponse) {
											var fileData = window.PluploadField.responseData(fileResponse);
											if (fileData && fileData.success) { startUpload(); return; }
											var details = fileData && fileData.data ? fileData.data : fileData;
										if (details && (details.can_overwrite === true || details.can_overwrite === 1 || details.can_overwrite === '1')) {
											showError(config.existsContinue, false);
											overwrite.removeClass('d-none').show();
											cancel.removeClass('btn-danger plupload-cancel').addClass('btn-success plupload-discard-duplicate').html('<span class="fas fa-ban icon-white" aria-hidden="true"></span> ' + config.discardLabel);
											return;
											}
											showError(responseError(fileResponse));
										})
										.fail(function (xhr) { showError(responseError(xhr.responseText)); });
								})
								.fail(function (xhr) { showError(responseError(xhr.responseText)); });
						},
						ChunkUploaded: function (up, file, result) {
							var response = JSON.parse(result.response);
							if (response.success === false) {
								file.status = plupload.FAILED; up.stop(); up.splice();
								showError(response.message || config.fallbackError);
								$('#' + id + '_modal-update').modal('show');
							}
						},
						UploadProgress: function (up, file) {
							$('#' + id + '_meter-desc small').first().text(file.percent + '%');
							$('#' + id + '_meter .progress-bar').attr('aria-valuenow', file.percent + '%').css('width', file.percent + '%');
						},
						FileUploaded: function (up, file, result) {
							var response = JSON.parse(result.response);
							if (response.success === true) {
								$('#' + id + '_cancel').addClass('hide'); $('#' + id + '_close').removeClass('hide');
								$('#' + id + '_modal-update').modal('hide');
								var name = response.data && response.data.info && response.data.info.name ? response.data.info.name : file.name;
								window[id + '_updateMediaField'](name, id);
								return false;
							}
							file.status = plupload.FAILED; up.stop(); up.splice(); showError(response.message || config.fallbackError);
							$('#' + id + '_modal-update').modal('show');
						},
						Error: function (up, err) {
							if (err.code !== 0) {
								pickerError = true; if (err.file) { err.file.status = plupload.FAILED; }
								up.stop(); up.splice();
								showError(err.code === plupload.FILE_EXTENSION_ERROR ? config.filterError : err.message);
								$('#' + id + '_modal-update').modal('show');
							}
						}
					}
				});

				uploader.init();
				window.PluploadField.bindPickerFilter('#' + id + '_pickfiles', config.mimeExtensions, function () {
					pickerError = true; showError(config.filterError);
				});
				window[id + '_createDirectory'] = function (event) {
					event.preventDefault(); create.prop('disabled', true); showError(config.pathCreating);
					$.ajax({url: config.createUrl, method: 'POST', dataType: 'json', cache: false})
						.done(function (response) { var data = window.PluploadField.responseData(response); if (data && data.success) { create.addClass('d-none').hide(); error.addClass('d-none').hide(); startUpload(); } else { showError(responseError(response)); } })
						.fail(function (xhr) { showError(responseError(xhr.responseText) || config.createRequestError.replace('%s', xhr.status)); })
						.always(function () { create.prop('disabled', false); });
				};
				window[id + '_overwriteFile'] = function (event) {
					event.preventDefault(); restoreCancelButton(); overwrite.prop('disabled', true).hide(); error.addClass('d-none').hide();
					if (!uploader.settings.url.includes('overwrite=1')) { uploader.settings.url += '&overwrite=1'; }
					startUpload();
				};
				function initialize() {
					uploader.stop(); if (uploader.files.length) { uploader.splice(); }
					resetView();
				}
				$('#' + id + '_modal-update').on('hidden.bs.modal', initialize);
				$('#' + id + '_pickfiles').on('click', function () {
					resetView(); var count = uploader.files.length;
					var closeEmpty = function () { setTimeout(function () { if (!pickerError && uploader.files.length === count) { $('#' + id + '_modal-update').modal('hide'); } }, 500); window.removeEventListener('focus', closeEmpty); };
					window.addEventListener('focus', closeEmpty);
				});
				$('#' + id + '_cancel').on('click', function () { uploader.stop(); });

				function clearField() {
					var field = $('#' + id);
					field.val('');
					$('#' + id + '_clear, #' + id + '_download').attr('disabled', 'disabled');
					$('#' + id + '_pickfiles').removeAttr('disabled');
					field.trigger('change');
					if (typeof field.get(0).onchange === 'function') { field.get(0).onchange(); }
				}
				window[id + '_updateMediaField'] = function (value, fieldId) {
					var field = $('#' + fieldId);
					if (field.val() === value) { return; }
					if (config.isSuperUser && value === '' && field.val() !== '') {
						$('#' + id + '_modal-delete').modal('show');
						return;
					}
					if (value === '') { clearField(); return; }
					field.val(value).trigger('change');
					if (typeof field.get(0).onchange === 'function') { field.get(0).onchange(); }
					$('#' + id + '_clear, #' + id + '_download').removeAttr('disabled');
				};
				window[id + '_keepFile'] = clearField;
				window[id + '_confirmDelete'] = function () {
					if (document.activeElement && typeof document.activeElement.blur === 'function') { document.activeElement.blur(); }
					$('#' + id + '_modal-delete').modal('hide');
					setTimeout(function () { $('#' + id + '_modal-delete-warning').modal('show'); }, 300);
				};
				window[id + '_deleteFile'] = function () {
					if (!config.isSuperUser) { return; }
					var field = $('#' + id);
					var fileName = field.val();
					field.val('');
					$('#' + id + '_clear, #' + id + '_download').attr('disabled', 'disabled');
					$('#' + id + '_pickfiles').removeAttr('disabled');
					var xhr = new XMLHttpRequest();
					var deleteParams = $.extend({}, config.deleteParams, {file_name: fileName});
					xhr.open('POST', buildActionUrl(deleteParams));
					xhr.send();
					field.trigger('change');
					if (typeof field.get(0).onchange === 'function') { field.get(0).onchange(); }
				};
				window[id + '_downloadMediaField'] = function () {
					var downloadParams = $.extend({}, config.downloadParams, {file_name: $('#' + id).val()});
					window.open(buildActionUrl(downloadParams), '_blank');
				};
			});
		}
	};
}(window, document, window.jQuery));
