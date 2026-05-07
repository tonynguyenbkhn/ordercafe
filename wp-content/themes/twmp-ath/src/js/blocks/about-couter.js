

import {
	select,
	selectAll,
} from 'lib/dom'

const getCounterTarget = value => {
	const text = (value || '').trim()
	const target = parseInt(text.replace(/[^\d]/g, ''), 10)

	return Number.isNaN(target) ? 0 : target
}

const animateCount = (el, target, prefix = '') => {
	if (!el || target <= 0) {
		return
	}

	const duration = 2000
	const startTime = performance.now()

	const updateCounter = currentTime => {
		const elapsed = currentTime - startTime
		const progress = Math.min(elapsed / duration, 1)
		const currentValue = Math.round(target * progress)

		el.textContent = `${prefix}${currentValue}`

		if (progress < 1) {
			window.requestAnimationFrame(updateCounter)
			return
		}

		el.textContent = `${prefix}${target}`
	}

	window.requestAnimationFrame(updateCounter)
}

export default el => {
	const statsSection =
		el && el.classList && el.classList.contains('about-us__stats')
			? el
			: select('.about-us__stats', el)
	const values = selectAll('.about-us__stat-value', el)

	if (!statsSection || !values.length || typeof IntersectionObserver === 'undefined') {
		return
	}

console.log('About Counter block loaded')

	let hasRun = false

	const observer = new IntersectionObserver(
		entries => {
			entries.forEach(entry => {
				if (!entry.isIntersecting || hasRun) {
					return
				}

				hasRun = true

				values.forEach(valueEl => {
					const text = valueEl.textContent || ''
					const prefix = text.trim().charAt(0) === '+' ? '+' : ''
					const target = getCounterTarget(text)

					animateCount(valueEl, target, prefix)
				})

				observer.unobserve(statsSection)
			})
		},
		{
			threshold: 0.5,
		}
	)

	observer.observe(statsSection)
}
