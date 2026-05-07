<?php

if (!defined('ABSPATH')) {
    exit;
}

$data = wp_parse_args(
    $args,
    [
        'class' => '',
        'content' => '',
    ]
);

$content = is_string($data['content']) ? trim($data['content']) : '';

if ('' === $content) {
    return;
}

$_class = 'text-infor__column';
$_class .= ! empty($data['class']) ? esc_attr(' ' . $data['class']) : '';
?>

<div class="<?php echo esc_attr($_class); ?>">
    <div class="text-infor__column-inner">
        <?php echo wp_kses_post($content); ?>
    </div>
</div>
