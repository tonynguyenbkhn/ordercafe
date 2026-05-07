import {
	select,
	on,
	toggleClass,
} from 'lib/dom'

export default el => {
	const btnShowToggleContent = select('.js-btn-toggle-content', el)
	const wrapperContent = select('.js-content-toggle', el)

	if (btnShowToggleContent) {
		on(
			'click',
			e => {
				e.preventDefault()
				if (wrapperContent) {
					toggleClass('is-expanded', wrapperContent)
				}
			},
			btnShowToggleContent
		)
	}
}
