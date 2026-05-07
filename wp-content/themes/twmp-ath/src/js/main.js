import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

import init from './lib/init-blocks'

document.addEventListener('DOMContentLoaded', () => {
    init({
        block: 'blocks'
    }).mount()
})
