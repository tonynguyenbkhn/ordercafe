<?php

if (!defined('ABSPATH')) {
    exit;
}

if (have_rows('sections')) {
    $base_fields = [
        'id' => 'section_id',
        'title' => 'title',
        'description' => 'description',
        'items' => 'items',
    ];

    while (have_rows('sections')) : the_row();

        $layout = get_row_layout();

        $data = twmp_resolve_flexible_layout_data($layout, $base_fields);

        if (empty($data)) {
            continue;
        }

        twmp_render_flexible_layout($layout, $data);
    endwhile;
}
