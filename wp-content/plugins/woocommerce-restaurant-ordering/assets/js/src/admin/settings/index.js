( function( $, window, document, _ ) {
	'use strict';

	$( function() {
		// Setup toggle-able settings.
		$( '.form-table .toggle' ).each( function() {
			let $parent = $( this ),
				$children = $parent.closest( '.form-table' ).find( '.' + $parent.data( 'toggleClass' ) ).closest( 'tr' );

			if ( $children.length ) {
				$parent.on( 'change', function() {
					toggleChildSettings( $parent, $children );
				} ).trigger( 'change' );
			}
		} );

		// Restaurant order page.
		$( '#wro_menu_page' ).selectWoo( {
			allowClear: true,
			minimumResultsForSearch: 10
		} );

		// Menu categories.
		let $categories = $( '#wro_categories' );

		let $categorySelection = $categories
			.selectWoo( {
				dropdownCssClass: 'restaurant-categories-dropdown',
				minimumResultsForSearch: 10
			} )
			.siblings( '.select2' )
			.find( '.select2-selection__rendered' );

		// Make menu categories sortable.
		$categorySelection
			.sortable( {
				axis: 'y',
				update: function() {
					reorderMultiSelect( $categories, $( this ) );
				}
			} );

		// Trigger reordering on select2 events.
		$categories
			.on( 'select2:select select2:unselect', function() {
				reorderMultiSelect( $categories, $categorySelection );
			} );

		// Opening hours.
		$( '.setting-opening-hours' ).on( 'click', 'a.change-periods', onChangeOpeningPeriod );
		openingHoursTimepicker( $( '.form-table' ) );
	} );

	function toggleChildSettings( $parent, $children ) {
		let showChildren = false,
			toggleValue = $parent.data( 'toggleValue' );

		if ( 'radio' === $parent.attr( 'type' ) ) {
			showChildren = $parent.prop( 'checked' ) && toggleValue == $parent.val();
		} else if ( 'checkbox' === $parent.attr( 'type' ) ) {
			if ( typeof toggleValue === 'undefined' || 1 == toggleValue ) {
				showChildren = $parent.prop( 'checked' );
			} else {
				showChildren = ! $parent.prop( 'checked' );
			}
		} else {
			showChildren = ( toggleValue == $parent.val() );
		}

		$children.toggle( showChildren );
	}

	// Re-order options in a <select> based on the current order in the select2 container.
	function reorderMultiSelect( $multiSelect, $selection ) {
		$selection.children( 'li[title]' ).get().reverse().forEach( function( obj ) {
			let $option = $multiSelect.children( 'option' ).filter( function() {
				return $( this ).html() === _.escape( obj.title );
			} );

			$option.detach();
			$multiSelect.prepend( $option );
		} );
	}

	function openingHoursTimepicker( $el ) {
		if ( $.fn.timepicker ) {
			$el.find( '.opening-hours-time' ).timepicker( {
				minTime: '7:00am',
				step: 15
			} );
		}
	}

	function onChangeOpeningPeriod( event ) {
		let $link = $( this ),
			$table = $link.closest( 'table' ),
			action = $link.data( 'action' ),
			$changePeriodsAction = $table.find( '.change-periods-action' );

		if ( 'add' === action ) {
			let openingPeriodTemplate = wp.template( 'wc-restaurant-opening-period' );

			if ( ! openingPeriodTemplate ) {
				return false;
			}

			$table.find( '.opening-hours-day' ).each( function() {
				let $period = $( openingPeriodTemplate( {
					id: $table.prop( 'id' ),
					day: $( this ).data( 'day' ),
					period: 2
				} ) );

				$( this ).children( '[data-period="1"]' ).after( $period );
				openingHoursTimepicker( $period );
			} );

			$table.addClass( 'additional-periods' );

			$changePeriodsAction.attr( 'colspan', 3 );
			$changePeriodsAction.children( '.add-more' ).hide();
			$changePeriodsAction.children( '.use-less' ).show();
		} else if ( 'remove' === action ) {
			$changePeriodsAction.attr( 'colspan', 2 );
			$changePeriodsAction.children( '.add-more' ).show();
			$changePeriodsAction.children( '.use-less' ).hide();

			$table.find( '[data-period="2"]' ).remove();
			$table.removeClass( 'additional-periods' );
		}

		return false;
	}

} )( jQuery, window, document, _ );
