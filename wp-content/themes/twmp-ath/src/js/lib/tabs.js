import {
	on,
	selectAll,
	setAttribute,
	trigger,
	addClass,
	removeClass,
	hasClass
} from 'lib/dom'

export default (el, customOptions = {}) => {
	const defaultOptions = {
		tabNavSelector: '[role="tab"]',
		tabPanelSelector: '[role="tabpanel"]',
		activeNavClass: 'is-active',
		activePanelClass: 'is-active',
		lazyload: true,
		lazyloadCallback: function () { }
	}

	const options = { ...defaultOptions, ...customOptions }
	const navItems = selectAll(options.tabNavSelector, el)
	const panels = selectAll(options.tabPanelSelector, el)

	let currentIndex = 0 // ✅ lưu trạng thái tab hiện tại

	const checkTabPanelLoad = tabPanel => {
		const contextEls = tabPanel.getElementsByTagName('noscript')
		if (!contextEls || !contextEls.length) return false
		const content = contextEls[0].textContent || contextEls[0].innerHTML
		tabPanel.innerHTML = content
	}

	on(
		'update',
		e => {
			const previousIndex = currentIndex     // ✅ lưu lại index cũ
			currentIndex = e.detail.currentIndex   // ✅ cập nhật index mới

			navItems.forEach(item => removeClass('prev-active', item))
			panels.forEach(item => removeClass('prev-active', item))
			if (previousIndex !== currentIndex) {
				addClass('prev-active', navItems[previousIndex])
				addClass('prev-active', panels[previousIndex])
			}

			for (let index = 0; index < navItems.length; index++) {
				if (index === currentIndex) {
					setAttribute('aria-selected', 'true', navItems[index])
					addClass(options.activeNavClass, navItems[index])

					setAttribute('aria-expanded', 'true', panels[index])
					addClass(options.activePanelClass, panels[index])

					if (options.lazyload) {
						checkTabPanelLoad(panels[index])
						if (typeof options.lazyloadCallback === 'function') {
							options.lazyloadCallback(navItems[index], panels[index])
						}
					}
				} else {
					setAttribute('aria-selected', 'false', navItems[index])
					removeClass(options.activeNavClass, navItems[index])

					setAttribute('aria-expanded', 'false', panels[index])
					removeClass(options.activePanelClass, panels[index])
				}
			}
		},
		el
	)

	on(
		'click',
		e => {
			const navItem = e.target
			const index = navItems.indexOf(navItem)

			if (index !== -1 && index !== currentIndex) {
				trigger(
					{
						event: 'update',
						data: {
							currentIndex: index
						}
					},
					el
				)
			}
		},
		navItems
	)

	// ✅ khởi tạo tab đầu tiên
	trigger(
		{
			event: 'update',
			data: {
				currentIndex: currentIndex
			}
		},
		el
	)
}
