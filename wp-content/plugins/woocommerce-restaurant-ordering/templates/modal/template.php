<?php
defined( 'ABSPATH' ) || exit;
?>
<script type="text/template" id="tmpl-wc-restaurant-product-modal">
	<div class="wc-restaurant-product-modal wc-restaurant-modal" data-product-id="{{ data.product_id }}" id="wro-product-modal" aria-hidden="false">
		<form method="post" class="{{ data.form_class}}" {{{ data.form_data }}} id="cart-{{ data.product_id }}">
			<?php echo $content; ?>
			<input type="hidden" name="product_id" value="{{ data.product_id }}" />
		</form>
		<button type="button" class="modal-close" aria-expanded="true" aria-controls="wro-product-modal"><svg width="24px" height="24px" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="presentation" aria-hidden="true" focusable="false"><path d="m19.5831 6.24931-1.8333-1.83329-5.75 5.83328-5.75-5.83328-1.8333 1.83329 5.8333 5.74999-5.8333 5.75 1.8333 1.8333 5.75-5.8333 5.75 5.8333 1.8333-1.8333-5.8333-5.75z" fill="#000000"></path></svg></button>
	</div>
	<div class="wc-restaurant-modal-backdrop modal-close"></div>
</script>