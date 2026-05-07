import Modal from 'lib/modal'
import { select, setStyle } from 'lib/dom'

export default el => {
	const mainMenu = select('.header__main .header__nav .main-menu');
	const toggleIconSearch = select('.header__main .header__actions .header__menu-icons__icon');

	el.addEventListener('activate', () => {
		if (mainMenu && toggleIconSearch) {
			setStyle('opacity', '0', mainMenu)
			setStyle('opacity', '0', toggleIconSearch)
		}
	})

	el.addEventListener('deactivate', () => {
		if (mainMenu && toggleIconSearch) {
			setStyle('opacity', '1', mainMenu)
			setStyle('opacity', '1', toggleIconSearch)
		}
	})

	Modal(el, {
		id: 'modal-search-form'
	})
}
