import { select, on } from 'lib/dom'

export default el => {
	const checkoutBlock = select('[data-block="checkout-custom"]', el) || el

	if (!checkoutBlock) {
		return
	}

	let settings = {}
	try {
		const settingsNode = select('.woocommerce-checkout-custom--settings', checkoutBlock)
		settings = JSON.parse(settingsNode ? settingsNode.getAttribute('data-settings') || '{}' : '{}')
	} catch (error) {
		settings = {}
	}

	const checkoutForm = select('form.checkout', checkoutBlock)
	let refreshTimer = null
	let loadingOverlay = null

	const handleProceedToPayment = event => {
		const target = event.target
		const button = target && target.closest ? target.closest('.submit-thanh-toan') : null
		if (!button || !checkoutBlock.contains(button)) return

		event.preventDefault()
		console.log('submit-thanh-toan clicked')

		const placeOrderButton = checkoutForm ? checkoutForm.querySelector('#place_order') : null
		console.log('placeOrderButton:', placeOrderButton)

		if (placeOrderButton && typeof placeOrderButton.click === 'function') {
			console.log('clicking #place_order')
			placeOrderButton.click()
			return
		}

		if (checkoutForm && typeof checkoutForm.requestSubmit === 'function') {
			console.log('calling checkoutForm.requestSubmit()')
			checkoutForm.requestSubmit()
		}
	}

	// DOM helper getters scoped to this block
	const getCartForm = () => select('.woocommerce-cart-form')
	const getQuantityInput = () => select('.twmp-ticket-quantity__input', checkoutBlock)
	const getTicketDetailCard = () => select('.twmp-checkout-card--ticket-detail', checkoutBlock)
	const getTicketOptionItems = () =>
		Array.from(checkoutBlock.querySelectorAll('.twmp-ticket-option, .twmp-ticket-price-option'))

	/**
	 * syncActiveRadioState
	 * - Trigger: called when a ticket option radio input changes.
	 * - Purpose: toggles `.is-selected` and `.is-active` classes on the
	 *   option containers for the radio group so the UI reflects the
	 *   currently selected option.
	 */
	const syncActiveRadioState = input => {
		if (!input || !input.name) {
			return
		}

		const option = input.closest('.twmp-ticket-option, .twmp-ticket-price-option')
		if (!option) {
			return
		}

		const group = checkoutBlock.querySelectorAll(`input[name="${input.name}"]`)
		group.forEach(groupInput => {
			const groupOption = groupInput.closest('.twmp-ticket-option, .twmp-ticket-price-option')
			if (!groupOption) {
				return
			}

			const isActive = groupInput === input || groupInput.checked
			groupOption.classList.toggle('is-selected', isActive)
			groupOption.classList.toggle('is-active', isActive)
		})
	}

	/**
	 * setLoadingState
	 * - Show or hide a loading overlay inside the ticket detail card.
	 * - When: used to block UI while async operations (update/poll) run.
	 * - Adds `aria-busy` and `is-loading` classes on the block/card.
	 */
	const setLoadingState = isLoading => {
		const ticketDetailCard = getTicketDetailCard()

		if (isLoading) {
			if (!loadingOverlay && ticketDetailCard) {
				loadingOverlay = document.createElement('div')
				loadingOverlay.className = 'twmp-checkout-ticket-detail__loading'
				loadingOverlay.setAttribute('aria-hidden', 'true')
				loadingOverlay.style.cssText = [
					'position:absolute',
					'inset:0',
					'z-index:999',
					'cursor:wait',
					'background:rgba(255,255,255,.45)',
					'pointer-events:auto',
					'touch-action:none',
				].join(';')

				ticketDetailCard.style.position = ticketDetailCard.style.position || 'relative'
				ticketDetailCard.appendChild(loadingOverlay)
			}

			checkoutBlock.setAttribute('aria-busy', 'true')
			checkoutBlock.classList.add('is-loading')
			if (ticketDetailCard) {
				ticketDetailCard.classList.add('is-loading')
			}
			return
		}

		checkoutBlock.removeAttribute('aria-busy')
		checkoutBlock.classList.remove('is-loading')
		if (ticketDetailCard) {
			ticketDetailCard.classList.remove('is-loading')
		}

		if (loadingOverlay && loadingOverlay.parentNode) {
			loadingOverlay.parentNode.removeChild(loadingOverlay)
		}

		loadingOverlay = null
	}

	// Serialize a form to `application/x-www-form-urlencoded` string
	const serializeForm = form => {
		const formData = new FormData(form)
		return new URLSearchParams(formData).toString()
	}

	/**
	 * updateCheckoutSession
	 * - Called when ticket options/quantity change.
	 * - Purpose: call WooCommerce's `update_order_review` to refresh
	 *   server-side order/session data based on current checkout form.
	 */
	const updateCheckoutSession = () => {
		if (
			typeof window.wc_checkout_params === 'undefined' ||
			!window.wc_checkout_params ||
			!window.wc_checkout_params.wc_ajax_url ||
			!checkoutForm
		) {
			return Promise.resolve()
		}

		const body = new URLSearchParams()
		body.append('security', window.wc_checkout_params.update_order_review_nonce || '')
		body.append('post_data', serializeForm(checkoutForm))

		const url = window.wc_checkout_params.wc_ajax_url.replace('%%endpoint%%', 'update_order_review')

		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		}).then(() => undefined)
	}

	/**
	 * submitCartUpdate
	 * - Submit the cart form to update cart totals on the server.
	 * - If cart form is not present, fall back to a full page reload.
	 */
	const submitCartUpdate = () => {
		const cartForm = getCartForm()

		if (!cartForm) {
			window.location.reload()
			return Promise.resolve()
		}

		const body = new URLSearchParams(serializeForm(cartForm))
		body.set('update_cart', '1')

		return fetch(cartForm.action, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		}).then(() => {
			window.location.reload()
		})
	}

	// Ensure quantity is an integer >= 1
	const clampQuantity = value => {
		const parsed = Number.parseInt(value, 10)
		if (Number.isNaN(parsed) || parsed < 1) {
			return 1
		}

		return parsed
	}

	/**
	 * syncQuantityInput
	 * - Set quantity input programmatically and emit `change` so handlers run.
	 */
	const syncQuantityInput = nextValue => {
		const quantityInput = getQuantityInput()
		if (!quantityInput) {
			return
		}

		quantityInput.value = String(clampQuantity(nextValue))
		quantityInput.dispatchEvent(new Event('change', { bubbles: true }))
	}

	// Delegate click events inside `checkoutBlock` to handle proceed-to-payment
	on('click', handleProceedToPayment, checkoutBlock)

	/**
	 * updateQuantityByStep
	 * - Trigger: when element with `data-ticket-quantity-step` is clicked.
	 * - `step` should be 'plus' to increment; otherwise decrements.
	 */
	const updateQuantityByStep = step => {
		const quantityInput = getQuantityInput()
		if (!quantityInput) {
			return
		}

		const currentValue = clampQuantity(quantityInput.value)
		const nextValue = step === 'plus' ? currentValue + 1 : currentValue - 1
		syncQuantityInput(nextValue)
	}

	// Delegate click handler for quantity +/- step buttons inside block.
	// Elements should have `data-ticket-quantity-step="plus"` or `"minus"`.
	on(
		'click',
		e => {
			const target = e.target
			const stepButton = target && target.closest ? target.closest('[data-ticket-quantity-step]') : null

			if (!stepButton) {
				return
			}

			e.preventDefault()
			updateQuantityByStep(stepButton.getAttribute('data-ticket-quantity-step'))
		},
		checkoutBlock
	)

	// Delegated change handler for ticket option/quantity inputs
	on(
		'change',
		e => {
			const target = e.target

			if (
				!target ||
				![
					'twmp_ticket_price_option',
					'twmp_ticket_performance',
					'twmp_ticket_quantity',
				].includes(target.name)
			) {
				return
			}

			if (target.matches && target.matches('input[type="radio"]')) {
				syncActiveRadioState(target)
			}

			if (target.name === 'twmp_ticket_quantity') {
				target.value = String(clampQuantity(target.value))
			}

			const ticketDetailCard = getTicketDetailCard()
			if (ticketDetailCard) {
				const controlWrappers = ticketDetailCard.querySelectorAll(
					'.twmp-checkout-ticket-detail__options, [data-ticket-quantity-control]'
				)
				controlWrappers.forEach(wrapper => {
					wrapper.classList.toggle('is-disabled', true)
				})
			}

			// Debounce update: show loading state, call server to update
			window.clearTimeout(refreshTimer)
			setLoadingState(true)

			refreshTimer = window.setTimeout(() => {
				updateCheckoutSession()
					.then(() => submitCartUpdate())
					.catch(() => submitCartUpdate())
					.finally(() => {
						setLoadingState(false)
						const ticketDetailCard = getTicketDetailCard()
						if (ticketDetailCard) {
							const controlWrappers = ticketDetailCard.querySelectorAll(
								'.twmp-checkout-ticket-detail__options, [data-ticket-quantity-control]'
							)
							controlWrappers.forEach(wrapper => {
								wrapper.classList.toggle('is-disabled', false)
							})
						}
					})
			}, 100)
		},
		checkoutBlock
	)

	// On init: mark option items that are checked as selected/active so the
	// UI reflects the initial state.
	getTicketOptionItems().forEach(item => {
		const input = item.querySelector('input[type="radio"]')
		if (input && input.checked) {
			item.classList.add('is-selected', 'is-active')
		}
	})

	// Payment / step-2 handling (copied from backup)
	const stage = select('[data-payment-stage]', checkoutBlock)
	const proofForm = select('[data-payment-proof-form]', checkoutBlock)
	const fileInput = select('[data-payment-file]', checkoutBlock)
	const filePicker = select('.twmp-checkout-proof-form__file', checkoutBlock)
	const fileLabel = select('[data-payment-file-label]', checkoutBlock)
	const submitButton = select('[data-payment-submit]', checkoutBlock)
	const notice = select('[data-payment-notice]', checkoutBlock)
	const statusBadge = select('[data-payment-status-badge]', checkoutBlock)
	const statusTitle = select('[data-payment-status-title]', checkoutBlock)
	const statusText = select('[data-payment-status-text]', checkoutBlock)

	if (!stage || !proofForm || !fileInput || !submitButton) {
		return
	}

	if (stage.getAttribute('data-payment-initialized') === '1') {
		return
	}

	stage.setAttribute('data-payment-initialized', '1')

	const ajaxUrl = settings.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php'
	const orderId = stage.getAttribute('data-order-id') || settings.orderId || ''
	const orderKey = stage.getAttribute('data-order-key') || settings.orderKey || ''
	const nonce = stage.getAttribute('data-payment-nonce') || settings.nonce || ''
	const pollAction = settings.pollAction || 'twmp_checkout_poll_payment_status'
	const uploadAction = settings.uploadAction || 'twmp_checkout_upload_payment_proof'
	const pollInterval = Number.parseInt(settings.pollInterval || 15000, 10) || 15000
	const initialStatus = stage.getAttribute('data-payment-status') || ''
	let pollTimer = null
	let isUploading = false

	// postAjax: lightweight POST helper returning parsed JSON or null
	const postAjax = async (payload, useFormData = false) => {
		if (!ajaxUrl) {
			return null
		}

		const requestInit = {
			method: 'POST',
			credentials: 'same-origin',
		}

		if (useFormData) {
			requestInit.body = payload
		} else {
			requestInit.headers = {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			}
			requestInit.body = payload.toString()
		}

		try {
			const response = await fetch(ajaxUrl, requestInit)
			const raw = await response.text().catch(() => '')

			let data = null
			if (raw) {
				try {
					data = JSON.parse(raw)
				} catch (error) {
					data = {
						success: false,
						data: {
							message: raw,
						},
					}
				}
			}

			if (!response.ok) {
				throw data || new Error('Request failed')
			}

			return data
		} catch (error) {
			console.error('checkout-custom ajax failed:', error)
			throw error
		}
	}

	const setNotice = (message, type) => {
		if (!notice) {
			return
		}

		notice.textContent = message || ''
		notice.dataset.state = type || ''
		notice.classList.toggle('is-error', type === 'error')
		notice.classList.toggle('is-success', type === 'success')
		notice.classList.toggle('is-waiting', type === 'waiting')
	}

	const setFileLabel = fileName => {
		if (fileLabel) {
			fileLabel.textContent = fileName || settings.fileLabel || 'Choose bill file'
		}
	}

	const setButtonState = (disabled, label) => {
		submitButton.classList.toggle('is-loading', !!disabled && isUploading)

		if (label) {
			submitButton.textContent = label
		}
	}

	const getStatusPayload = response => {
		if (!response || !response.success || !response.data) {
			return null
		}

		return response.data.status || null
	}

	const applyStatus = payload => {
		if (!payload) {
			return
		}

		stage.setAttribute('data-payment-status', payload.proof_status || '')

		if (statusBadge) {
			statusBadge.textContent = payload.action_label || payload.status_label || ''
		}

		if (statusTitle) {
			statusTitle.textContent = payload.status_label || ''
		}

		if (statusText) {
			statusText.textContent = payload.status_text || ''
		}

		if (payload.proof_status === 'approved') {
			setNotice(payload.status_text || 'Payment confirmed.', 'success')
			setButtonState(true, settings.approvedLabel || 'Confirmed')
			if (pollTimer) {
				window.clearInterval(pollTimer)
				pollTimer = null
			}
			return
		}

		if (payload.proof_status === 'rejected') {
			setNotice(payload.review_note || payload.status_text || 'Bill rejected. Please upload again.', 'error')
			setButtonState(false, settings.uploadLabel || settings.billTitle || 'Upload bill')
			return
		}

		if (payload.proof_status === 'pending_review') {
			setNotice(payload.status_text || 'Waiting for admin review.', 'waiting')
			setButtonState(true, settings.waitingLabel || 'Waiting for confirmation')
			return
		}

		setButtonState(false, settings.uploadLabel || settings.billTitle || 'Upload bill')
		setNotice(payload.status_text || '', '')
	}

	const pollStatus = () => {
		if (!orderId || !orderKey || !nonce) {
			return
		}

		const payload = new URLSearchParams()
		payload.append('action', pollAction)
		payload.append('order_id', orderId)
		payload.append('order_key', orderKey)
		payload.append('nonce', nonce)

		postAjax(payload).then(response => {
			const payload = getStatusPayload(response)
			if (payload) {
				applyStatus(payload)
			}
		}).catch(() => undefined)
	}

	const startPolling = () => {
		if (pollTimer) {
			window.clearInterval(pollTimer)
		}

		pollTimer = window.setInterval(pollStatus, pollInterval)
	}

	const uploadBill = file => {
		if (!file) {
			setNotice(settings.noFileMessage || 'Please choose a bill file first.', 'error')
			return
		}

		const fileInputLocal = proofForm.querySelector('[data-payment-file]')
		const fileLabelLocal = proofForm.querySelector('[data-payment-file-label]')
		const submitBtnLocal = proofForm.querySelector('[data-payment-submit]')

		fileInputLocal?.addEventListener('change', function () {
			const file = this.files?.[0]

			if (file && fileLabelLocal) {
				fileLabelLocal.textContent = file.name
			}
		})

		proofForm.addEventListener('submit', async function (event) {
			event.preventDefault()

			const file = fileInputLocal.files?.[0]

			if (!file) {
				alert('Please upload payment receipt.')
				return
			}

			const formData = new FormData(proofForm)
			formData.append('action', uploadAction)

			submitBtnLocal.disabled = true
			submitBtnLocal.textContent = 'Uploading...'

			try {
				const response = await fetch(ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
				})

				const result = await response.json()

				if (!result.success) {
					throw new Error(result.data?.message || 'Upload failed.')
				}

				fileLabelLocal.textContent = result.data.filename
				submitBtnLocal.textContent = 'Uploaded'
				alert(result.data.message || 'Bill uploaded successfully.')
				// window.location.href = result.data.redirect_url || window.location.href
			} catch (error) {
				alert(error.message)
				submitBtnLocal.disabled = false
				submitBtnLocal.textContent = 'Upload bill'
			}
		})
	}

	on(
		'submit',
		event => {
			event.preventDefault()
			uploadBill(fileInput.files && fileInput.files[0] ? fileInput.files[0] : null)
		},
		proofForm
	)

	on('change', function () {
		const file = this.files && this.files[0] ? this.files[0] : null
		setFileLabel(file ? file.name : '')
		if (!file) {
			setNotice('', '')
			return
		}
		setNotice(settings.selectedFileMessage || file.name, '')
	}, fileInput)

	if (filePicker) {
		on('click', event => {
			if (fileInput.disabled) {
				return
			}

			const target = event.target
			if (target === fileInput) {
				return
			}

			event.preventDefault()
			fileInput.click()
		}, filePicker)
	}

	if (stage.getAttribute('data-payment-status') === 'approved') {
		setButtonState(true, settings.approvedLabel || 'Confirmed')
	} else if (stage.getAttribute('data-payment-status') === 'pending_review') {
		setButtonState(true, settings.waitingLabel || 'Waiting for confirmation')
	} else if (stage.getAttribute('data-payment-status') === 'rejected') {
		setButtonState(false, settings.uploadLabel || settings.billTitle || 'Upload bill')
	} else {
		setButtonState(false, settings.uploadLabel || settings.billTitle || 'Upload bill')
	}

	setFileLabel('')
	pollStatus()
	if (initialStatus !== 'approved') {
		startPolling()
	}
}

