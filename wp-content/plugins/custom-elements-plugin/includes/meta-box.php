<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add meta box for hook selection
add_action( 'add_meta_boxes', 'custom_elements_add_meta_box' );

function custom_elements_add_meta_box() {
    add_meta_box(
        'custom_elements_hook',
        'Hook Settings',
        'custom_elements_meta_box_callback',
        'custom_element',
        'side'
    );
}

function custom_elements_meta_box_callback( $post ) {
    wp_nonce_field( 'custom_elements_save_meta', 'custom_elements_nonce' );
    $hook = get_post_meta( $post->ID, '_custom_elements_hook', true );
    ?>
    <label for="custom_elements_hook">Hook Name:</label>
    <input type="text" id="custom_elements_hook" name="custom_elements_hook" value="<?php echo esc_attr( $hook ); ?>" style="width: 100%;" />
    <p>Enter the name of the WordPress hook (e.g., <code>wp_footer</code>, <code>generate_after_footer</code>).</p>
    <?php
}

// Save hook data
add_action( 'save_post', 'custom_elements_save_meta_box_data' );

function custom_elements_save_meta_box_data( $post_id ) {
    if (
        ! isset( $_POST['custom_elements_nonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['custom_elements_nonce'] ) ),
            'custom_elements_save_meta'
        )
    ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['custom_elements_hook'] ) ) {
        $hook = sanitize_text_field( wp_unslash( $_POST['custom_elements_hook'] ) );
        update_post_meta( $post_id, '_custom_elements_hook', $hook );
    }
}