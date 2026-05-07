<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args($args, [
    'description_class' => '',
    'class' => '',
    'description' => '',
]);

$_class = 'two-up-intro__description';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_description_class = 'two-up-intro__description-text';
$_description_class .= !empty($data['description_class']) ? esc_attr(' ' . $data['description_class']) : '';

?>

<?php if (!empty($data['description'])) : ?>
    <div class="<?php echo esc_attr($_class) ?>">
        <?php if (!empty($data['description'])) : ?>
            <div class="<?php echo esc_attr($_description_class); ?>">
                <?php echo wp_kses_post($data['description']); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>