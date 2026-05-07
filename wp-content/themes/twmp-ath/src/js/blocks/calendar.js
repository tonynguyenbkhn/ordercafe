import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import listPlugin from '@fullcalendar/list'
import multiMonthPlugin from '@fullcalendar/multimonth'
import Modal from 'lib/modal'
import { on, select, selectAll, trigger } from 'lib/dom'

const parseSettings = el => {
	const rawSettings = el.getAttribute('data-settings')

	if (!rawSettings) {
		return {}
	}

	try {
		return JSON.parse(rawSettings)
	} catch (error) {
		console.warn('calendar block: invalid settings', error)
		return {}
	}
}

const startOfDay = date => {
	const result = new Date(date)
	result.setHours(0, 0, 0, 0)
	return result
}

const endOfDay = date => {
	const result = new Date(date)
	result.setHours(23, 59, 59, 999)
	return result
}

const addDays = (date, days) => {
	const result = new Date(date)
	result.setDate(result.getDate() + days)
	return result
}

const addMonths = (date, months) => {
	const result = new Date(date)
	result.setMonth(result.getMonth() + months)
	return result
}

const formatMonthYear = date =>
	new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' }).format(date)

const formatShortMonth = date =>
	new Intl.DateTimeFormat('en', { month: 'short' }).format(date)

const formatSidebarTitle = date =>
	new Intl.DateTimeFormat('en', {
		weekday: 'long',
		day: '2-digit',
		month: 'short',
		year: 'numeric'
	}).format(date)

const getWeekStart = date => {
	const result = startOfDay(date)
	const day = result.getDay()
	const offset = day === 0 ? -6 : 1 - day
	return addDays(result, offset)
}

const getWeekEnd = date => endOfDay(addDays(getWeekStart(date), 6))

const getEventDay = event => {
	const start = event.start instanceof Date ? event.start : new Date(event.start)
	return startOfDay(start)
}

const getMonthWindow = (year, startMonth) => {
	const start = new Date(year, Math.max(0, startMonth - 1), 1)
	const end = endOfDay(addMonths(start, 5))
	return { start, end }
}

const getMeta = event => event.extendedProps || {}

const renderWeekRows = events => {
	const firstWeekStart = getWeekStart(events.length ? getEventDay(events[0]) : new Date())
	const lastWeekEnd = getWeekEnd(events.length ? getEventDay(events[events.length - 1]) : new Date())
	const weeks = []
	let cursor = firstWeekStart
	let weekIndex = 1

	while (cursor <= lastWeekEnd) {
		const weekStart = new Date(cursor)
		const weekEnd = getWeekEnd(cursor)
		const weekEvents = events.filter(event => {
			const eventDate = getEventDay(event)
			return eventDate >= weekStart && eventDate <= weekEnd
		})
		const visibleEvents = weekEvents.slice(0, 3)
		const moreCount = weekEvents.length - visibleEvents.length

		weeks.push({
			weekStart,
			weekEnd,
			html: `
				<div class="calendar-week" data-week-start="${weekStart.toISOString()}" data-week-end="${weekEnd.toISOString()}">
					<div class="calendar-week__head">
						<div class="calendar-week__label">Week ${weekIndex}</div>
						<div class="calendar-week__range">${new Intl.DateTimeFormat('en', { day: 'numeric' }).format(weekStart)}-${new Intl.DateTimeFormat('en', { day: 'numeric' }).format(weekEnd)}</div>
					</div>
					<div class="calendar-week__events">
						${visibleEvents.map(event => {
							const meta = getMeta(event)
							const title = event.title || ''
							const timeRange = meta.timeRange || ''
							const color = event.backgroundColor || '#F8B2A5'
                            const product_cat = Array.isArray(meta?.product_cat) ? meta.product_cat[0]?.slug || '' : '';

							return `
								<button type="button" class="calendar-event-card ${product_cat}" data-event-id="${event.id}" data-event-date="${event.start}">
									<span class="calendar-event-card__body" style="--event-color:${color};">
										<span class="calendar-event-card__title">${title}</span>
										<span class="calendar-event-card__meta">${timeRange}</span>
									</span>
								</button>
							`
						}).join('')}
						${moreCount > 0 ? `<div class="calendar-week__more">+${moreCount}</div>` : ''}
					</div>
				</div>
			`
		})

		cursor = addDays(cursor, 7)
		weekIndex += 1
	}

	return weeks.map(week => week.html).join('')
}

const renderSidebarContent = (sidebarTitleEl, sidebarContentEl, date, events, activeEventId) => {
	sidebarTitleEl.textContent = formatSidebarTitle(date)

	if (!events.length) {
		sidebarContentEl.innerHTML = `
			<div class="calendar-sidebar__empty">
				<p>No events for this date.</p>
			</div>
		`
		return
	}

	sidebarContentEl.innerHTML = events.map(event => {
		const meta = getMeta(event)
		const isActive = activeEventId && activeEventId === event.id
		const thumb = meta.thumbnailUrl || ''
		const title = event.title || ''
		const link = meta.permalink || event.url || '#'
		const timeRange = meta.timeRange || ''
		const details = [meta.ageDisplay, meta.language, meta.locationLabel].filter(Boolean).join(', ')

		return `
			<article class="calendar-sidebar__item ${isActive ? 'is-active' : ''}">
				<a class="calendar-sidebar__item-link" href="${link}">
					${thumb ? `<span class="calendar-sidebar__thumb"><img src="${thumb}" alt="${(meta.thumbnailAlt || title).replace(/"/g, '&quot;')}" loading="lazy"></span>` : ''}
					<span class="calendar-sidebar__item-body">
						<span class="calendar-sidebar__item-title">${title}</span>
						${timeRange ? `<span class="calendar-sidebar__item-time">${timeRange}</span>` : ''}
						${details ? `<span class="calendar-sidebar__item-meta">${details}</span>` : ''}
						${meta.shortInfo ? `<span class="calendar-sidebar__item-copy">${meta.shortInfo}</span>` : ''}
					</span>
					<span class="calendar-sidebar__item-arrow" aria-hidden="true">&rsaquo;</span>
				</a>
			</article>
		`
	}).join('')
}

export default el => {
	if (!el) {
		return null
	}

	const settings = parseSettings(el)
	const endpoint = settings.endpoint || ''
	const monthPanel = select('[data-calendar-panel="month"]', el)
	const yearPanel = select('[data-calendar-panel="year"]', el)
	const monthMount = select('[data-calendar-month]', el)
	const yearMount = select('[data-calendar-year]', el)
	const rangeLabel = select('[data-calendar-range]', el)
	const prevButton = select('[data-calendar-prev]', el)
	const nextButton = select('[data-calendar-next]', el)
	const modeButtons = selectAll('[data-calendar-mode]', el)
	const filterInputs = selectAll('[data-calendar-filter]', el)
	const sidebar = select('#calendar-sidebar')
	const sidebarTitle = select('[data-calendar-sidebar-title]', sidebar)
	const sidebarContent = select('[data-calendar-sidebar-content]', sidebar)
	const sidebarClose = select('[data-close-modal="calendar-sidebar"]', sidebar)
	let calendar = null
	let currentMode = settings.initialMode || 'year'
	let currentDate = new Date(settings.initialYear || new Date().getFullYear(), (settings.initialMonth || 1) - 1, 1)
	let yearWindowStartMonth = Number.isFinite(settings.initialWindowStartMonth) ? settings.initialWindowStartMonth : 1
	let activeFilters = {
		location: 'all',
		type: 'all',
		status: 'all'
	}
	let cachedEvents = []
	let loadingRequests = 0

	const isMonthMode = () => currentMode === 'month'
	const setClassState = (el, className, active) => {
		if (!el) {
			return
		}

		el.classList.toggle(className, !!active)
	}

	const setLoadingState = active => {
		loadingRequests = Math.max(0, loadingRequests + (active ? 1 : -1))
		const isLoading = loadingRequests > 0

		setClassState(el, 'is-loading', isLoading)
		el.setAttribute('aria-busy', isLoading ? 'true' : 'false')
	}

	const updateRootState = () => {
		setClassState(el, 'is-month-mode', isMonthMode())
		setClassState(el, 'is-year-mode', !isMonthMode())
		setClassState(monthPanel, 'is-active', isMonthMode())
		setClassState(yearPanel, 'is-active', !isMonthMode())

		modeButtons.forEach(button => {
			setClassState(button, 'is-active', button.getAttribute('data-calendar-mode') === currentMode)
		})
	}

	const updateRangeLabel = () => {
		if (!rangeLabel) {
			return
		}

		if (isMonthMode()) {
			rangeLabel.textContent = formatMonthYear(currentDate)
			return
		}

		const { start, end } = getMonthWindow(currentDate.getFullYear(), yearWindowStartMonth)
		rangeLabel.textContent = `${formatShortMonth(start)} - ${formatShortMonth(end)} ${end.getFullYear()}`
	}

	const openSidebar = (date, events, activeEventId = '') => {
		if (!sidebar) {
			return
		}

		renderSidebarContent(sidebarTitle, sidebarContent, date, events, activeEventId)
		trigger('activate', sidebar)
	}

	const closeSidebar = () => {
		if (!sidebar) {
			return
		}

		trigger('deactivate', sidebar)
	}

	const getFetchUrl = (start, end) => {
		const url = new URL(endpoint, window.location.origin)
		url.searchParams.set('start', start.toISOString())
		url.searchParams.set('end', end.toISOString())
		url.searchParams.set('view', currentMode)
		url.searchParams.set('year', String(currentDate.getFullYear()))
		url.searchParams.set('month', String(currentDate.getMonth() + 1))
		url.searchParams.set('start_month', String(yearWindowStartMonth))
		url.searchParams.set('location', activeFilters.location || 'all')
		url.searchParams.set('type', activeFilters.type || 'all')
		url.searchParams.set('status', activeFilters.status || 'all')
		return url
	}

	const fetchEvents = async (start, end) => {
		if (!endpoint) {
			return []
		}

		setLoadingState(true)

		try {
			const response = await fetch(getFetchUrl(start, end).toString(), {
				credentials: 'same-origin'
			})

			if (!response.ok) {
				throw new Error(`calendar block: request failed with ${response.status}`)
			}

			const payload = await response.json()
			cachedEvents = Array.isArray(payload.events) ? payload.events : []
			return cachedEvents
		} catch (error) {
			console.warn(error)
			cachedEvents = []
			return []
		} finally {
			setLoadingState(false)
		}
	}

	const filterEventsByDay = date => {
		const dayKey = date.toISOString().slice(0, 10)
		return cachedEvents.filter(event => {
			const meta = getMeta(event)
			return meta.dayKey === dayKey
		})
	}

	const getEventsInRange = (start, end) =>
		cachedEvents.filter(event => {
			const eventStart = new Date(event.start)
			return eventStart >= startOfDay(start) && eventStart <= endOfDay(end)
		})

	const renderMonthView = () => {
		if (!monthMount) {
			return
		}

		if (calendar) {
			calendar.destroy()
		}

		calendar = new Calendar(monthMount, {
			plugins: [dayGridPlugin, interactionPlugin, listPlugin, multiMonthPlugin],
			initialView: 'dayGridMonth',
			initialDate: currentDate,
			headerToolbar: false,
			height: 'auto',
			fixedWeekCount: false,
			showNonCurrentDates: true,
			dayMaxEvents: 4,
			navLinks: false,
			editable: false,
			selectable: false,
			eventSources: [
				async (fetchInfo, successCallback, failureCallback) => {
					try {
						const events = await fetchEvents(fetchInfo.start, fetchInfo.end)
						successCallback(events)
					} catch (error) {
						failureCallback(error)
					}
				}
			],
			dateClick: info => {
				openSidebar(info.date, filterEventsByDay(info.date))
			},
			eventClick: info => {
				info.jsEvent.preventDefault()
				const date = info.event.start || new Date()
				openSidebar(date, filterEventsByDay(date), info.event.id)
			},
			datesSet: info => {
				currentDate = new Date(info.view.calendar.getDate())
				updateRangeLabel()
			}
		})

		calendar.render()
	}

	const shiftYearWindow = direction => {
		if (direction > 0) {
			if (yearWindowStartMonth === 1) {
				yearWindowStartMonth = 7
				return
			}

			yearWindowStartMonth = 1
			currentDate = new Date(currentDate.getFullYear() + 1, 0, 1)
			return
		}

		if (yearWindowStartMonth === 7) {
			yearWindowStartMonth = 1
			return
		}

		yearWindowStartMonth = 7
		currentDate = new Date(currentDate.getFullYear() - 1, 0, 1)
	}

	const renderYearView = async () => {
		if (!yearMount) {
			return
		}

		const { start, end } = getMonthWindow(currentDate.getFullYear(), yearWindowStartMonth)
		const events = await fetchEvents(start, end)
		const months = []

		for (let i = 0; i < 6; i += 1) {
			const monthDate = new Date(start.getFullYear(), start.getMonth() + i, 1)
			const monthStart = startOfDay(monthDate)
			const monthEnd = endOfDay(new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0))
			const monthEvents = events.filter(event => {
				const eventDate = getEventDay(event)
				return eventDate >= monthStart && eventDate <= monthEnd
			})

			months.push(`
				<section class="calendar-month-card">
					<header class="calendar-month-card__header">
						<span class="calendar-month-card__month">${new Intl.DateTimeFormat('en', { month: 'long' }).format(monthDate)}</span>
					</header>
					<div class="calendar-month-card__weeks">
						${renderMonthWeeks(monthEvents, monthDate)}
					</div>
				</section>
			`)
		}

		yearMount.innerHTML = months.join('')
		updateRangeLabel()

		selectAll('.calendar-event-card', yearMount).forEach(button => {
			on('click', event => {
				event.preventDefault()
				const eventId = button.getAttribute('data-event-id')
				const eventDate = new Date(button.getAttribute('data-event-date'))
				const selected = cachedEvents.filter(item => item.id === eventId)
				openSidebar(eventDate, selected, eventId)
			}, button)
		})

		selectAll('.calendar-week', yearMount).forEach(weekEl => {
			on('click', event => {
				if (event.target.closest('.calendar-event-card')) {
					return
				}

				const weekStart = new Date(weekEl.getAttribute('data-week-start'))
				const weekEnd = new Date(weekEl.getAttribute('data-week-end'))
				openSidebar(weekStart, getEventsInRange(weekStart, weekEnd))
			}, weekEl)
		})
	}

	const renderMonthWeeks = (events, monthDate) => {
		const monthStart = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1)
		const monthEnd = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0)
		const firstWeekStart = getWeekStart(monthStart)
		const lastWeekEnd = getWeekEnd(monthEnd)
		let cursor = firstWeekStart
		let weekIndex = 1
		const rows = []

		while (cursor <= lastWeekEnd) {
			const weekStart = new Date(cursor)
			const weekEnd = getWeekEnd(cursor)
			const weekEvents = events.filter(event => {
				const eventDate = getEventDay(event)
				return eventDate >= weekStart && eventDate <= weekEnd
			})
			const visibleEvents = weekEvents.slice(0, 3)
			const moreCount = weekEvents.length - visibleEvents.length

			rows.push(`
				<div class="calendar-week" data-week-start="${weekStart.toISOString()}" data-week-end="${weekEnd.toISOString()}">
					<div class="calendar-week__head">
						<div class="calendar-week__label">Week ${weekIndex}</div>
						<div class="calendar-week__range">${new Intl.DateTimeFormat('en', { day: 'numeric' }).format(weekStart)}-${new Intl.DateTimeFormat('en', { day: 'numeric' }).format(weekEnd)}</div>
					</div>
					<div class="calendar-week__events">
						${visibleEvents.map(event => {
							const meta = getMeta(event)
							const title = event.title || ''
							const timeRange = meta.timeRange || ''
							const color = event.backgroundColor || '#F8B2A5'
                            const product_cat = Array.isArray(meta?.product_cat) ? meta.product_cat[0]?.slug || '' : '';
                            
							return `
								<button type="button" class="calendar-event-card ${product_cat}" data-event-id="${event.id}" data-event-date="${event.start}">
									<span class="calendar-event-card__body" style="--event-color:${color};">
										<span class="calendar-event-card__title">${title}</span>
										<span class="calendar-event-card__meta">${timeRange}</span>
									</span>
								</button>
							`
						}).join('')}
						${moreCount > 0 ? `<div class="calendar-week__more">+${moreCount}</div>` : ''}
					</div>
				</div>
			`)

			cursor = addDays(cursor, 7)
			weekIndex += 1
		}

		return rows.join('')
	}

	const applyFilters = async () => {
		if (isMonthMode()) {
			if (calendar) {
				calendar.refetchEvents()
			} else {
				renderMonthView()
			}

			return
		}

		await renderYearView()
	}

	const setMode = async mode => {
		currentMode = mode === 'month' ? 'month' : 'year'
		updateRootState()

		if (isMonthMode()) {
			renderMonthView()
		} else {
			await renderYearView()
		}

		updateRangeLabel()
	}

	const mount = () => {
		if (sidebar) {
			Modal(sidebar, {
				id: 'calendar-sidebar',
				openTriggers: [],
				closeTriggers: ['[data-close-modal="calendar-sidebar"]']
			})
		}

		on('deactivate', () => {
			if (sidebarClose) {
				sidebarClose.blur()
			}
		}, sidebar)

		modeButtons.forEach(button => {
			on('click', async event => {
				event.preventDefault()
				await setMode(button.getAttribute('data-calendar-mode'))
			}, button)
		})

		if (prevButton) {
			on('click', async event => {
				event.preventDefault()

				if (isMonthMode()) {
					currentDate = addMonths(currentDate, -1)
					if (calendar) {
						calendar.gotoDate(currentDate)
						calendar.updateSize()
					}
					await applyFilters()
				} else {
					shiftYearWindow(-1)
					await renderYearView()
				}

				updateRangeLabel()
			}, prevButton)
		}

		if (nextButton) {
			on('click', async event => {
				event.preventDefault()

				if (isMonthMode()) {
					currentDate = addMonths(currentDate, 1)
					if (calendar) {
						calendar.gotoDate(currentDate)
						calendar.updateSize()
					}
					await applyFilters()
				} else {
					shiftYearWindow(1)
					await renderYearView()
				}

				updateRangeLabel()
			}, nextButton)
		}

		filterInputs.forEach(input => {
			on('change', async event => {
				const target = event.target
				const key = target.getAttribute('data-calendar-filter')

				activeFilters = {
					...activeFilters,
					[key]: target.value || 'all'
				}

				await applyFilters()
			}, input)
		})

		updateRootState()
		updateRangeLabel()

		if (isMonthMode()) {
			renderMonthView()
		} else {
			renderYearView()
		}
	}

	mount()

	return {
		displayName: 'calendar',
		unmount() {
			if (calendar) {
				calendar.destroy()
			}

			if (sidebar) {
				closeSidebar()
			}
		}
	}
}
