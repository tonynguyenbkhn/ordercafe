const ACTIVE_CLASS = 'is-active'
const MENU_SELECTOR = '#primary-menu li.has-mega-menu'
const CLOSE_DELAY = 500

const initMegaMenu = () => {
	const menuItems = Array.from(document.querySelectorAll(MENU_SELECTOR))

	if (!menuItems.length) {
		return
	}

	let closeTimeoutId = null

	const closeAllSubMenus = () => {
		document
			.querySelectorAll(`${MENU_SELECTOR}.${ACTIVE_CLASS}`)
			.forEach(menuItem => menuItem.classList.remove(ACTIVE_CLASS))
	}

	const openSubMenu = menuItem => {
		if (!menuItem) {
			return
		}

		if (closeTimeoutId) {
			window.clearTimeout(closeTimeoutId)
			closeTimeoutId = null
		}

		closeAllSubMenus()
		menuItem.classList.add(ACTIVE_CLASS)
	}

	const closeSubMenu = menuItem => {
		if (!menuItem) {
			return
		}

		closeTimeoutId = window.setTimeout(() => {
			menuItem.classList.remove(ACTIVE_CLASS)
			closeTimeoutId = null
		}, CLOSE_DELAY)
	}

	menuItems.forEach(menuItem => {
		menuItem.addEventListener('mouseenter', () => {
			openSubMenu(menuItem)
		})

		menuItem.addEventListener('mouseleave', () => {
			closeSubMenu(menuItem)
		})
	})
}

export default initMegaMenu
