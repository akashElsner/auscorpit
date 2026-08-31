/**
 * Saves the status dropdown in the Quote Requests list table via AJAX,
 * without leaving the list screen.
 */
(function () {
	'use strict';

	document.addEventListener('change', function (e) {
		var select = e.target.closest('.auscorp-rfq-status-select');

		if (!select) {
			return;
		}

		var savedLabel = select.parentNode.querySelector('.auscorp-rfq-status-saved');
		select.disabled = true;

		var body = new URLSearchParams({
			action: 'auscorp_rfq_update_status',
			post_id: select.dataset.postId,
			nonce: select.dataset.nonce,
			status: select.value,
		});

		fetch(ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (res) {
				select.disabled = false;

				if (res.success && savedLabel) {
					savedLabel.style.display = 'inline';
					window.setTimeout(function () {
						savedLabel.style.display = 'none';
					}, 1500);
				}
			})
			.catch(function () {
				select.disabled = false;
			});
	});
})();
