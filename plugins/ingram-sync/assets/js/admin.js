/**
 * Ingram Sync admin JavaScript.
 */
(function ($) {
	'use strict';

	var CHUNKED_ACTIONS = {
		download_catalog: true,
		download_price: true,
		download_details: true,
		sync_woocommerce: true,
		warm_zoho_maps: true,
		sync_zoho: true
	};

	function formatNumber(n) {
		n = parseInt(n, 10) || 0;
		try {
			return n.toLocaleString();
		} catch (e) {
			return String(n);
		}
	}

	function buildProgressHtml(response, message) {
		var total = typeof response.total === 'number' ? response.total : 0;
		var downloaded = typeof response.downloaded === 'number' ? response.downloaded : 0;
		var remaining = typeof response.remaining === 'number' ? response.remaining : -1;
		var percent = 0;
		var html = '';

		if (total > 0) {
			percent = Math.min(100, Math.round((downloaded / total) * 100));
		} else if (response.done) {
			percent = 100;
		}

		html += '<div class="ingram-progress">';
		html += '<div class="ingram-progress-message">' + $('<div>').text(message || '').html() + '</div>';

		if (total > 0 || downloaded > 0) {
			html += '<div class="ingram-progress-stats">';
			html += '<span><strong>' + formatNumber(downloaded) + '</strong> ' + (ingramSync.i18n.downloaded || 'downloaded') + '</span>';
			if (total > 0) {
				html += '<span><strong>' + formatNumber(total) + '</strong> ' + (ingramSync.i18n.total || 'total') + '</span>';
			}
			if (remaining >= 0) {
				html += '<span><strong>' + formatNumber(remaining) + '</strong> ' + (ingramSync.i18n.remaining || 'remaining') + '</span>';
			}
			if (total > 0) {
				html += '<span><strong>' + percent + '%</strong></span>';
			}
			html += '</div>';

			if (total > 0) {
				html += '<div class="ingram-progress-bar" role="progressbar" aria-valuenow="' + percent + '" aria-valuemin="0" aria-valuemax="100">';
				html += '<span class="ingram-progress-bar-fill" style="width:' + percent + '%"></span>';
				html += '</div>';
			}
		}

		html += '</div>';
		return html;
	}

	function showResult($el, type, message, response) {
		$el.removeClass('success error loading visible')
			.addClass(type + ' visible');

		if (response && (typeof response.total === 'number' || typeof response.downloaded === 'number')) {
			$el.html(buildProgressHtml(response, message));
		} else {
			$el.text(message);
		}
	}

	function nextChunkData(action, response, prevData) {
		var data = {};

		if (action === 'download_catalog') {
			data = {
				page: response.next_page || ((response.page || 1) + 1),
				downloaded: response.downloaded || 0
			};
		} else if (action === 'download_price') {
			data = { offset: response.next_offset || 0 };
		} else if (action === 'warm_zoho_maps') {
			data = {
				page: response.next_page || ((response.page || 1) + 1)
			};
		} else if (action === 'sync_zoho') {
			var prev = (prevData && prevData.exclude_ids) ? prevData.exclude_ids : [];
			if (typeof prev === 'string') {
				try {
					prev = JSON.parse(prev);
				} catch (e) {
					prev = [];
				}
			}
			var failed = response.failed_ids || [];
			var merged = prev.concat(failed);
			data = { exclude_ids: JSON.stringify(merged) };
		} else if (action === 'sync_woocommerce') {
			var wprev = (prevData && prevData.exclude_ids) ? prevData.exclude_ids : [];
			if (typeof wprev === 'string') {
				try {
					wprev = JSON.parse(wprev);
				} catch (e2) {
					wprev = [];
				}
			}
			var wfailed = response.failed_ids || [];
			data = { exclude_ids: JSON.stringify(wprev.concat(wfailed)) };
		}

		// Thread the shared sync lock token through every chunked action uniformly —
		// the server acquires it on the first chunk (no lock_token in the POST) and
		// heartbeats it on every subsequent one, so two tabs/triggers can't drive
		// the same or a conflicting stage at once.
		if (response.lock_token) {
			data.lock_token = response.lock_token;
		}

		return data;
	}

	// Remembers the in-flight request payload per action so a manual retry after
	// a timeout/network failure resumes from where it left off instead of
	// silently restarting at page 1 — the UI already claims "progress is saved
	// between batches" on timeout, this is what actually delivers that.
	var lastAttempt = {};

	function runAjax(action, $btn, extraData, onDone) {
		var $result = $('#ingram-tools-result');
		var $inline = $('[data-for="' + action + '"]');
		var data;

		if (extraData && Object.keys(extraData).length > 0) {
			lastAttempt[action] = extraData;
		}

		$btn.prop('disabled', true);
		if (!extraData || Object.keys(extraData).length === 0) {
			showResult($result, 'loading', ingramSync.i18n.processing);
		}

		data = $.extend(
			{
				action: 'ingram_sync_' + action,
				nonce: ingramSync.nonce
			},
			extraData || {}
		);

		$.ajax({
			url: ingramSync.ajaxUrl,
			method: 'POST',
			data: data,
			timeout: 180000
		})
			.done(function (response) {
				if (!response) {
					showResult($result, 'error', ingramSync.i18n.error);
					$btn.prop('disabled', false);
					return;
				}

				var success = response.success === true;
				var message = response.message || (response.data && response.data.message) || ingramSync.i18n.success;

				// Continue chunked jobs until the server reports done.
				if (success && CHUNKED_ACTIONS[action] && response.done === false) {
					showResult($result, 'loading', message, response);
					runAjax(action, $btn, nextChunkData(action, response, extraData), onDone);
					return;
				}

				if (success) {
					delete lastAttempt[action];
				}

				// A stage completed successfully and the caller wants to chain into
				// another action (e.g. warm_zoho_maps -> sync_zoho) — skip the
				// terminal success UI/re-enable, the next stage's own runAjax call
				// will pick both up.
				if (success && typeof onDone === 'function') {
					onDone(response);
					return;
				}

				showResult($result, success ? 'success' : 'error', message, response);
				$btn.prop('disabled', false);
			})
			.fail(function (xhr) {
				var message = ingramSync.i18n.error;
				if (xhr.statusText === 'timeout') {
					message = ingramSync.i18n.timeout || 'Request timed out. Try again — progress is saved between batches.';
				} else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
					message = xhr.responseJSON.data.message;
				} else if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				} else if (xhr.status) {
					message = message + ' (HTTP ' + xhr.status + ')';
				}
				showResult($result, 'error', message);
				$btn.prop('disabled', false);
			});
	}

	$(document).on('click', '.ingram-ajax-btn', function (e) {
		e.preventDefault();

		var $btn = $(this);
		var action = $btn.data('action');
		var confirmMsg = $btn.data('confirm');

		if (confirmMsg && !window.confirm(confirmMsg)) {
			return;
		}

		// Warm the Zoho SKU/name lookup maps first so sync_zoho resolves items from
		// cache instead of a per-item Zoho API search (see warm_zoho_maps handler).
		if (action === 'sync_zoho') {
			runAjax('warm_zoho_maps', $btn, lastAttempt.warm_zoho_maps, function () {
				runAjax('sync_zoho', $btn, lastAttempt.sync_zoho);
			});
			return;
		}

		// Resume from the last attempted chunk (page/offset/exclude_ids/lock_token)
		// instead of restarting at the beginning if the previous click timed out.
		runAjax(action, $btn, lastAttempt[action]);
	});
})(jQuery);
