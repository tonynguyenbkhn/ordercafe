const config = window.twmpCafeMenu || {}

const state = {
	cartOpen: false,
	modalOpen: false,
	product: null,
	currentStepIndex: 0,
	selections: {},
	noteSelections: {},
	editingCartItemKey: '',
	quantity: 1,
	note: ''
}

const qs = (selector, root = document) => root.querySelector(selector)
const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector))
const escapeHtml = value => String(value ?? '')
	.replace(/&/g, '&amp;')
	.replace(/</g, '&lt;')
	.replace(/>/g, '&gt;')
	.replace(/"/g, '&quot;')
	.replace(/'/g, '&#039;')

const parseProduct = node => {
	if (!node) {
		return null
	}

	try {
		return JSON.parse(node.dataset.cafeProduct || '{}')
	} catch (error) {
		return null
	}
}

const parseCartItem = node => {
	if (!node) {
		return null
	}

	try {
		return JSON.parse(node.dataset.cafeCartItem || '{}')
	} catch (error) {
		return null
	}
}

const buildAjaxBody = (action, data = {}) => {
	const body = new URLSearchParams()
	body.set('action', action)
	body.set('nonce', config.nonce || '')

	Object.entries(data).forEach(([key, value]) => {
		if (value === undefined || value === null) {
			return
		}

		if (typeof value === 'object' && !Array.isArray(value)) {
			Object.entries(value).forEach(([nestedKey, nestedValue]) => {
				body.append(`${key}[${nestedKey}]`, nestedValue)
			})
			return
		}

		if (Array.isArray(value)) {
			value.forEach(item => body.append(`${key}[]`, item))
			return
		}

		body.set(key, value)
	})

	return body
}

const requestCart = async (action, data = {}, useAjax = false) => {
	if (useAjax || !config.restUrl) {
		const response = await fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: buildAjaxBody(action, data).toString()
		})

		return response.json()
	}

	const routes = {
		twmp_cafe_menu_get_cart: ['cart', 'GET'],
		twmp_cafe_menu_add_to_cart: ['cart/add', 'POST'],
		twmp_cafe_menu_update_cart: ['cart/update', 'PUT'],
		twmp_cafe_menu_remove_cart: ['cart/remove', 'POST'],
		twmp_cafe_menu_clear_cart: ['cart/clear', 'POST']
	}
	const route = routes[action] || [action, 'POST']
	const baseUrl = config.restUrl || '/wp-json/twmp-ath/v1/cafe-menu/'

	const response = await fetch(baseUrl.replace(/\/$/, '') + '/' + route[0], {
		method: route[1],
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce || ''
		},
		body: route[1] === 'GET' ? undefined : JSON.stringify(data)
	})

	const payload = await response.json()
	if (!response.ok) {
		throw payload
	}

	return payload
}

const post = async (action, data = {}) => {
	try {
		return await requestCart(action, data)
	} catch (error) {
		return requestCart(action, data, true)
	}
}

const getLocalBusinessDate = (date = new Date()) => {
	const year = date.getFullYear()
	const month = String(date.getMonth() + 1).padStart(2, '0')
	const day = String(date.getDate()).padStart(2, '0')

	return `${year}-${month}-${day}`
}

const getNextLocalMidnightDelay = () => {
	const now = new Date()
	const nextMidnight = new Date(now)
	nextMidnight.setHours(24, 0, 0, 0)

	return Math.max(1000, nextMidnight.getTime() - now.getTime())
}

const clearCartForNewBusinessDay = async () => {
	try {
		const response = await post('twmp_cafe_menu_clear_cart')
		if (response?.success) {
			replaceCart(response.data)
			closeCart()
		}
	} catch (error) {
		// Keep current cart if the clear request fails; the next check will retry.
	}
}

const bootDailyCartClear = () => {
	const storageKey = 'twmp_cafe_menu_business_date'
	const today = getLocalBusinessDate()

	try {
		const lastDate = window.localStorage ? localStorage.getItem(storageKey) : today
		if (lastDate && lastDate !== today) {
			clearCartForNewBusinessDay()
		}

		if (window.localStorage) {
			localStorage.setItem(storageKey, today)
		}
	} catch (error) {
		// localStorage can be unavailable in private/browser-restricted contexts.
	}

	window.setTimeout(() => {
		try {
			if (window.localStorage) {
				localStorage.setItem(storageKey, getLocalBusinessDate())
			}
		} catch (error) {
			// Ignore storage failures.
		}

		clearCartForNewBusinessDay()
		bootDailyCartClear()
	}, getNextLocalMidnightDelay())
}

const replaceCart = payload => {
	if (!payload || !payload.cart) {
		return
	}

	const cart = qs('[data-cafe-cart]')
	if (cart && payload.cart.html) {
		cart.outerHTML = payload.cart.html
	}

	qsa('[data-cafe-cart-count]').forEach(node => {
		node.textContent = payload.cart.count ?? 0
	})

	qsa('.js-cafe-cart-subtotal').forEach(node => {
		node.innerHTML = payload.cart.subtotal || ''
	})

	qsa('.js-cafe-cart-total').forEach(node => {
		node.innerHTML = payload.cart.total || ''
	})
}

const setMessage = (root, message, isError = false) => {
	const node = qs('[data-cafe-modal-message]', root)
	if (!node) {
		return
	}

	node.textContent = message || ''
	node.classList.toggle('is-error', !!isError)
	node.classList.toggle('is-success', !isError && !!message)
}

const setStaffOrderMessage = (form, message, isError = false) => {
	const node = qs('[data-cafe-staff-order-message]', form)
	if (!node) {
		return
	}

	node.textContent = message || ''
	node.classList.toggle('is-error', !!isError)
	node.classList.toggle('is-success', !isError && !!message)
}

const createStaffOrder = async data => {
	const staffOrder = config.staffOrder || {}
	if (!staffOrder.enabled || !staffOrder.restUrl) {
		throw new Error('Staff order endpoint unavailable.')
	}

	const response = await fetch(staffOrder.restUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce || ''
		},
		body: JSON.stringify(data || {})
	})

	const payload = await response.json()
	if (!response.ok) {
		throw new Error(payload?.message || config.strings?.orderError || 'Create order failed')
	}

	return payload
}

const openCart = () => {
	state.cartOpen = true
	document.body.classList.add('is-cafe-cart-open')
}

const closeCart = () => {
	state.cartOpen = false
	document.body.classList.remove('is-cafe-cart-open')
}

const toggleCart = () => {
	if (state.cartOpen) {
		closeCart()
		return
	}

	openCart()
}

const getModal = () => qs('[data-cafe-modal]')

const getProductSteps = () => state.product?.steps || []

const getProductVariations = () => state.product?.variations || []

const getStaffNoteSteps = () => state.product?.staff_note_steps || []

const getSelectedNoteValue = fieldName => state.noteSelections[fieldName] || ''

const getSelectedStaffNoteLabels = () => {
	const payload = {}
	const steps = getStaffNoteSteps()

	steps.forEach(step => {
		const selectedValue = state.noteSelections[step.field_name]
		if (!selectedValue) {
			return
		}

		const selectedChoice = (step.choices || []).find(choice => choice.value === selectedValue)
		payload[step.field_name] = selectedChoice ? selectedChoice.label : selectedValue
	})

	return payload
}

const hydrateSelectionsFromCartItem = cartItem => {
	if (!cartItem) {
		return
	}

	Object.entries(cartItem.variation || {}).forEach(([key, value]) => {
		const normalizedKey = String(key || '').replace(/^attribute_/, '')
		if (normalizedKey) {
			state.selections[normalizedKey] = String(value || '')
		}
	})

	const noteSteps = getStaffNoteSteps()
	const staffNotes = cartItem.staff_notes || {}

	Object.entries(staffNotes).forEach(([key, value]) => {
		const step = noteSteps.find(item => item.field_name === key)
		if (!step) {
			return
		}

		const selectedChoice = (step.choices || []).find(choice => choice.label === value || choice.value === value)
		if (selectedChoice) {
			state.noteSelections[key] = selectedChoice.value
		}
	})

	state.note = cartItem.note || ''
	state.quantity = Number.isFinite(Number(cartItem.quantity)) ? Math.max(1, parseInt(cartItem.quantity, 10) || 1) : 1
	state.editingCartItemKey = cartItem.cart_item_key || ''
}

const buildVariationPayload = () => {
	const payload = {}

	Object.entries(state.selections).forEach(([key, value]) => {
		if (!value) {
			return
		}

		const normalizedKey = key.startsWith('attribute_') ? key : `attribute_${key}`
		payload[normalizedKey] = value
	})

	return payload
}

const findStepIndex = () => {
	const steps = getProductSteps()

	if (!steps.length) {
		return -1
	}

	const firstIncomplete = steps.findIndex(step => !state.selections[step.field_name])
	return firstIncomplete >= 0 ? firstIncomplete : steps.length - 1
}

const getSelectedVariation = () => {
	const product = state.product
	if (!product) {
		return null
	}

	const selections = state.selections
	return getProductVariations().find(variation => {
		const attrs = variation.attributes || {}
		return Object.entries(attrs).every(([key, value]) => {
			if (!value) {
				return true
			}
			const normalizedKey = String(key || '').replace(/^attribute_/, '')
			return (selections[normalizedKey] || '') === value
		})
	}) || null
}

const getCurrentPriceHtml = () => {
	const variation = getSelectedVariation()
	if (variation?.price_html) {
		return variation.price_html
	}

	return state.product?.price_html || ''
}

const getCurrentStep = () => {
	const steps = getProductSteps()
	if (!steps.length) {
		return null
	}

	const index = Math.max(0, Math.min(state.currentStepIndex, steps.length - 1))
	return {
		index,
		step: steps[index]
	}
}

const renderModal = () => {
	const modal = getModal()
	if (!modal || !state.product) {
		return
	}

	const product = state.product
	const steps = getProductSteps()
	const variation = getSelectedVariation()
	const noteSteps = getStaffNoteSteps()
	const currentStep = getCurrentStep()
	const hasSteps = steps.length > 0
	const completed = steps.filter(step => state.selections[step.field_name]).length

	const media = qs('[data-cafe-modal-media]', modal)
	const title = qs('[data-cafe-modal-title]', modal)
	const price = qs('[data-cafe-modal-price]', modal)
	// const description = qs('[data-cafe-modal-description]', modal)
	// const progress = qs('[data-cafe-modal-progress]', modal)
	const stepHost = qs('[data-cafe-modal-steps]', modal)
	// const summary = qs('[data-cafe-modal-summary]', modal)
	const note = qs('[data-cafe-modal-note]', modal)
	const qty = qs('[data-cafe-modal-qty]', modal)
	// const noteField = qs('[data-cafe-modal-note-field]', modal)
	const qtyField = qs('[data-cafe-modal-qty-field]', modal)
	const prevButton = qs('.js-cafe-modal-prev', modal)
	const nextButton = qs('.js-cafe-modal-next', modal)
	const addButton = qs('.js-cafe-modal-add', modal)

	if (media) {
		media.innerHTML = ''
		media.hidden = true
	}

	if (title) {
		title.textContent = product.name || ''
	}

	if (price) {
		price.innerHTML = getCurrentPriceHtml()
	}

	// if (description) {
	// 	description.innerHTML = product.description || ''
	// }

	if (note) {
		note.value = state.note || ''
		note.placeholder = product.note_placeholder || ''
	}

	if (qty) {
		qty.value = String(state.quantity || 1)
	}

	// if (noteField) {
	// 	noteField.style.display = 'grid'
	// }

	if (qtyField) {
		qtyField.style.display = 'inline-flex'
	}

	// if (progress) {
	// 	if (!hasSteps) {
	// 		progress.innerHTML = '<span class="twmp-cafe-modal__progress-item is-active">Đơn giản</span>'
	// 	} else {
	// 		progress.innerHTML = steps.map((step, index) => {
	// 			const classes = [
	// 				'twmp-cafe-modal__progress-item',
	// 				index === currentStep?.index ? 'is-active' : '',
	// 				state.selections[step.field_name] ? 'is-complete' : ''
	// 			].filter(Boolean).join(' ')

	// 			return `<span class="${classes}">${escapeHtml(step.label)}</span>`
	// 		}).join('')
	// 	}
	// }

	// if (summary) {
	// 	if (!hasSteps) {
	// 		if (noteSteps.length) {
	// 			summary.innerHTML = `
	// 				<div class="twmp-cafe-modal__notes">
	// 					<p class="twmp-cafe-modal__notes-title">Ghi chú cho nhân viên</p>
	// 					<div class="twmp-cafe-modal__notes-list">
	// 						${noteSteps.map(note => {
	// 							const selectedValue = getSelectedNoteValue(note.field_name)
	// 							const selectedChoice = (note.choices || []).find(choice => choice.value === selectedValue)
	// 							const values = selectedChoice ? selectedChoice.label : 'Chưa chọn'
	// 							return `
	// 								<div class="twmp-cafe-modal__note-row">
	// 									<span>${escapeHtml(note.label || note.name || '')}</span>
	// 									<strong>${escapeHtml(values)}</strong>
	// 								</div>
	// 							`
	// 						}).join('')}
	// 					</div>
	// 				</div>
	// 			`
	// 		} else {
	// 			summary.innerHTML = '<p class="twmp-cafe-modal__summary-empty">Không có tuỳ chọn bắt buộc.</p>'
	// 		}
	// 	} else {
	// 		const variationSummary = steps.map(step => {
	// 			const selectedValue = state.selections[step.field_name] || ''
	// 			const selectedChoice = (step.choices || []).find(choice => choice.value === selectedValue)
	// 			const label = selectedChoice ? selectedChoice.label : 'Chưa chọn'
	// 			return `
	// 				<div class="twmp-cafe-modal__summary-row">
	// 					<span>${escapeHtml(step.label)}</span>
	// 					<strong>${escapeHtml(label)}</strong>
	// 				</div>
	// 			`
	// 		}).join('')
	// 		const noteSummary = noteSteps.length
	// 			? noteSteps.map(step => {
	// 				const selectedValue = getSelectedNoteValue(step.field_name)
	// 				const selectedChoice = (step.choices || []).find(choice => choice.value === selectedValue)
	// 				const label = selectedChoice ? selectedChoice.label : 'Chưa chọn'
	// 				return `
	// 					<div class="twmp-cafe-modal__summary-row">
	// 						<span>${escapeHtml(step.label)}</span>
	// 						<strong>${escapeHtml(label)}</strong>
	// 					</div>
	// 				`
	// 			}).join('')
	// 			: ''

	// 		summary.innerHTML = variationSummary + noteSummary
	// 	}
	// }

	if (stepHost) {
		const noteMarkup = noteSteps.length
			? `
				<div class="twmp-cafe-modal__step twmp-cafe-modal__step--notes">
					<div class="twmp-cafe-modal__step-head">
						<p class="twmp-cafe-modal__step-kicker">Ghi chú</p>
					</div>
					<div class="twmp-cafe-modal__note-groups">
						${noteSteps.map((note, noteIndex) => {
							const choices = (note.choices || []).map(choice => {
								const selected = getSelectedNoteValue(note.field_name) === choice.value
								return `
									<button
										type="button"
										class="twmp-cafe-modal__choice twmp-cafe-modal__choice--note ${selected ? 'is-selected' : ''}"
										data-cafe-note-index="${noteIndex}"
										data-cafe-note-field="${escapeHtml(note.field_name)}"
										data-cafe-note-value="${escapeHtml(choice.value)}">
										${escapeHtml(choice.label)}
									</button>
								`
							}).join('')

							return `
								<div class="twmp-cafe-modal__note-group">
									<p class="twmp-cafe-modal__note-group-label">${escapeHtml(note.label || note.name || '')}</p>
									<div class="twmp-cafe-modal__choices twmp-cafe-modal__choices--notes">
										${choices}
									</div>
								</div>
							`
						}).join('')}
					</div>
				</div>
			`
			: ''

		if (!hasSteps) {
			stepHost.innerHTML = noteMarkup || '<div class="twmp-cafe-modal__step twmp-cafe-modal__step--simple"><p>Chỉ cần chọn số lượng và ghi chú, sau đó thêm vào giỏ.</p></div>'
		} else if (currentStep) {
			const choices = (currentStep.step.choices || []).map(choice => {
				const selected = state.selections[currentStep.step.field_name] === choice.value
				return `
					<label class="twmp-cafe-modal__choice twmp-cafe-modal__choice--checkbox ${selected ? 'is-selected' : ''}">
						<input
							type="checkbox"
							class="twmp-cafe-modal__choice-input js-cafe-step-checkbox"
							data-cafe-step-index="${currentStep.index}"
							data-cafe-step-field="${escapeHtml(currentStep.step.field_name)}"
							data-cafe-choice-value="${escapeHtml(choice.value)}"
							${selected ? 'checked' : ''}>
						<span class="twmp-cafe-modal__choice-box" aria-hidden="true"></span>
						<span class="twmp-cafe-modal__choice-label">${escapeHtml(choice.label)}</span>
					</label>
				`
			}).join('')

			stepHost.innerHTML = `
				<div class="twmp-cafe-modal__step">
					<div class="twmp-cafe-modal__step-head">
						<p class="twmp-cafe-modal__step-kicker">Bước ${currentStep.index + 1} / ${steps.length}</p>
						<h3 class="twmp-cafe-modal__step-title">${escapeHtml(currentStep.step.label)}</h3>
					</div>
					<div class="twmp-cafe-modal__choices">
						${choices}
					</div>
				</div>
				${noteMarkup}
			`
		}
	}

	if (prevButton) {
		prevButton.disabled = !hasSteps || currentStep?.index === 0
		prevButton.hidden = !hasSteps
	}

	if (nextButton) {
		nextButton.hidden = !hasSteps || !currentStep || currentStep.index >= steps.length - 1
		nextButton.disabled = !hasSteps || !currentStep || !state.selections[currentStep.step.field_name]
	}

	if (addButton) {
		const canAddSimple = !hasSteps
		const canAddVariable = !!variation?.variation_id
		addButton.disabled = !(canAddSimple || canAddVariable)
		addButton.textContent = state.editingCartItemKey
			? (config.strings?.editOptions || 'Cập nhật lựa chọn')
			: (config.strings?.addToCart || 'Thêm vào giỏ')
	}

	if (modal) {
		modal.dataset.hasSteps = hasSteps ? '1' : '0'
		modal.dataset.currentStep = currentStep ? String(currentStep.index) : '0'
		modal.dataset.completedSteps = String(completed)
	}
}

const openModal = (product, cartItem = null) => {
	if (!product || !product.is_in_stock || !product.is_purchasable) {
		return
	}

	state.product = product
	state.selections = {}
	state.noteSelections = {}
	state.editingCartItemKey = ''
	state.currentStepIndex = 0
	state.quantity = Number.isFinite(Number(product.quantity_default)) ? Math.max(1, parseInt(product.quantity_default, 10) || 1) : 1
	state.note = ''

	;(product.steps || []).forEach(step => {
		if (step.selected) {
			state.selections[step.field_name] = step.selected
		}
	})

	if (cartItem) {
		hydrateSelectionsFromCartItem(cartItem)
	}

	const modal = getModal()
	if (!modal) {
		return
	}

	modal.hidden = false
	document.body.classList.add('is-cafe-modal-open')
	state.modalOpen = true

	state.currentStepIndex = findStepIndex()
	renderModal()

	const noteInput = qs('[data-cafe-modal-note]', modal)
	// if (noteInput) {
	// 	noteInput.focus({ preventScroll: true })
	// }
}

const closeModal = () => {
	const modal = getModal()
	if (!modal) {
		return
	}

	modal.hidden = true
	document.body.classList.remove('is-cafe-modal-open')
	state.modalOpen = false
	state.product = null
	state.currentStepIndex = 0
	state.selections = {}
	state.noteSelections = {}
	state.editingCartItemKey = ''
	state.quantity = 1
	state.note = ''
	setMessage(modal, '')
}

const setStepSelection = (fieldName, value, stepIndex) => {
	if (!fieldName) {
		return
	}

	state.selections[fieldName] = value

	const steps = getProductSteps()
	steps.slice(stepIndex + 1).forEach(step => {
		delete state.selections[step.field_name]
	})

	state.currentStepIndex = Math.max(0, stepIndex)
	const nextStep = steps[stepIndex + 1]
	if (nextStep) {
		state.currentStepIndex = stepIndex + 1
	}

	renderModal()
}

const setNoteSelection = (fieldName, value) => {
	if (!fieldName) {
		return
	}

	if (state.noteSelections[fieldName] === value) {
		delete state.noteSelections[fieldName]
	} else {
		state.noteSelections[fieldName] = value
	}

	renderModal()
}

const addActiveProductToCart = async () => {
	const modal = getModal()
	if (!modal || !state.product) {
		return
	}

	setMessage(modal, '')

	const variation = getSelectedVariation()
	const hasSteps = getProductSteps().length > 0

	if (hasSteps && (!variation || !variation.variation_id)) {
		setMessage(modal, config.strings?.chooseAttrs || 'Please choose options', true)
		return
	}

	const payload = {
		product_id: state.product.product_id,
		quantity: Math.max(1, parseInt(state.quantity, 10) || 1),
		note: (state.note || '').trim(),
		staff_notes: getSelectedStaffNoteLabels()
	}

	if (variation?.variation_id) {
		payload.variation_id = variation.variation_id
		payload.variation = buildVariationPayload()
	}

	if (state.editingCartItemKey) {
		payload.cart_item_key = state.editingCartItemKey
	}

	const submitButton = qs('.js-cafe-modal-add', modal)
	if (submitButton) {
		submitButton.disabled = true
	}

	try {
		const response = await post('twmp_cafe_menu_add_to_cart', payload)
		if (!response || !response.success) {
			setMessage(modal, response?.data?.message || config.strings?.addError || 'Add to cart failed', true)
			return
		}

		replaceCart(response.data)
		setMessage(modal, response.data.message || config.strings?.cartUpdated || 'Updated')
		openCart()
		closeModal()
	} catch (error) {
		setMessage(modal, config.strings?.addError || 'Add to cart failed', true)
	} finally {
		if (submitButton) {
			submitButton.disabled = false
		}
	}
}

document.addEventListener('click', event => {
	const toggle = event.target.closest('.js-cafe-cart-toggle')
	if (toggle) {
		event.preventDefault()
		toggleCart()
		return
	}

	const category = event.target.closest('.js-cafe-category')
	if (category) {
		const targetId = category.dataset.target
		const target = targetId ? document.getElementById(targetId) : null

		if (target) {
			event.preventDefault()
			const offset = 96
			const top = target.getBoundingClientRect().top + window.pageYOffset - offset
			window.scrollTo({ top, behavior: 'smooth' })
		}
		return
	}

	const editButton = event.target.closest('.js-cafe-cart-edit')
	if (editButton) {
		event.preventDefault()
		const cartNode = editButton.closest('[data-cafe-cart-item]')
		if (!cartNode) {
			return
		}

		const cartItem = parseCartItem(cartNode)
		openModal(cartItem, cartItem)
		return
	}

	const openProduct = event.target.closest('.js-cafe-product-open')
	if (openProduct) {
		const card = openProduct.closest('[data-cafe-product]')
		openModal(parseProduct(card))
		return
	}

	if (event.target.closest('.js-cafe-modal-close')) {
		event.preventDefault()
		closeModal()
	}
})

document.addEventListener('keydown', event => {
	if (event.key === 'Escape' && state.modalOpen) {
		closeModal()
	}
})

document.addEventListener('click', async event => {
	const qtyButton = event.target.closest('.js-cafe-qty')
	if (!qtyButton) {
		return
	}

	const modal = getModal()
	if (!modal) {
		return
	}

	const form = qtyButton.closest('.twmp-cafe-form')
	const input = form ? qs('.js-cafe-qty-input', form) : null

	if (!input) {
		return
	}

	event.preventDefault()
	event.stopPropagation()
	event.stopImmediatePropagation()
	const delta = parseInt(qtyButton.dataset.delta || '0', 10)
	const nextValue = Math.max(1, (parseInt(input.value, 10) || 1) + delta)
	input.value = String(nextValue)
})

document.addEventListener('input', event => {
	const modal = getModal()
	if (!modal || !state.modalOpen) {
		return
	}

	if (event.target.matches('[data-cafe-modal-note]')) {
		state.note = event.target.value
		return
	}

	if (event.target.matches('[data-cafe-modal-qty]')) {
		state.quantity = Math.max(1, parseInt(event.target.value, 10) || 1)
		return
	}
})

document.addEventListener('click', event => {
	const modal = getModal()
	if (!modal || !state.modalOpen) {
		return
	}

	const modalQtyButton = event.target.closest('.js-cafe-modal-qty')
	if (modalQtyButton) {
		event.preventDefault()

		const qtyInput = qs('[data-cafe-modal-qty]', modal)
		if (!qtyInput) {
			return
		}

		const delta = parseInt(modalQtyButton.dataset.delta || '0', 10)
		const nextValue = Math.max(1, (parseInt(qtyInput.value, 10) || 1) + delta)
		qtyInput.value = String(nextValue)
		state.quantity = nextValue
		return
	}

	const noteButton = event.target.closest('[data-cafe-note-index][data-cafe-note-value]')
	if (noteButton) {
		event.preventDefault()
		setNoteSelection(noteButton.dataset.cafeNoteField, noteButton.dataset.cafeNoteValue)
		return
	}

	const prevButton = event.target.closest('.js-cafe-modal-prev')
	if (prevButton) {
		event.preventDefault()
		state.currentStepIndex = Math.max(0, state.currentStepIndex - 1)
		renderModal()
		return
	}

	const nextButton = event.target.closest('.js-cafe-modal-next')
	if (nextButton) {
		event.preventDefault()
		state.currentStepIndex = Math.min(getProductSteps().length - 1, state.currentStepIndex + 1)
		renderModal()
		return
	}

	const addButton = event.target.closest('.js-cafe-modal-add')
	if (addButton) {
		event.preventDefault()
		addActiveProductToCart()
	}
})

document.addEventListener('change', event => {
	const modal = getModal()
	if (!modal || !state.modalOpen) {
		return
	}

	const choiceInput = event.target.closest('.js-cafe-step-checkbox')
	if (!choiceInput) {
		return
	}

	const fieldName = choiceInput.dataset.cafeStepField
	const value = choiceInput.dataset.cafeChoiceValue
	const index = parseInt(choiceInput.dataset.cafeStepIndex || '0', 10)

	if (choiceInput.checked) {
		setStepSelection(fieldName, value, index)
		return
	}

	if (fieldName && state.selections[fieldName] === value) {
		delete state.selections[fieldName]
		renderModal()
	}
})

document.addEventListener('click', async event => {
	const removeButton = event.target.closest('.js-cafe-cart-remove')
	const qtyButton = event.target.closest('.js-cafe-cart-qty')

	if (!removeButton && !qtyButton) {
		return
	}

	event.preventDefault()
	event.stopPropagation()
	event.stopImmediatePropagation()

	const cartKey = (removeButton || qtyButton).dataset.cartKey
	if (!cartKey) {
		return
	}

	const action = removeButton ? 'twmp_cafe_menu_remove_cart' : 'twmp_cafe_menu_update_cart'
	const qtyInput = qtyButton ? qtyButton.closest('.twmp-cafe-cart__item').querySelector('.js-cafe-cart-qty-value') : null
	const currentQuantity = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1
	const quantity = qtyButton
		? Math.max(0, currentQuantity + parseInt(qtyButton.dataset.delta || '0', 10))
		: 0

	if (qtyInput && qtyButton) {
		qtyInput.value = String(quantity)
	}

	try {
		const response = await post(action, {
			cart_item_key: cartKey,
			quantity
		})

		if (!response || !response.success) {
			return
		}

		replaceCart(response.data)
		if (response.data.cart && response.data.cart.count <= 0) {
			closeCart()
		}
	} catch (error) {
		// Keep existing state if request fails.
	}
})

document.addEventListener('submit', async event => {
	const form = event.target.closest('[data-cafe-staff-order-form]')
	if (!form) {
		return
	}

	event.preventDefault()
	const submitButton = qs('[type="submit"]', form)
	const formData = new FormData(form)
	const payload = {}

	formData.forEach((value, key) => {
		payload[key] = value
	})

	if (!payload.payment_method) {
		payload.payment_method = config.staffOrder?.defaultPaymentMethod || 'cod'
	}

	setStaffOrderMessage(form, config.strings?.creatingOrder || 'Đang tạo đơn...')
	if (submitButton) {
		submitButton.disabled = true
	}

	try {
		const response = await createStaffOrder(payload)
		const orderNumber = response?.order_number ? ` #${response.order_number}` : ''
		setStaffOrderMessage(form, (config.strings?.orderCreated || 'Đã tạo đơn.') + orderNumber)

		let redirectUrl = response?.staff_orders_url || ''
		if (!redirectUrl && response?.order_id) {
			const staffOrdersUrl = config.staffOrder?.staffOrdersUrl || '/staff-orders/'
			const separator = staffOrdersUrl.includes('?') ? '&' : '?'
			redirectUrl = `${staffOrdersUrl}${separator}twmp_order_id=${encodeURIComponent(response.order_id)}&order_status=all&twmp_order_created=1`
		}

		if (redirectUrl) {
			window.location.assign(redirectUrl)
			return
		}

		if (response?.cart) {
			replaceCart({ cart: response.cart })
		}
	} catch (error) {
		setStaffOrderMessage(form, error?.message || config.strings?.orderError || 'Vui lòng thử lại.', true)
	} finally {
		if (submitButton) {
			submitButton.disabled = false
		}
	}
})

const observeSections = () => {
	const sections = qsa('[data-menu-section]')
	const categories = qsa('.js-cafe-category')

	if (!sections.length || !categories.length) {
		return
	}

	const setActive = id => {
		categories.forEach(category => {
			category.classList.toggle('is-active', category.dataset.target === id)
		})
	}

	const getScrollOffset = () => {
		const topbar = qs('.twmp-cafe-menu__topbar')
		return (topbar ? topbar.offsetHeight : 0) + 24
	}

	let ticking = false

	const updateActiveSection = () => {
		const triggerY = window.pageYOffset + getScrollOffset()
		let activeId = 'twmp-cafe-menu'

		sections.forEach(section => {
			if (section.offsetTop <= triggerY) {
				activeId = section.id
			}
		})

		setActive(activeId)
		ticking = false
	}

	const requestUpdate = () => {
		if (!ticking) {
			window.requestAnimationFrame(updateActiveSection)
			ticking = true
		}
	}

	updateActiveSection()
	window.addEventListener('scroll', requestUpdate, { passive: true })
	window.addEventListener('resize', requestUpdate)
}

const boot = () => {
	bootDailyCartClear()
	observeSections()
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot, { once: true })
} else {
	boot()
}
