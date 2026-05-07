import Swiper from 'swiper'
import { Navigation, Pagination } from 'swiper/modules'

const parseSettings = el => {
	const rawSettings = el.getAttribute('data-settings')

	if (!rawSettings) {
		return {}
	}

	try {
		return JSON.parse(rawSettings)
	} catch (error) {
		console.warn('event block: invalid swiper settings', error)
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
	const sliderWrap = swiperEl.closest('.class-section__slider-wrap')
	const nextEl = sliderWrap ? sliderWrap.querySelector('.swiper-button-next') : el.querySelector('.swiper-button-next')
	const prevEl = sliderWrap ? sliderWrap.querySelector('.swiper-button-prev') : el.querySelector('.swiper-button-prev')
	const paginationEl = swiperEl.querySelector('.swiper-pagination')
	const pagination = settings.pagination === false
		? false
		: {
			el: paginationEl,
			type: 'progressbar',
			...(settings.pagination && typeof settings.pagination === 'object' ? settings.pagination : {}),
		}
	const navigation = {
		...(nextEl ? { nextEl } : {}),
		...(prevEl ? { prevEl } : {}),
		...(settings.navigation && typeof settings.navigation === 'object' ? settings.navigation : {}),
	}

	return new Swiper(swiperEl, {
		modules: [Navigation, Pagination],

		slidesPerView: 2.15,
		spaceBetween: 32,

		loop: false,
		centeredSlides: false,

		slidesOffsetBefore: 0,
		// slidesOffsetAfter: 160,

		...settings,
		navigation,
		pagination,
	})
}
