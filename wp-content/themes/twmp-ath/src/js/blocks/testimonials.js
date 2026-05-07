import Swiper from 'swiper'
import { Pagination } from 'swiper/modules'

const parseSettings = el => {
	const rawSettings = el.getAttribute('data-settings')

	if (!rawSettings) {
		return {}
	}

	try {
		return JSON.parse(rawSettings)
	} catch (error) {
		console.warn('testimonials block: invalid swiper settings', error)
		return {}
	}
}

export default el => {
	if (!el) {
		return null
	}

	const swiperEl = el.classList.contains('js-swiper') ? el : el.querySelector('.js-swiper')

	if (!swiperEl) {
		return null
	}

	const settings = parseSettings(swiperEl)

	return new Swiper(swiperEl, {
		modules: [Pagination],
		loop: false,
		centeredSlides: true,
		slidesPerView: 1.05,
		spaceBetween: 24,
		pagination: {
			el: swiperEl.querySelector('.swiper-pagination'),
			type: 'progressbar',
			clickable: false,
		},
		...settings,
	})
}
