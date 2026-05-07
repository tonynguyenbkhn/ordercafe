import Swiper from 'swiper'
import { Autoplay, FreeMode } from 'swiper/modules'

const parseSettings = el => {
    const rawSettings = el.getAttribute('data-settings')

    if (!rawSettings) {
        return {}
    }

    try {
        return JSON.parse(rawSettings)
    } catch (error) {
        console.warn('logo slider block: invalid swiper settings', error)
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

    console.log(settings);

    const slidesPerView = settings.slidesPerView ?? 2.2
    const spaceBetween = settings.spaceBetween ?? 24
    const autoplaySettings = settings.autoplay && typeof settings.autoplay === 'object'
        ? settings.autoplay
        : false

    return new Swiper(swiperEl, {
        // modules: [Autoplay, FreeMode],
        slidesPerView,
        spaceBetween,
        // speed: 3500,
        // loop: true,
        // allowTouchMove: true,
        centeredSlides: false,
        // freeMode: {
        // 	enabled: true,
        // 	momentum: false,
        // },
        // autoplay: autoplaySettings || {
        // 	delay: 0,
        // 	disableOnInteraction: false,
        // 	pauseOnMouseEnter: false,
        // },
        ...settings,
    })
}
