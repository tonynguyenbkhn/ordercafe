( function( $, window, document, undefined ) {
	'use strict';

	let params = window.wc_restaurant_ordering_params || {};

	if ( $.isEmptyObject( params ) ) {
		console.warn( 'WooCommerce Restaurant Ordering - script params not defined' );
		return;
	}

	/**
	 * Adjust a product quantity based on the min, max and step attributes.
	 *
	 * @param current The current quantity value.
	 * @param min The minimum quantity
	 * @param max The maximum quantity
	 * @param step The step value
	 * @param add Whether to add to or remove from the quantity. Default: true (add)
	 * @returns {number}
	 */
	let adjustQuantity = function( current, min, max, step, add ) {
		// 'add' defaults to true. add = true => increase quantity. add = false => decrease quantity
		if ( false !== add ) {
			add = true;
		}

		// Sanitize quantity properties.
		min = parseFloat( min );
		max = parseFloat( max );
		step = parseFloat( step );

		min = ! isNaN( min ) ? min : 1;
		max = ! isNaN( max ) ? max : -1;
		step = ! isNaN( step ) ? step : 1;

		// Ensure max is >= min.
		if ( max > 0 && max < min ) {
			max = min;
		}

		let quantity = parseFloat( current );

		if ( isNaN( quantity ) ) {
			// If current quantity is invalid, just set to the minimum.
			quantity = min;
		} else if ( add ) {
			// Add quantity.
			quantity += step;

			if ( max > 0 ) {
				quantity = window.Math.min( quantity, max );
			}
		} else {
			// Remove quantity.
			quantity -= step;
			quantity = window.Math.max( quantity, min );
		}

		return quantity;
	};

	let getOffsetTop = function() {
		let offsetTop = 0;

		if ( 'nav_scroll_offset' in params && Number( params.nav_scroll_offset ) ) {
			// Use custom scroll offset if passed in script params.
			offsetTop = params.nav_scroll_offset;
		}

		// Now check for fixed WP admin bar.
		let $adminBar = $( '#wpadminbar' );

		if ( $adminBar.length && 'fixed' === $adminBar.css( 'position' ) ) {
			offsetTop += $( 'html' ).offset().top;
		}

		return Number( offsetTop );
	};

	let makeSticky = function( $el ) {
		if ( ! $el.length ) {
			return;
		}

		let elementOffset = $el.offset(),
			elementWidth = $el.outerWidth(),
			offsetTop = getOffsetTop();

		// Run on every scroll event.
		$( window ).scroll( function() {
			sticky( $el, elementOffset, elementWidth, offsetTop );
		} );

		// Run now in case $el should be sticky on page load.
		sticky( $el, elementOffset, elementWidth, offsetTop );
	};

	// Decides weather the element should be sticky.
	let sticky = function( $el, elementOffset, elementWidth, offsetTop ) {
		// Get the current vertical position from the top.
		let scrollTop = $( window ).scrollTop(),
			offsetVisible = elementOffset.top - offsetTop;

		// If we've scrolled passed than the element, change it to sticky, otherwise reset to default.
		if ( scrollTop > offsetVisible ) {
			// Set the height of the parent so it occupies the correct amount of space when $el is sticky.
			$el.parent().height( $el.parent().height() );

			$el.addClass( 'sticky' );
			$el.css( {
				'left': 'auto',
				'top': offsetTop,
				'width': elementWidth
			} );
		} else {
			$el.removeClass( 'sticky' );
			$el.parent().height( 'auto' );
		}
	};

	/**
	 * Update fragments after add to cart events.
	 */
	let updateFragments = function( fragments ) {
		if ( fragments ) {
			$.each( fragments, function( key ) {
				$( key )
					.addClass( 'updating' )
					.fadeTo( '400', '0.6' )
					.block( {
						message: null,
						overlayCSS: {
							opacity: 0.6
						}
					} );
			} );

			$.each( fragments, function( key, value ) {
				$( key ).replaceWith( value );
				$( key ).stop( true ).css( 'opacity', '1' ).unblock();
			} );

			$( document.body )
				.trigger( 'wro:update_fragments' )
				.trigger( 'wc_fragments_loaded' );
		}
	};

	let updateRestNonce = function( xhr ) {
		// Check for refreshed nonce returned from REST API and update our global param.
		const nonce = xhr.getResponseHeader( 'X-WP-Nonce' );

		if ( nonce ) {
			params.rest_nonce = nonce;
		}
	};

	let ProductModal = ( function() {

		const TEMPLATE_ID = 'wc-restaurant-product-modal';

		let currentModal = null,
			$htmlBody = $( 'html, body' );

		function Modal( productId ) {
			if ( productId && 'object' === typeof productId ) {
				this.productData = productId;
				this.productId = parseInt( this.productData.product_id, 10 );
			} else {
				this.productId = parseInt( productId, 10 );
			}

			this.$modal = [];
			this.$cart = [];
			this.$quantity = [];
			this.$price = [];
			this.$addonsTotal = [];

			if ( ! this.productId ) {
				if ( window.console ) {
					window.console.warn( 'Invalid product ID provided for product modal.' );
				}
				return false;
			}
		}

		Modal.prototype.load = function() {
			if ( ! $.isEmptyObject( this.productData ) ) {
				this.open();
				return;
			}

			let xhr = $.ajax( {
				url: params.rest_url + params.rest_endpoints.modal + '/' + this.productId,
				method: 'GET',
				dataType: 'json',
				cache: true,
				context: this,
				headers: {
					'X-WP-Nonce': params.rest_nonce
				}
			} )
				.done( function( response, textStatus, xhr ) {
					// Check for updated REST nonce.
					updateRestNonce( xhr );

					if ( response.success ) {
						$( document.body ).trigger( 'wro:modal:load', response );

						this.productData = response.product_data;
						this.open();
					} else {
						RestaurantMenu.showCartNotice( response );
					}
				} )
				.fail( function( xhr, textStatus, errorThrown ) {
					if ( console.error ) {
						console.error( xhr.responseText );
					}
					RestaurantMenu.showCartNotice( {
						error_message: params.messages.item_cannot_be_loaded
					} );
				} );

			return xhr;
		};

		Modal.prototype.open = function() {
			if ( $.isEmptyObject( this.productData ) ) {
				return;
			}

			// Close current modal first if there is one.
			close();

			$( '<div/>' ).WCBackboneModal( {
				template: TEMPLATE_ID,
				variable: this.productData
			} );

			this.init();
			this.$modal.trigger( 'wro:modal:open', this.productData.product_id );
		};

		Modal.prototype.init = function() {
			this.$modal = getModalElement();

			if ( ! this.$modal.length ) {
				return;
			}

			this.$cart = this.$modal.find( '.cart' );

			if ( this.$cart.length ) {
				this.$quantity = this.$cart.find( 'input.qty' );
				this.$price = this.$cart.find( '#product-total-' + this.productId );
				this.$addonsTotal = this.$cart.find( '#product-addons-total' );
			}

			this.bindEvents();
			this.initPlugins();
			this.updatePrice();
			this.initVariations();

			this.$modal.trigger( 'wro:modal:init' );
		};

		Modal.prototype.bindEvents = function() {
			this.$modal
				.off( '.restaurantOrder' )
				.on( 'wro:modal:open', onOpen )
				.on( 'wro:modal:close', onClose )
				.on( 'click.restaurantOrder', '.quantity .add', { modal: this }, onAddQuantity )
				.on( 'click.restaurantOrder', '.quantity .remove', { modal: this }, onRemoveQuantity )
				.on( 'change.restaurantOrder', '.quantity .qty', { modal: this }, onChangeQuantity )
				.on( 'wro:modal:update_price wro:modal:change_quantity', { modal: this }, onUpdatePrice )
				.on( 'updated_addons.restaurantOrder', '.cart', { modal: this }, onUpdateAddons )
				.on( 'wro:modal:before_add_product', { modal: this }, onBlock )
				.on( 'wro:modal:add_product_complete', { modal: this }, onUnblock )
				.on( 'submit.restaurantOrder', '.cart', { modal: this }, onAddToOrder );

			$( document )
				.on( 'wc_backbone_modal_before_remove.restaurantOrder', onBackboneClose );

			if ( this.hasProductAddons() ) {
				$( document )
					.ajaxSend( onAjaxSend )
					.ajaxComplete( onAjaxComplete );
			}
		};

		Modal.prototype.initPlugins = function() {
			let $modal = this.$modal;

			// WooCommerce Product Addons.
			this.initAddons();

			// WooCommerce Bundled Products.
			if ( $.fn.wc_pb_bundle_form ) {
				$modal.find( '.bundle_form .bundle_data' ).each( function() {

					var $bundle_data = $( this ),
						$composite_form = $bundle_data.closest( '.composite_form' );

					// If part of a composite, let the composite initialize it.
					if ( $composite_form.length === 0 ) {
						$bundle_data.wc_pb_bundle_form();
					}
				} );
			}

			// WooCommerce Composite Products.
			if ( $.fn.wc_composite_form ) {
				$modal.find( '.composite_form .composite_data' ).each( function() {
					$( this ).wc_composite_form();
				} );
			}
		};

		Modal.prototype.initAddons = function() {
			let $modal = this.$modal,
				$addonsContainer = $modal.find( '.wc-pao-addons-container' );

			if ( ! $addonsContainer.length ) {
				return;
			}

			// We initialise addons by triggering the 'quick-view-displayed' event.
			$modal.trigger( 'quick-view-displayed' );

			// Required checkbox logic - copied from Product Addons as there's no way to initialise it after page load in addons.js.
			$addonsContainer.find( '.wc-pao-addon-checkbox-group-required' )
				.each( function() {
					let checkboxesGroup = this;

					/*
					 * Require at least one checkbox in a required group to be checked.
					 * If at least one is checked then remove the required attribute from all of the group checkboxes.
					 * With all of the required attributes removed the form can be submitted even if some of the checkboxes are un-checked.
					 *
					 * This requires HTML5 to work.
					 */
					$( checkboxesGroup )
						.find( '.wc-pao-addon-checkbox' )
						.change( function() {
							if ( $( checkboxesGroup ).find( 'input:checked' ).length > 0 ) {
								$( checkboxesGroup ).removeClass(
									'wc-pao-addon-checkbox-required-error'
								);
								$( checkboxesGroup )
									.find( 'input' )
									.each( function() {
										$( this ).attr( 'required', false );
									} );
							} else {
								$( checkboxesGroup ).addClass(
									'wc-pao-addon-checkbox-required-error'
								);
								$( checkboxesGroup )
									.find( 'input' )
									.each( function() {
										$( this ).attr( 'required', true );
									} );
							}
						} );
				} );
		};

		Modal.prototype.initVariations = function() {
			if ( ! $.fn.wc_variation_form ) {
				return;
			}

			let modal = this;

			// Initialise variations form.
			modal.$modal.find( 'form.variations_form' )
				.first()
				.wc_variation_form()
				.on( 'reset_data', function() {
					modal.resetVariations();
				} )
				.on( 'found_variation', function( event, variation ) {
					modal.setVariation( variation );
				} );
		};

		Modal.prototype.getQuantity = function() {
			if ( ! this.$quantity.length ) {
				return 0;
			}

			let quantity = parseFloat( this.$quantity.val() );
			quantity = ! isNaN( quantity ) ? quantity : 0;
			return quantity;
		};

		Modal.prototype.setQuantity = function( quantity ) {
			if ( ! this.$quantity.length ) {
				return;
			}

			this.$quantity.val( quantity ).trigger( 'change' );
		};

		Modal.prototype.addRemoveQuantity = function( add ) {
			add = !! add;

			if ( ! this.$quantity.length ) {
				return;
			}

			let quantity = adjustQuantity(
				this.$quantity.val(),
				this.$quantity.prop( 'min' ),
				this.$quantity.prop( 'max' ),
				this.$quantity.prop( 'step' ),
				add
			);

			this.setQuantity( quantity );
		};

		Modal.prototype.updatePrice = function() {
			// Bail if no price element found.
			if ( ! this.$price.length ) {
				return;
			}

			let quantity = this.getQuantity(),
				itemPrice = parseFloat( this.$price.data( 'itemPrice' ) ),
				extrasPerItem = this.$price.data( 'extrasPerItem' ),
				extras = this.$price.data( 'extras' );

			if ( isNaN( itemPrice ) ) {
				return;
			}

			if ( ! extrasPerItem ) {
				extrasPerItem = 0;
			}

			if ( ! extras ) {
				extras = 0;
			}

			this.$price.html( formatPrice( extras + ( quantity * ( itemPrice + extrasPerItem ) ) ) );
		};

		Modal.prototype.setPrice = function( price ) {
			if ( this.$price.length ) {
				this.$price.data( 'itemPrice', price ).trigger( 'wro:modal:update_price' );
			}
		};

		Modal.prototype.setExtras = function( extras ) {
			if ( this.$price.length ) {
				this.$price.data( 'extras', extras ).trigger( 'wro:modal:update_price' );
			}
		};

		Modal.prototype.setExtrasPerItem = function( extrasPerItem ) {
			if ( this.$price.length ) {
				 this.$price.data( 'extrasPerItem', extras ).trigger( 'wro:modal:update_price' );
			}
	 	};

		Modal.prototype.resetVariations = function() {
			// Disable add to cart button.
			this.$cart.find( '.order button, .order .qty' ).prop( 'disabled', true ).addClass( 'disabled' );

			let $variationIdInput = this.$cart.find( 'input[name="variation_id"]' );

			// Ensure 'variation_id' input is not blank - it must be 0 or a valid product ID, otherwise it breaks the product addons subtotal.
			if ( $variationIdInput.length && '' === $variationIdInput.val() ) {
				$variationIdInput.val( '0' );
			}

			this.resetVariationData();
		};

		Modal.prototype.setVariation = function( variation ) {
			this.resetVariationData();

			if ( variation.is_purchasable && variation.is_in_stock && variation.variation_is_visible ) {
				this.$cart.find( '.order button, .order .qty' ).prop( 'disabled', false ).removeClass( 'disabled' );

				if ( this.$quantity.length ) {
					if ( 'min_qty' in variation ) {
						this.$quantity.prop( 'min', variation.min_qty );
					}

					if ( 'max_qty' in variation ) {
						this.$quantity.prop( 'max', variation.max_qty );
					}
				}

				this.setPrice( variation.display_price );

				let $appendDataTo = this.$cart.find( '.options .variations-data' );

				if ( variation.variation_description ) {
					$appendDataTo.append( variation.variation_description );
				}

				if ( params.show_stock_in_modal && variation.availability_html ) {
					$appendDataTo.append( variation.availability_html );
				}
			} else {
				this.$cart.find( '.order button, .order .qty' ).prop( 'disabled', true ).addClass( 'disabled' );

				if ( ! variation.is_in_stock ) {
					this.showOrderError( params.messages.item_out_of_stock );
				} else {
					this.showOrderError( params.messages.item_not_available );
				}
			}
		};

		Modal.prototype.resetVariationData = function() {
			this.$cart.find( '.options .variations-data' ).empty();
			this.hideOrderError();
		};

		Modal.prototype.showOrderError = function( message ) {
			this.hideOrderError( true );
			$( '<p class="order-error">' + message + '</p>' ).prependTo( this.$cart.find( '.order' ) ).slideDown( 150 );
		};

		Modal.prototype.hideOrderError = function( instant ) {
			let transitionTime = ( instant && true === instant ) ? 0 : 150;

			this.$cart.find( '.order > .order-error' ).slideUp( transitionTime, function() {
				this.remove();
			} );
		};

		Modal.prototype.hasProductAddons = function() {
			return this.$addonsTotal.length && 'undefined' !== typeof woocommerce_addons_params;
		};

		Modal.prototype.block = function() {
			if ( this.$cart.length ) {
				this.$cart.find( '.buy' ).addClass( 'block' );
			}
		};

		Modal.prototype.unblock = function() {
			if ( this.$cart.length ) {
				this.$cart.find( '.buy' ).removeClass( 'block' );
			}
		};

		function close( $modal ) {
			$modal = $modal || getModalElement();

			if ( ! $modal.length ) {
				return;
			}

			// We can't access the Backbone View to call view.remove(), so we simulate a ESC key press to close the modal.
			let keyPress = $.Event( 'keydown' );
			keyPress.which = 27; // ESC key
			$modal.trigger( keyPress );
		}

		function formatPrice( amount ) {
			if ( 'function' !== typeof window.accounting.formatMoney ) {
				return amount;
			}

			return window.accounting.formatMoney( amount, {
				symbol: params.price_currency_symbol,
				decimal: params.price_decimal_sep,
				thousand: params.price_thousand_sep,
				precision: params.price_num_decimals,
				format: params.price_currency_format
			} );
		}

		function getCurrentModal() {
			return currentModal;
		}

		function getFormData( $form ) {
			var data = {};

			$.each( $form.serializeArray(), function( index, item ) {
				if ( item.name.indexOf( '[]' ) !== -1 ) {
					item.name = item.name.replace( '[]', '' );
					data[item.name] = $.makeArray( data[item.name] );
					data[item.name].push( item.value );
				} else {
					data[item.name] = item.value;
				}
			} );

			return data;
		}

		function getModalElement() {
			return $( document.body ).children( '#wc-backbone-modal-dialog' );
		}

		function load( productId ) {
			currentModal = new Modal( productId );
			return currentModal.load();
		}

		function show( productData ) {
			currentModal = new Modal( productData );
			currentModal.open();
		}

		function setPrice( price ) {
			if ( currentModal ) {
				currentModal.setPrice( price );
			}
		}

		function setExtras( extras ) {
			if ( currentModal ) {
				currentModal.setExtras( extras );
			}
		}

		function setExtrasPerItem( extrasPerItem ) {
			if ( currentModal ) {
				currentModal.setExtrasPerItem( extrasPerItem );
			}
		}

		function validateOrder( $cartForm ) {
			let formData = getFormData( $cartForm );

			let result = {
				'isValid': false,
				'message': false
			};

			if ( $.isEmptyObject( formData ) || ! ( 'product_id' in formData ) ) {
				result.message = params.messages.error_adding_item;
				return result;
			}

			// Check quantity is valid.
			if ( ! ( 'quantity' in formData ) ) {
				formData.quantity = 1;
			}

			if ( ! formData.quantity ) {
				result.message = params.messages.enter_quantity_greater_than_0
				return result;
			}

			// Check variation has been selected.
			if ( 'variation_id' in formData && ( 0 === parseInt( formData.variation_id, 10 ) ) ) {
				result.message = params.messages.select_all_required_options;
				return result;
			}

			// Validate required options/addons.
			$( 'select, input, textarea', $cartForm ).each( function() {
				let name = $( this ).prop( 'name' );

				// Strip [] from input name if present, to match properties in formData.
				if ( -1 !== name.indexOf( '[]' ) ) {
					name = name.replace( '[]', '' );
				}

				if ( $( this ).prop( 'required' ) ) {
					if( $(this).closest( '.wpo-options-container' ).length ) {
						return;
					}
					
					if ( ! ( name in formData ) || '' === formData[name] ) {
						result.message = params.messages.select_all_required_options;
						return result;
					}
				}
			} );

			if ( ! result.message ) {
				result.isValid = true;
			}

			result.formData = formData;
			return result;
		}

		// EVENTS

		function onAddQuantity( event ) {
			event.data.modal.addRemoveQuantity( true );
			event.data.modal.$modal.trigger( 'wro:modal:add_quantity' );
		}

		function onRemoveQuantity( event ) {
			event.data.modal.addRemoveQuantity( false );
			event.data.modal.$modal.trigger( 'wro:modal:remove_quantity' );
		}

		function onChangeQuantity( event ) {
			// Skip if product has Product Addons as 'onAddonsUpdated' will handle it.
			if ( event.data.modal.hasProductAddons() ) {
				return true;
			}

			event.data.modal.$modal.trigger( 'wro:modal:change_quantity' );
		}

		function onUpdateAddons( event ) {
			let modal = event.data.modal,
				addonsData = modal.$addonsTotal.data( 'price_data' ),
				$price = modal.$price,
				addonsTotal = 0;

			if ( ! Array.isArray( addonsData ) || ! $price.length ) {
				return true;
			}

			if ( addonsData.length ) {
				addonsTotal = addonsData.reduce( function( runningTotal, addon ) {
					if ( ! ( 'cost_raw' in addon ) ) {
						return runningTotal;
					}

					let addonCost = parseFloat( addon.cost_raw );
					return ! isNaN( addonCost ) ? ( runningTotal + addonCost ) : runningTotal;
				}, addonsTotal );
			}

			modal.setExtras( addonsTotal );
		}

		function onUpdatePrice( event ) {
			event.data.modal.updatePrice();
		}

		function onAddToOrder( event ) {
			let $cartForm = $( event.target ),
				modal = event.data.modal,
				$modal = modal.$modal;

			modal.hideOrderError( true );
			let formValidation = validateOrder( $cartForm );

			if ( ! formValidation.isValid ) {
				modal.showOrderError( formValidation.message );
				return false;
			}

			$modal.trigger( 'wro:modal:before_add_product', [formValidation.formData] );

			$.ajax( {
				url: params.rest_url + params.rest_endpoints.cart,
				method: 'POST',
				data: formValidation.formData,
				dataType: 'json',
				headers: {
					'X-WP-Nonce': params.rest_nonce
				}
			} )
				.done( function( response, status, xhr ) {
					// Check for updated REST nonce.
					updateRestNonce( xhr );

					// Triggering wro:add_product event will display success message and refresh cart widget.
					$modal.trigger( 'wro:add_product', [response] );
					$modal.trigger( 'wro:modal:add_product', [response] );
				} )
				.always( function() {
					$modal.trigger( 'wro:modal:add_product_complete' );
					close( $modal );
				} )
				.fail( function( xhr, textStatus, errorThrown ) {
					if ( console.error ) {
						console.error( xhr.responseText );
					}
					$modal.trigger( 'wro:add_product_fail' ).trigger( 'wro:modal:add_product_fail' );
				} );

			return false;
		}

		function onBackboneClose( event, target ) {
			if ( TEMPLATE_ID !== target ) {
				return true;
			}

			let $modal = getModalElement();

			if ( ! $modal.length ) {
				return true;
			}

			$modal.trigger( 'wro:modal:close', $modal.find( '#wro-product-modal' ).attr( 'data-product-id' ) );
		}

		function onOpen( event, productId ) {
			const $button = $( ".wc-restaurant-menu-product[data-product-id=" + productId + "] button.add" );
			$button.attr( 'aria-expanded', 'true' );

			// Some themes set overflow on <html> so we need to remove this while modal is open.
			$htmlBody.addClass( 'wc-restaurant-modal-active' );	

			// Remove the block class from the add button
			let $addButton = $( ".quantity-plus.block" );
			if( $addButton.length === 0 ) {
				$addButton = $( ".details .price.block" );
			}
			if( $addButton.length ) {
				$addButton.removeClass( 'block' );
			}
		}

		function onClose( event, productId ) {
			const $button = $( ".wc-restaurant-menu-product[data-product-id=" + productId + "] button.add" );
			$button.attr( 'aria-expanded', 'false' );
			$button.focus();
			
			// Reset <html> overflow on close.
			$htmlBody.removeClass( 'wc-restaurant-modal-active' );
			
		}

		function onBlock( event ) {
			event.data.modal.block();
		}

		function onUnblock( event ) {
			event.data.modal.unblock();
		}

		function onAjaxSend( event, jqxhr, settings ) {
			// We need to block the modal price if Product Addons does an Ajax request to recalculate totals and taxes.
			// The only way to do this is to check the data.action passed in Ajax object.
			if ( currentModal && 'data' in settings && settings.data.indexOf( 'action=wc_product_addons_calculate_tax' ) >= 0 ) {
				currentModal.block();
			}
		}

		function onAjaxComplete( event, jqxhr, settings ) {
			if ( currentModal ) {
				currentModal.unblock();
			}
		}

		// Return product modal API.
		return {
			load: load,
			show: show,
			close: close,
			getCurrentModal: getCurrentModal,
			getModalElement: getModalElement,
			setExtras: setExtras,
			setExtrasPerItem: setExtrasPerItem,
			setPrice: setPrice
		};

	} )();

	let RestaurantMenu = ( function() {
		let cartNoticeTemplate = null,
			$sections = [];

		function init() {
			$sections = $( '.wc-restaurant-menu-section' );

			bindEvents();
			initCartNotice();
		}

		function bindEvents() {
			$sections
				.off( '.restaurantOrder' )
				.on( 'click.restaurantOrder', '.clickable .wc-restaurant-menu-product', onAddProduct )
				.on( 'keydown', '.wc-restaurant-menu-product', onPressButton )
				.on( 'click.restaurantOrder', '.wc-restaurant-menu-product .add', onAddProduct );

			$( document.body )
				.on( 'wro:add_product', onProductAdded )
				.on( 'wro:add_product_fail', onAddProductFail )
				.on( 'updated_cart_totals', onUpdatedCartTotals )
				.on( 'removed_from_cart', onRemovedFromCart )
				.on( 'updated_checkout', onUpdatedCartTotals )

			if ( ! wc_restaurant_ordering_params.products_quantity_init_sync ) {
				// refresh restautrent menu items quantity
				refreshRestaurentMenuQuantities()			
			}
		}

		function initCartNotice() {
			cartNoticeTemplate = wp.template( 'wc-restaurant-cart-notice' );
		}

		function showCartNotice( data, timeout ) {
			if ( ! cartNoticeTemplate ) {
				return;
			}

			data = $.extend( { success: false, error_message: '' }, data );
			timeout = timeout || ( 'cart_notice_timeout' in params ? params.cart_notice_timeout : 2800 );

			let $notice = $( cartNoticeTemplate( data ) );
			$notice.appendTo( document.body ).slideDown();

			// Delete all the loading spinners
			let $blocked = $( ".quantity-plus.block" )
			if( $blocked.length === 0 ) {
				$blocked = $( ".details .price.block" )
			}
			if( $blocked.length && $blocked.hasClass( "block" ) ) {
				$blocked.removeClass( "block" );
			}
			setTimeout( function() {
				$notice.fadeOut( function() {
					this.remove();
				} );
			}, timeout );
		}

		function checkOrderType( productId ) {
			productId = parseInt( productId, 10 );

			$( document.body ).trigger( 'wro:before_check_order_type', productId );

			let xhr = $.ajax( {
				url: params.rest_url + params.rest_endpoints.order_type + '/' + productId,
				method: 'GET',
				dataType: 'json',
				cache: true,
				headers: {
					'X-WP-Nonce': params.rest_nonce
				}
			} )
				.done( function( response, textStatus, xhr ) {
					// Check for updated REST nonce.
					updateRestNonce( xhr );

					$( document.body ).trigger( 'wro:check_order_type', [response] );
				} )
				.always( function() {
					$( document.body ).trigger( 'wro:check_order_type_complete' );
				} )
				.fail( function( xhr, textStatus, errorThrown ) {
					if ( console.error ) {
						console.error( xhr.responseText );
					}
					$( document.body ).trigger( 'wro:check_order_type_fail' );
				} );

			return xhr;
		}

		function quickAddProduct( productId, quantity, buyButtonStyle, action = 'add' ) {
			productId = parseInt( productId, 10 );
			quantity = parseFloat( quantity );

			// We must have a product ID and a quantity greater than 0 to add or remove.
			if ( ! productId || ! quantity ) {
				return false;
			}

			const cartData = {
				'product_id': productId,
				'quantity': quantity,
				'buy_button_style': buyButtonStyle,
				'action' : action
			};

			$( document.body ).trigger( 'wro:before_add_product', [cartData] );

			let xhr = $.ajax( {
				url: params.rest_url + params.rest_endpoints.cart,
				method: 'POST',
				data: cartData,
				dataType: 'json',
				headers: {
					'X-WP-Nonce': params.rest_nonce
				}
			} )
				.done( function( response, status, xhr ) {
					// Check for updated REST nonce.
					updateRestNonce( xhr );

					// Trigger add/remove events.
					$( document.body )
						.trigger( `wro:add_product`, response )
						.trigger( `wro:quick_add_product`, response );
				} )
				.always( function() {
					$( document.body ).trigger( 'wro:add_product_complete' ).trigger( 'wro:modal:add_product_complete' );
				} )
				.fail( function( xhr, textStatus, errorThrown ) {
					if ( console.error ) {
						console.error( xhr.responseText );
					}
					$( document.body ).trigger( 'wro:add_product_fail' );
				} );

			return xhr;
		}

		function block( $el ) {
			$el.addClass( 'block' );
		}

		function unblock( $el ) {
			$el.removeClass( 'block' );
		}

		function onPressButton ( event ) {
			if( event.keyCode === 13 ) {
				event.preventDefault();
				if( $( event.target ).parent().hasClass( 'clickable' ) ){
					$( event.target ).find( '.details' ).click();
				} else {
					$( event.target ).click();
				}
			}
		}

		function onAddProduct( event ) {
			let $target = $( event.target ),
				$product = $target.parents( '.wc-restaurant-menu-product' ),
				productId = $product.data( 'productId' ) || 0,
				orderType = $product.data( 'orderType' ) || false,
				quantity = $product.data( 'quantity' ) || 1,
				timeout = 500;
			
			let productsQuantityInCart = $product.find('button.add .quantity-qty').length ? parseInt( $product.find('button.add .quantity-qty').text() ) : 0;

			// Clear the timeout if it exists.
			clearTimeout(window.orderDebounceTimer);
			// set seft cart refresh starting add to product
			params.self_cart_refresh = true

			// Bail if we couldn't find a product ID.
			if ( ! productId ) {
				return true;
			}

			let $blocked = $product;

			if ( $target.closest('button').hasClass( 'add' ) ) {
				$blocked = $target.closest('.buy-button');
			} else {
				$blocked = $( '.price', $product );
			}

			let buyButtonStyle = $target.closest('.wc-restaurant-menu-products').hasClass('clickable') ? 'clickAnywhere' : 'buyButton';

			if ( 'check' === orderType ) {
				window.xhr = checkOrderType( productId )
					.done( function( response, status, xhr ) {
						if ( ! response.success ) {
							showCartNotice( response );
							return;
						}

						// The returned order_type should be 'quick' or 'lightbox'. Update the product data with this.
						$product.data( 'orderType', response.order_type );

						// If product can be quick added, do that, otherwise open the modal.
						if ( 'quick' === response.order_type && ! $target.hasClass('quantity-qty') ) {
							block( $blocked );
							window.xhr = quickAddProduct( productId, parseInt( productsQuantityInCart + quantity ), buyButtonStyle )
								
							window.xhr.done( function( response ) {
								unblock( $blocked );
							} );
						} else if ( 'product_data' in response ) {
							// Modal required, so load this with the returned product data.
							ProductModal.show( response.product_data );
						}
					} );
			} else if ( 'quick' === orderType ) {
				let buttonActionDiv = $target.closest('div');

				if ( buttonActionDiv.hasClass('quantity-minus') ) {
					let removeItemQuantity = parseInt(productsQuantityInCart - quantity) < 1 ? quantity :  parseInt(productsQuantityInCart - quantity),
						actionIntention = parseInt(productsQuantityInCart - quantity) < 1 ? 'remove' : 'add';

					// Quick update quantity on debounce method
					window.orderDebounceTimer = setTimeout( () => {
						block( $blocked );
						window.xhr = quickAddProduct( productId, removeItemQuantity, buyButtonStyle, actionIntention );

						window.xhr.done( function( response ) {
							if ( response.success && ! response.sold_individually && orderType !== 'lightbox') {
								quantityButtonsOnAddToCart( $target, productsQuantityInCart, $product, quantity )
							}
						} );
					}, timeout )
				} else if ( buttonActionDiv.hasClass('quantity-plus') ) {
					// Quick update quantity on debounce method
					window.orderDebounceTimer = setTimeout( () => {
						block( $blocked );
						window.xhr = quickAddProduct( productId, parseInt(productsQuantityInCart + quantity ), buyButtonStyle );

						window.xhr.done( function( response ) {
							if ( response.success && ! response.sold_individually && orderType !== 'lightbox') {
								quantityButtonsOnAddToCart( $target, productsQuantityInCart, $product, quantity )
							}
						} );
						 
						window.xhr.always( function() {
							unblock( $blocked )
						} );
						
					}, timeout )
				} else {
					block( $blocked );
					window.xhr = quickAddProduct( productId, parseInt(productsQuantityInCart + quantity ), buyButtonStyle );
					window.xhr.done( function( response ) {
						unblock( $blocked );
					} );
				}

			} else {
				// Otherwise must be lightbox. Prevent multiple clicks loading more than one modal.
				if ( $product.data( 'modal' ) ) {
					return false;
				}
				$product.data( 'modal', true );

				// Load lightbox for this product.
				$blocked = $(this).find( '.quantity-plus' )

				// When clicking anywhere on the item menu should open the modal
				if( $blocked.length === 0 ) {
					$blocked = $(this).find('.price');
				}

				block( $blocked )
				window.xhr = ProductModal.load( productId );
				// unblock( $blocked )
			}

			if ( window.xhr ) {
				window.xhr.always( function( response ) {
					if ( response.success && ! response.sold_individually && orderType !== 'lightbox') {
						quantityButtonsOnAddToCart( $target, productsQuantityInCart, $product, quantity )
					}

					$product.data( 'modal', false );
				} );
			}

			return false;
		}

		function quantityButtonsOnAddToCart( $target, productsQuantityInCart, $product, quantity ) {
			productsQuantityInCart = $target.closest('div').hasClass('quantity-minus') ? 
										productsQuantityInCart - quantity : 
										productsQuantityInCart + quantity;
			// Update products quantity in the button
			if ( ! $target.hasClass('quantity-qty') ) {
				$product.find('button.add .quantity-qty').text( productsQuantityInCart );
			}

			// Display or hide bin icon
			if ( productsQuantityInCart == 1 ) {
				$product.find('.buy-button .quantity-minus').addClass('show-bin-icon');
			} else {
				$product.find('.buy-button .quantity-minus').removeClass('show-bin-icon');
			}

			if ( productsQuantityInCart > 0 ) {
				if ( $product.data('order-type') === 'quick' ) {
					$product.find('.buy-button').addClass('added');
					$target.closest('.buy-button').animate({marginLeft: '64px'}, 200);
				}
			} else {
				$product.find('.buy-button').removeClass('added');
				$target.closest('.buy-button').animate({marginLeft: '8px'}, 300);
			}
		}

		function onProductAdded( event, response ) {
			// Always show cart errors, regardless of show_cart_notice param.
			if ( ! response.success ) {
				showCartNotice( response );
				return;
			}

			if ( params.show_cart_notice ) {
				showCartNotice( response );
			}

			if ( params.refresh_cart && 'fragments' in response ) {
				updateFragments( response.fragments );
			}

			// Remove block status from products on ajax done
			$( '.wc-restaurant-menu-product .buy-button' ).removeClass( 'block' );

			// Trigger WooCommerce added_to_cart event to support themes/plugins which listen for this event.
			$( document.body ).trigger( 'added_to_cart', [response.fragments, response.cart_hash] );
			// set self cart refresh to false after trigger added_to_cart event
			params.self_cart_refresh = false
		}

		/**
		 * On item removed from cart event handler.
		 */
		function onRemovedFromCart() {
			// Do not need to sync cart items on self actions
			if( params.self_cart_refresh ) {
				return;
			}
			// refresh restautrent menu items quantity
			refreshRestaurentMenuQuantities()
		}

		/**
		 * On item removed from cart event handler.
		 */
		function onUpdatedCartTotals() {
			// Do not need to sync cart items on self actions
			if( params.self_cart_refresh ) {
				return;
			}
			// refresh restautrent menu items quantity
			refreshRestaurentMenuQuantities()			
		}

		function onAddProductFail() {
			showCartNotice( { success: false } );
		}

		/**
		 * Refresh restaurant menu items quantity by making REST API call 
		 * and update the quantity on the page for the products that are in the cart
		 */
		function refreshRestaurentMenuQuantities() {
			// make the REST API call to get the cart products and their quantity
			const cart_quantity_rest_endpoint = 'wc-restaurant-ordering/v1/cart-quantity';
			$.ajax( {
				url: params.rest_url + cart_quantity_rest_endpoint,
				method: 'POST',
				dataType: 'json',
				headers: {
					'X-WP-Nonce': params.rest_nonce
				}
			} )
				.done( function( response, status, xhr ) {
					updateProductQuantity( response );
					// set init synt to true after first sync
					wc_restaurant_ordering_params.products_quantity_init_sync = true;
				} )
		}

		/**
		 * Update cart products quantity from the restaurent menu
		 * @param {array} products 
		 */
		function updateProductQuantity( products ) {
			// return if not on the restaurent menu page
			if ( ! jQuery('.wc-restaurant-menu-product').length ) {
				return;
			}
			// grab all divs with .wc-restaurant-menu-product class
			jQuery('.wc-restaurant-menu-product[data-order-type="check"], .wc-restaurant-menu-product[data-order-type="quick"]').each( function() {
				let productID = jQuery(this).data('product-id');
				let quantity = 0;
				let parentDiv = jQuery(this);

				products.map( item => {
					if ( item.product_id === productID ) {
						quantity = item.quantity;
					}
				})

				if ( parseInt( quantity ) > 0 ) {
					jQuery(parentDiv).find('.quantity-qty').text( quantity );
					jQuery(parentDiv).find('.buy-button').addClass('added').animate({marginLeft: '64px'}, 200);
				} else {
					jQuery(parentDiv).find('.quantity-qty').text( 0 );
					jQuery(parentDiv).find('.buy-button').removeClass('added').animate({marginLeft: '8px'}, 200);
				}
				if ( parseInt( quantity ) == 1 ) {
					jQuery(parentDiv).find('.quantity-minus').addClass( 'show-bin-icon' );
				}
			})
		}

		// Return restaurant menu API.
		return {
			init: init,
			addRemoveProduct: quickAddProduct,
			checkOrderType: checkOrderType,
			showCartNotice: showCartNotice
		};

	} )();

	let NavigationBar = ( function() {

		let $navBar = [],
			navs = [],
			scrollOffset = 0;

		function init() {
			$navBar = $( '.wc-restaurant-navigation' );

			if ( ! $navBar.length ) {
				return;
			}

			scrollOffset = getOffsetTop() + 12;

			$navBar.each( function() {
				navs.push( {
					'items': $( '.wc-restaurant-navigation-items', this ),
					'more': $( '.wc-restaurant-navigation-more', this ),
					'moreDropdown': $( '.more-dropdown', this )
				} );
			} );

			bindEvents();
			updateMoreDropdowns();
		}

		function bindEvents() {
			$navBar.on( 'click', 'a', onNavigationClick );
			$navBar.on( 'click', '.more-button', onMoreClick );

			$( window ).resize( updateMoreDropdowns );
		}

		function updateMoreDropdowns() {
			navs.forEach( nav => updateMoreDropdown( nav ) );
		}

		function updateMoreDropdown( nav ) {
			let offsetTop = nav.items.offset().top;
			nav.moreDropdown.empty();

			nav.items.children().each( function( i, li ) {
				let $li = $( li );

				// If the offset top is greater than the menu offset top, then it has wrapped onto the 2nd line
				// (i.e. not visible) so we need to add this link to the more dropdown.
				if ( $li.offset().top > offsetTop ) {
					nav.moreDropdown.append( $li.clone() );
				}
			} );

			// Show or hide the 'more' section.
			nav.more[nav.moreDropdown.children().length ? 'removeClass' : 'addClass']( 'hidden' );
		}

		function onMoreClick( event ) {
			$( this )
				.siblings( '.more-dropdown' )
				.slideToggle( 200 );

			return false;
		}

		function onNavigationClick( event ) {
			let $nav = $( this ).closest( '.wc-restaurant-navigation' ),
				$navItems = $( '.wc-restaurant-navigation-items', $nav ),
				$moreDropdown = $( '.more-dropdown', $nav );

			$navItems.children().removeClass( 'active' );
			$moreDropdown.children().removeClass( 'active' );
			$moreDropdown.slideUp( 200 );

			$( this ).parent( 'li' ).addClass( 'active' );

			let href = this.getAttribute( 'href' ),
				$anchor = $( href );

			// Scroll to the clicked category if the anchor exists on the page.
			if ( href && $anchor.length ) {
				// Calculate the scroll offset - we adjust the target ID's offset by subtracting any html offset plus a small adjustment.
				let offset = $anchor.first().offset().top - scrollOffset;
				$( 'html, body' ).animate( { scrollTop: offset }, 'slow' );
				return false;
			}

			return true;
		}

		return {
			init: init
		};

	} )();

	let RestaurantInfoModal = ( function() {

		const TEMPLATE_ID = 'wc-restaurant-info-modal';

		function init() {
			bindEvents();
		}

		function bindEvents() {
			$( '.wc-restaurant-info-more' ).on( 'click', 'button', openInfoModal );
		}

		function openInfoModal( event ) {
			// TODO: Use an empty div on page rather than creating one here.
			// See https://github.com/awkward/backbone.modal/blob/master/examples/1_single_view.html
			$( '<div/>' ).WCBackboneModal( {
				template: TEMPLATE_ID
			} );

			let $modal = $( document.body ).children( '#wc-backbone-modal-dialog' );
			
			if ( $modal.length ) {
				$( event.target ).attr( 'aria-expanded', 'true' );
				$modal.find( '#wro-info-copy-address' ).on( 'click', onCopyAddress );
				$( document ).on( 'wc_backbone_modal_before_remove', onRemoveModal );
			}

			return false;
		}

		function onCopyAddress() {
			let $copyButton = $( this ),
				$address = $copyButton.siblings( '.address-text' );

			if ( ! $address.length ) {
				return false;
			}

			let range = document.createRange();
			range.selectNode( $address.get( 0 ) );
			window.getSelection().addRange( range );

			try {
				// Now that we've selected the anchor text, execute the copy command
				let success = document.execCommand( 'copy' );

				if ( success ) {
					$( '<span class="address-copied">' + params.messages.address_copied + '</span>' )
						.insertAfter( $copyButton )
						.delay( 650 )
						.fadeOut( 400, function() {
							$( this ).remove();
						} );
				}
			} catch ( err ) {
				// Do nothing on copy error
			}

			// Remove the selections - NOTE: Use removeRange(range) when it is supported
			window.getSelection().removeAllRanges();
			return false;
		}

		function onRemoveModal() {
			const $button = $( '.wc-restaurant-info-menu button' );
			$button.attr( 'aria-expanded', 'false' );
		}

		return {
			init: init
		};

	} )();

	// Expose APIs on global object.
	window.wcRestaurantMenu = RestaurantMenu;
	window.wcRestaurantProductModal = ProductModal;

	// Initialize everything on document.ready
	$( function() {
		RestaurantMenu.init();
		NavigationBar.init();
		RestaurantInfoModal.init();
	} );

} )
( jQuery, window, document );
