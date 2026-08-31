/**
 * Front-end quote cart interactions: add-to-quote buttons (shop loop, grid
 * widget, single product), the quote page's quantity/remove controls, and
 * the request-a-quote submission form. Vanilla JS, no jQuery dependency.
 */
(function () {
	'use strict';

	if (typeof AuscorpRFQ === 'undefined') {
		return;
	}

	function post(action, data) {
		var body = new URLSearchParams(Object.assign({ action: action, nonce: AuscorpRFQ.nonce }, data));

		return fetch(AuscorpRFQ.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		}).then(function (response) {
			return response.json();
		});
	}

	function updateBadge(count) {
		document.querySelectorAll('.auscorp-rfq-badge').forEach(function (badge) {
			badge.textContent = count;
			badge.style.display = count > 0 ? '' : 'none';
		});
	}

	function showButtonFeedback(button, message, isError) {
		var original = button.dataset.originalLabel || button.innerHTML;
		button.dataset.originalLabel = original;
		button.querySelector('span') ? (button.querySelector('span').textContent = message) : (button.textContent = message);
		button.classList.toggle('auscorp-rfq-btn--error', !!isError);

		window.setTimeout(function () {
			button.innerHTML = original;
			button.classList.remove('auscorp-rfq-btn--error');
			button.disabled = false;
		}, 1800);
	}

	/* ---- Shop loop / grid widget "Add to Quote" buttons ---- */

	document.addEventListener('click', function (e) {
		var button = e.target.closest('.addquotelistbutton');

		if (!button || button.disabled) {
			return;
		}

		e.preventDefault();
		button.disabled = true;

		post('auscorp_rfq_add', {
			product_id: button.dataset.product_id,
			quantity: button.dataset.quantity || 1,
		}).then(function (res) {
			if (res.success) {
				updateBadge(res.data.count);
				showButtonFeedback(button, AuscorpRFQ.i18n.added || 'Added!', false);
			} else {
				showButtonFeedback(button, res.data.message || AuscorpRFQ.i18n.genericError, true);
			}
		}).catch(function () {
			button.disabled = false;
		});
	});

	/* ---- Single product page: hijack the add-to-cart form for guests ---- */

	function getVariationText(form) {
		var rows = form.querySelectorAll('.variations tr');
		var parts = [];

		rows.forEach(function (row) {
			var label = row.querySelector('label');
			var select = row.querySelector('select');

			if (!select || !select.value) {
				return;
			}

			var optionText = select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent : select.value;
			parts.push((label ? label.textContent.trim() : '') + ': ' + optionText.trim());
		});

		return parts.join(', ');
	}

	document.addEventListener('submit', function (e) {
		var form = e.target.closest('form.cart');

		if (!form) {
			return;
		}

		e.preventDefault();

		var isVariable = !!form.querySelector('.variations');
		var variationIdInput = form.querySelector('input[name="variation_id"], input.variation_id');
		var variationId = variationIdInput ? parseInt(variationIdInput.value, 10) || 0 : 0;

		if (isVariable && !variationId) {
			var notice = form.querySelector('.auscorp-rfq-form-notice') || document.createElement('p');
			notice.className = 'auscorp-rfq-form-notice';
			notice.textContent = AuscorpRFQ.i18n.selectOptions || 'Please select product options first.';
			if (!form.contains(notice)) {
				form.appendChild(notice);
			}
			return;
		}

		var productInput = form.querySelector('input[name="product_id"]') || form.querySelector('button[name="add-to-cart"]');
		var productId = productInput ? productInput.value : 0;
		var quantityInput = form.querySelector('input[name="quantity"]');
		var quantity = quantityInput ? quantityInput.value : 1;
		var submitButton = form.querySelector('button[type="submit"]');

		if (submitButton) {
			submitButton.disabled = true;
		}

		post('auscorp_rfq_add', {
			product_id: productId,
			variation_id: variationId,
			quantity: quantity,
			variation_text: isVariable ? getVariationText(form) : '',
		}).then(function (res) {
			if (submitButton) {
				submitButton.disabled = false;
			}

			var notice = form.querySelector('.auscorp-rfq-form-notice') || document.createElement('p');
			notice.className = 'auscorp-rfq-form-notice';

			if (res.success) {
				notice.classList.remove('auscorp-rfq-form-notice--error');
				notice.textContent = res.data.message;
				updateBadge(res.data.count);
			} else {
				notice.classList.add('auscorp-rfq-form-notice--error');
				notice.textContent = res.data.message || AuscorpRFQ.i18n.genericError;
			}

			if (!form.contains(notice)) {
				form.appendChild(notice);
			}
		}).catch(function () {
			if (submitButton) {
				submitButton.disabled = false;
			}
		});
	});

	/* ---- Quote page: quantity updates, line removal, request submission ---- */

	var page = document.querySelector('.auscorp-rfq-page');

	if (!page) {
		return;
	}

	page.addEventListener('change', function (e) {
		var input = e.target.closest('.auscorp-rfq-qty-input');

		if (!input) {
			return;
		}

		var row = input.closest('.auscorp-rfq-row');
		var quantity = Math.max(1, parseInt(input.value, 10) || 1);
		input.value = quantity;

		post('auscorp_rfq_update', {
			item_key: row.dataset.itemKey,
			quantity: quantity,
		}).then(function (res) {
			if (!res.success) {
				return;
			}

			row.querySelector('.auscorp-rfq-row__subtotal').textContent = res.data.line_subtotal;
			document.querySelector('.auscorp-rfq-total').textContent = res.data.total;
			updateBadge(res.data.count);
		});
	});

	page.addEventListener('click', function (e) {
		var button = e.target.closest('.auscorp-rfq-remove');

		if (!button) {
			return;
		}

		if (!window.confirm(AuscorpRFQ.i18n.confirmRemove)) {
			return;
		}

		var row = button.closest('.auscorp-rfq-row');

		post('auscorp_rfq_remove', { item_key: row.dataset.itemKey }).then(function (res) {
			if (!res.success) {
				return;
			}

			updateBadge(res.data.count);

			if (res.data.count === 0) {
				window.location.reload();
				return;
			}

			row.remove();
			document.querySelector('.auscorp-rfq-total').textContent = res.data.total;
		});
	});

	var form = page.querySelector('.auscorp-rfq-form');

	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();

			var responseEl = form.querySelector('.auscorp-rfq-response');
			var submitButton = form.querySelector('button[type="submit"]');

			submitButton.disabled = true;
			responseEl.classList.remove('auscorp-rfq-response--error');
			responseEl.textContent = '';

			post('auscorp_rfq_submit', {
				name: form.querySelector('[name="name"]').value,
				email: form.querySelector('[name="email"]').value,
				note: form.querySelector('[name="note"]').value,
			}).then(function (res) {
				if (res.success) {
					page.innerHTML = '<p class="auscorp-rfq-success">' + res.data.message + '</p>';
					updateBadge(0);
				} else {
					responseEl.classList.add('auscorp-rfq-response--error');
					responseEl.textContent = res.data.message || AuscorpRFQ.i18n.genericError;
					submitButton.disabled = false;
				}
			}).catch(function () {
				submitButton.disabled = false;
				responseEl.classList.add('auscorp-rfq-response--error');
				responseEl.textContent = AuscorpRFQ.i18n.genericError;
			});
		});
	}
})();
