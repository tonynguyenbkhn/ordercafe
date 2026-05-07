import './scss/frontend.scss'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'swiper/css/grid'

import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";

import init from 'lib/init-blocks'

document.addEventListener('DOMContentLoaded', () => {
    Fancybox.bind("[data-fancybox]", {});

    init({
        block: 'blocks'
    }).mount()
})
