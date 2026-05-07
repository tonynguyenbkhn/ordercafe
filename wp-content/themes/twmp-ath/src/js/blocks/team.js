import Swiper from 'swiper'
import { Navigation } from 'swiper/modules'

const parseSettings = el => {
	const rawSettings = el.getAttribute('data-settings')

	if (!rawSettings) {
		return {}
	}

	try {
		return JSON.parse(rawSettings)
	} catch (error) {
		console.warn('team block: invalid swiper settings', error)
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
	const sliderWrap = swiperEl.closest('.team-section__slider-wrap')
	const nextEl = sliderWrap
		? sliderWrap.querySelector('.swiper-button-next')
		: el.querySelector('.swiper-button-next')
	const prevEl = sliderWrap
		? sliderWrap.querySelector('.swiper-button-prev')
		: el.querySelector('.swiper-button-prev')

	return new Swiper(swiperEl, {
		modules: [Navigation],
		slidesPerView: 1.25,
		spaceBetween: 50,
		loop: true,
		navigation: {
			nextEl,
			prevEl,
		},
		...settings,
	})
}
