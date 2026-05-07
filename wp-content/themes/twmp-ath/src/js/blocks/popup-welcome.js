import Modal from 'lib/modal'
import { trigger } from 'lib/dom'

const STORAGE_KEY = 'twmp-ath-popup-welcome-last-visit'
const SHOW_AGAIN_AFTER = 7 * 24 * 60 * 60 * 1000

// const getLastVisit = () => {
// 	try {
// 		const value = window.localStorage.getItem(STORAGE_KEY)
// 		const timestamp = Number(value)

// 		return Number.isFinite(timestamp) && timestamp > 0 ? timestamp : 0
// 	} catch (error) {
// 		return 0
// 	}
// }

// const setLastVisit = timestamp => {
// 	try {
// 		window.localStorage.setItem(STORAGE_KEY, String(timestamp))
// 	} catch (error) {
// 	}
// }

export default el => {
	// const lastVisit = getLastVisit()
	// const now = Date.now()
	// const shouldShow = !lastVisit || now - lastVisit >= SHOW_AGAIN_AFTER

	// setLastVisit(now)

	// Modal(el, {
	// 	id: 'modal-popup-welcome'
	// })

	// el.addEventListener('activate', () => {
	// 	setLastVisit(Date.now())
	// })

	if (1 > 0) { // shouldShow
		trigger('activate', el)
	}
}
