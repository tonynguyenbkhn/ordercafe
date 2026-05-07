import Swiper from 'swiper'
import { Grid, Navigation, Pagination } from 'swiper/modules'

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
	const sliderWrap = swiperEl.closest('.team-02-section')
	const nextEl = sliderWrap
		? sliderWrap.querySelector('.swiper-button-next')
		: el.querySelector('.swiper-button-next')
	const prevEl = sliderWrap
		? sliderWrap.querySelector('.swiper-button-prev')
		: el.querySelector('.swiper-button-prev')

	const paginationEl = sliderWrap ? sliderWrap.querySelector('.swiper-pagination') : el.querySelector('.swiper-pagination')

	const paginationSettings = settings.pagination && typeof settings.pagination === 'object'
		? settings.pagination
		: {}
	const pagination = paginationEl
		? {
			el: paginationEl,
			type: 'progressbar',
			clickable: false,
			...paginationSettings,
		}
		: false
	const navigation = {
		...(nextEl ? { nextEl } : {}),
		...(prevEl ? { prevEl } : {}),
		...(settings.navigation && typeof settings.navigation === 'object' ? settings.navigation : {}),
	}

	return new Swiper(swiperEl, {
		modules: [Grid, Navigation, Pagination],
		slidesPerView: 1.15,
		spaceBetween: 24,
		...settings,
		navigation,
		pagination,
	})
}
