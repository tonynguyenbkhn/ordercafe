const config = window.twmpCafeMenu || {}

const state = {
	cartOpen: false
}

const qs = (selector, root = document) => root.querySelector(selector)
const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector))

const post = async (action, data = {}) => {
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

	const response = await fetch(config.ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
		},
		body: body.toString()
	})

	return response.json()
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

const setMessage = (form, message, isError = false) => {
	const node = qs('.twmp-cafe-form__message', form)
	if (!node) {
		return
	}

	node.textContent = message || ''
	node.classList.toggle('is-error', !!isError)
	node.classList.toggle('is-success', !isError && !!message)
}

const getQty = form => {
	const input = qs('.js-cafe-qty-input', form)
	const qty = input ? parseInt(input.value, 10) : 1

	return Number.isFinite(qty) && qty > 0 ? qty : 1
}

const getNote = form => {
	const note = qs('[name="note"]', form)

	return note ? note.value.trim() : ''
}

const getVariationData = form => {
	const variationField = qs('[name="variation_id"]', form)
	const variationId = variationField ? parseInt(variationField.value, 10) : 0
	const attrs = {}

	qsa('.js-cafe-variation-attr', form).forEach(select => {
		attrs[select.name] = select.value
	})

	return {
		variationId,
		attributes: attrs
	}
}

const findVariation = (form, selectedAttrs) => {
	const variations = form.dataset.variations ? JSON.parse(form.dataset.variations) : []

	return variations.find(variation => {
		const attrs = variation.attributes || {}

		return Object.entries(attrs).every(([key, value]) => {
			if (!value) {
				return true
			}

			return (selectedAttrs[key] || '') === value
		})
	})
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
	}
})

document.addEventListener('click', event => {
	const qtyButton = event.target.closest('.js-cafe-qty')
	if (!qtyButton) {
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

document.addEventListener('submit', async event => {
	const form = event.target.closest('.js-cafe-simple-form, .js-cafe-variable-form')
	if (!form) {
		return
	}

	event.preventDefault()
	setMessage(form, '')

	const productId = parseInt(form.dataset.productId || '0', 10)
	const qty = getQty(form)
	const note = getNote(form)

	if (!productId) {
		setMessage(form, config.strings?.invalidForm || 'Invalid product', true)
		return
	}

	const payload = {
		product_id: productId,
		quantity: qty,
		note
	}

	if (form.classList.contains('js-cafe-variable-form')) {
		const selected = getVariationData(form)
		const variation = findVariation(form, selected.attributes)

		if (!variation || !variation.variation_id) {
			setMessage(form, config.strings?.chooseAttrs || 'Please choose options', true)
			return
		}

		payload.variation_id = variation.variation_id
		payload.variation = selected.attributes
	}

	const submitButton = qs('.twmp-cafe-form__submit', form)
	if (submitButton) {
		submitButton.disabled = true
	}

	try {
		const response = await post('twmp_cafe_menu_add_to_cart', payload)
		if (!response || !response.success) {
			setMessage(form, response?.data?.message || config.strings?.addError || 'Add to cart failed', true)
			return
		}

		replaceCart(response.data)
		setMessage(form, response.data.message || config.strings?.cartUpdated || 'Updated')
		openCart()
	} catch (error) {
		setMessage(form, config.strings?.addError || 'Add to cart failed', true)
	} finally {
		if (submitButton) {
			submitButton.disabled = false
		}
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
		// No-op: keep the existing cart state if the request fails.
	}
})

const observeSections = () => {
	const sections = qsa('[data-menu-section]')
	const categories = qsa('.js-cafe-category')

	if (!sections.length || !categories.length || !('IntersectionObserver' in window)) {
		return
	}

	const setActive = id => {
		categories.forEach(category => {
			category.classList.toggle('is-active', category.dataset.target === id)
		})
	}

	const observer = new IntersectionObserver(entries => {
		const visible = entries
			.filter(entry => entry.isIntersecting)
			.sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0]

		if (visible) {
			setActive(visible.target.id)
		}
	}, {
		rootMargin: '-20% 0px -65% 0px',
		threshold: [0.15, 0.35, 0.5]
	})

	sections.forEach(section => observer.observe(section))
}

const boot = () => {
	observeSections()
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot, { once: true })
} else {
	boot()
}
