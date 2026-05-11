const normalizeIconValue = value => String(value || '').trim().toLowerCase().replace(/\s+/g, '_')

const ICONS = {
	en: {url: twmpConfig.themePath + '/assets/images/icons/english.svg', name: 'English'},
	vi: {url: twmpConfig.themePath + '/assets/images/icons/vietnam.svg', name: 'Viet Nam'},
	fr: {url: twmpConfig.themePath + '/assets/images/icons/french.svg', name: 'French'},
}

const ICONS_BY_NAME = Object.fromEntries(Object.values(ICONS).map(icon => [normalizeIconValue(icon.name), icon]))

const BODY_LOADING_CLASS = 'facetwp-is-loading'

const getDefaultLabel = select => {
	const firstOption = select?.options?.[0]

	return firstOption?.text || 'Select language'
}

const getIconContent = value => {
	const normalizedValue = normalizeIconValue(value)

	if (!normalizedValue) {
		return null
	}

	return ICONS_BY_NAME[normalizedValue] || null
}

const renderOptionContent = (value, label) => {
	const icon = getIconContent(value)
	const optionLabel = label || value || ''
	const iconUrl = icon?.url || icon?.src || ''
	const iconAlt = icon?.name || icon?.alt || optionLabel
	return `${iconUrl ? `<img src="${iconUrl}" alt="${iconAlt}">` : ''}<span>${optionLabel}</span>`
}

// Loading FacetWP
const initFacetWpState = () => {
	document.addEventListener('facetwp-loaded', () => {
		const dateFacet = document.querySelector('.facetwp-facet-date_time')

		if (!dateFacet) {
			document.body.classList.remove(BODY_LOADING_CLASS)
			return
		}

		const minInput = dateFacet.querySelector('.facetwp-date-min.fdate-alt-input')
		const maxInput = dateFacet.querySelector('.facetwp-date-max.fdate-alt-input')

		if (minInput) {
			minInput.placeholder = 'From date'
		}

		if (maxInput) {
			maxInput.placeholder = 'To date'
		}

		document.body.classList.remove(BODY_LOADING_CLASS)
	})

	document.addEventListener('facetwp-refresh', () => {
		if (!window.FWP?.loaded) {
			return
		}

		document.body.classList.add(BODY_LOADING_CLASS)
	})
}

// Custom select for language
const initLanguageSelect = () => {
	document.querySelectorAll('.lang-select').forEach(select => {
		const wrapper = select.closest('.lang-wrap')

		if (!wrapper || wrapper.querySelector('.lang-custom')) {
			return
		}

		const custom = document.createElement('div')
		custom.className = 'lang-custom'

		const current = document.createElement('div')
		current.className = 'lang-current'

		const label = getDefaultLabel(select)
		const selectedOption = select.selectedOptions?.[0]
		current.innerHTML = selectedOption?.value
			? renderOptionContent(selectedOption.value, selectedOption.text)
			: label

		const options = document.createElement('div')
		options.className = 'lang-options'

		Array.from(select.options).forEach(option => {
			if (!option.value) {
				return
			}

			const item = document.createElement('div')
			item.className = 'lang-option'
			item.dataset.value = option.value
			item.innerHTML = renderOptionContent(option.value, option.text)

			item.addEventListener('click', () => {
				select.value = option.value
				current.innerHTML = renderOptionContent(option.value, option.text)
				custom.classList.remove('is-open')
			})

			options.appendChild(item)
		})

		current.addEventListener('click', event => {
			event.stopPropagation()
			custom.classList.toggle('is-open')
		})

		custom.appendChild(current)
		custom.appendChild(options)
		wrapper.appendChild(custom)
	})

	document.addEventListener('click', event => {
		document.querySelectorAll('.lang-custom.is-open').forEach(custom => {
			if (!custom.contains(event.target)) {
				custom.classList.remove('is-open')
			}
		})
	})
}

// Custom select
const initDefaultSelect = () => {
	document.querySelectorAll('.default-select').forEach(select => {
		const wrapper = select.closest('.default-select-wrap')

		if (!wrapper || wrapper.querySelector('.default-select-custom')) {
			return
		}

		const custom = document.createElement('div')
		custom.className = 'default-select-custom'

		const current = document.createElement('div')
		current.className = 'default-select-current'

		const label = getDefaultLabel(select)
		const selectedOption = select.selectedOptions?.[0]
		current.innerHTML = selectedOption?.value
			? selectedOption.text
			: label

		const options = document.createElement('div')
		options.className = 'default-select-options'

		Array.from(select.options).forEach(option => {
			if (!option.value) {
				return
			}

			const item = document.createElement('div')
			item.className = 'default-select-option'
			item.dataset.value = option.value
			item.innerHTML = option.text

			item.addEventListener('click', () => {
				select.value = option.value
				current.innerHTML = option.text
				custom.classList.remove('is-open')
			})

			options.appendChild(item)
		})

		current.addEventListener('click', event => {
			event.stopPropagation()
			custom.classList.toggle('is-open')
		})

		custom.appendChild(current)
		custom.appendChild(options)
		wrapper.appendChild(custom)
	})

	document.addEventListener('click', event => {
		document.querySelectorAll('.lang-custom.is-open').forEach(custom => {
			if (!custom.contains(event.target)) {
				custom.classList.remove('is-open')
			}
		})
	})
}

const initShowPicker = () => {
	if (
		typeof HTMLInputElement === 'undefined' ||
		typeof HTMLInputElement.prototype.showPicker !== 'function'
	) {
		return
	}

	document.addEventListener(
		'click',
		event => {
			const input = event.target.closest?.('input[type="date"]')

			if (!input) {
				return
			}

			input.showPicker()
		},
		true
	)
}

const initCommon = () => {
	initFacetWpState()
	initLanguageSelect()
	initDefaultSelect()
	initShowPicker()

}

export default initCommon
