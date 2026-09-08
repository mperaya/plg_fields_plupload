(function (window, document, $) {
	'use strict';

	window.PluploadField = {
		responseData: function (response) {
			if (!response) {
				return null;
			}

			try {
				var data = typeof response === 'string' ? JSON.parse(response) : response;
				if (data && typeof data.data === 'string') {
					try {
						data.data = JSON.parse(data.data);
					} catch (e) {}
				}
				return data;
			} catch (e) {
				return null;
			}
		},

		responseError: function (response, fallback) {
			var data = this.responseData(response);
			return data && data.success === false
				? (data.message || (data.data && data.data.message) || fallback)
				: '';
		},

		isInvalidExtension: function (files, extensions) {
			return Array.prototype.some.call(files || [], function (file) {
				var extension = (file.name.split('.').pop() || '').toLowerCase();
				return extensions.length > 0 && extensions.indexOf(extension) === -1;
			});
		},

		bindPickerFilter: function (selector, extensions, onInvalid) {
			var pickerActive = false;
			document.addEventListener('click', function (event) {
				var target = event.target.closest(selector);
				if (target) {
					pickerActive = true;
				}
			}, true);
			document.addEventListener('change', function (event) {
				if (!pickerActive || !event.target.matches('input[type="file"]')) {
					return;
				}

				pickerActive = false;
				if (this.isInvalidExtension(event.target.files, extensions)) {
					event.stopImmediatePropagation();
					event.target.value = '';
					onInvalid();
				}
			}.bind(this), true);
		}
	};
}(window, document, window.jQuery));
