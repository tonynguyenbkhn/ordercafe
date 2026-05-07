import { on, select, addClass, removeClass, scrollTop } from 'lib/dom'
import { throttle } from 'lib/utils'

const headerEl = select('[data-block="site-header-mega-menu"]')
const ACTIVE_EL_CLASS = 'back-to-top--visible'

export default el => {
	on(
		'click',
		() => {
			scrollTop(0)
		},
		el
	)

	on(
		'scroll',
		throttle(function () {
			const scrollTop = window.pageYOffset || document.body.scrollTop
			const offset = headerEl ? headerEl.offsetHeight + 20 : 0
			if (scrollTop > offset) {
				addClass(ACTIVE_EL_CLASS, el)
			} else {
				removeClass(ACTIVE_EL_CLASS, el)
			}
		}, 100),
		window
	)
}
