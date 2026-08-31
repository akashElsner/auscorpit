(function ($) {
	'use strict';

	var sessionId = '';
	var allErrors = [];

	function showNotice(type, message) {
		var $box = $('#zoho-inv-notice-area');
		var icon = type === 'error' ? 'dashicons-warning' : 'dashicons-yes-alt';
		var cls  = type === 'error' ? 'zoho-inv-notice-error' : 'zoho-inv-notice-success';

		$box.html(
			'<div class="zoho-inv-notice ' + cls + '">' +
				'<span class="dashicons ' + icon + '"></span>' +
				'<p>' + $('<div/>').text(message).html() + '</p>' +
			'</div>'
		).show();

		$('html, body').animate({ scrollTop: $box.offset().top - 50 }, 300);
	}

	function hideNotice() {
		$('#zoho-inv-notice-area').hide().empty();
	}

	function showProgress() {
		hideNotice();
		$('#zoho-inv-import-progress').show();
		$('#zoho-inv-import-summary').hide();
		$('#zoho-inv-live-log-wrap').show();
		$('#zoho-inv-live-log-body').empty();
		allErrors = [];
	}

	function renderLogEntries(entries) {
		if (!entries || !entries.length) {
			return;
		}

		var $body = $('#zoho-inv-live-log-body');
		entries.forEach(function (entry) {
			var rowClass = 'zoho-inv-live-log-row zoho-inv-live-log-' + entry.status;
			var html =
				'<tr class="' + rowClass + '">' +
					'<td>' + $('<div/>').text(entry.time).html() + '</td>' +
					'<td><span class="zoho-inv-log-badge zoho-inv-log-badge-' + entry.status + '">' +
						entry.status.toUpperCase() +
					'</span></td>' +
					'<td><code>' + $('<div/>').text(entry.message).html() + '</code></td>' +
				'</tr>';

			$body.append(html);
		});

		var $wrap = $('.zoho-inv-live-log-scroll');
		$wrap.scrollTop($wrap[0].scrollHeight);
	}

	function updateProgress(data) {
		var percent = data.progress || 0;
		$('#zoho-inv-progress-bar').css('width', percent + '%');
		$('#zoho-inv-progress-text').text(percent + '%');
		$('#zoho-inv-progress-status').text(
			zohoInvImport.messages.processing +
				' (' +
				(data.processed || 0) +
				' / ' +
				(data.total_rows || 0) +
				')'
		);

		if (data.log_entries && data.log_entries.length) {
			$('#zoho-inv-live-log-body').empty();
			renderLogEntries(data.log_entries);
		}

		if (data.errors && data.errors.length) {
			allErrors = allErrors.concat(data.errors);
		}
	}

	function renderErrorList(errors) {
		if (!errors || !errors.length) {
			$('#zoho-inv-import-errors').hide();
			return;
		}

		var $list = $('#zoho-inv-error-list').empty();
		errors.forEach(function (error) {
			$list.append($('<li/>').text(error));
		});
		$('#zoho-inv-import-errors').show();
	}

	function showSummary(data) {
		$('#zoho-inv-import-summary').show();

		var failed = data.failed || 0;
		$('#summary-processed').text(data.processed || 0);
		$('#summary-created').text(data.created || 0);
		$('#summary-updated').text(data.updated || 0);
		$('#summary-failed').text(failed);

		if (failed > 0) {
			$('#summary-failed').addClass('zoho-inv-failed-count');
			$('#zoho-inv-progress-bar').css('background', '#d63638');
		} else {
			$('#summary-failed').removeClass('zoho-inv-failed-count');
		}

		var errors = data.all_errors && data.all_errors.length ? data.all_errors : allErrors;
		renderErrorList(errors);

		if (data.log_url) {
			$('#zoho-inv-log-download').attr('href', data.log_url);
			$('#zoho-inv-log-download-wrap').show();
		}

		if (data.log_view_url) {
			$('#zoho-inv-log-view').attr('href', data.log_view_url).show();
		}

		if (failed > 0) {
			showNotice('error', zohoInvImport.messages.completeWarn);
			$('#zoho-inv-progress-status').text(zohoInvImport.messages.completeWarn);
		} else {
			showNotice('success', zohoInvImport.messages.complete);
			$('#zoho-inv-progress-status').text(zohoInvImport.messages.complete);
		}

		$('#zoho-inv-import-start').prop('disabled', false);
	}

	function getErrorMessage(xhr, fallback) {
		if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
			return xhr.responseJSON.data.message;
		}
		if (xhr.responseText) {
			try {
				var parsed = JSON.parse(xhr.responseText);
				if (parsed.data && parsed.data.message) {
					return parsed.data.message;
				}
			} catch (e) {
				// Ignore parse errors.
			}
		}
		if (xhr.status === 403) {
			return 'Permission denied (403).';
		}
		if (xhr.status === 0) {
			return zohoInvImport.messages.networkError;
		}
		return fallback;
	}

	function processBatch() {
		$.ajax({
			url: zohoInvImport.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'zoho_inv_import_process',
				nonce: zohoInvImport.nonce,
				session_id: sessionId
			}
		})
			.done(function (response) {
				if (!response || !response.success) {
					var msg = response && response.data && response.data.message
						? response.data.message
						: zohoInvImport.messages.error;
					showNotice('error', msg);
					$('#zoho-inv-import-start').prop('disabled', false);
					return;
				}

				updateProgress(response.data);

				if (response.data.complete) {
					showSummary(response.data);
					return;
				}

				processBatch();
			})
			.fail(function (xhr) {
				showNotice('error', getErrorMessage(xhr, zohoInvImport.messages.error));
				$('#zoho-inv-import-start').prop('disabled', false);
			});
	}

	$('#zoho-inv-import-form').on('submit', function (event) {
		event.preventDefault();

		if (typeof zohoInvImport === 'undefined') {
			showNotice('error', 'Import script failed to load. Please refresh the page.');
			return;
		}

		var fileInput = $('#csv_file')[0];
		if (!fileInput.files.length) {
			showNotice('error', 'Please select a CSV file.');
			return;
		}

		var formData = new FormData();
		formData.append('action', 'zoho_inv_import_upload');
		formData.append('nonce', zohoInvImport.nonce);
		formData.append('csv_file', fileInput.files[0]);

		$('#zoho-inv-import-start').prop('disabled', true);
		showProgress();
		$('#zoho-inv-progress-status').text(zohoInvImport.messages.uploading);

		$.ajax({
			url: zohoInvImport.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: formData,
			processData: false,
			contentType: false
		})
			.done(function (response) {
				if (!response || !response.success) {
					var msg = response && response.data && response.data.message
						? response.data.message
						: zohoInvImport.messages.error;
					showNotice('error', msg);
					$('#zoho-inv-import-start').prop('disabled', false);
					$('#zoho-inv-import-progress').hide();
					return;
				}

				sessionId = response.data.session_id;
				showNotice('success', response.data.message || 'CSV uploaded.');
				processBatch();
			})
			.fail(function (xhr) {
				showNotice('error', getErrorMessage(xhr, zohoInvImport.messages.networkError));
				$('#zoho-inv-import-start').prop('disabled', false);
				$('#zoho-inv-import-progress').hide();
			});
	});
})(jQuery);
