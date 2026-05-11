<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    'hero-banner' => [
        'template' => 'templates/sections/hero-banner/section',
        'extra_fields' => [
            'enable_container' => false,
        ],
    ],
    'product-cat-grid' => [
        'template' => 'templates/sections/product-cat-grid/section',
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'page-title' => [
        'template' => 'templates/sections/page-title/section',
        'fields' => [
            'show_breadcrumbs',
        ],
    ],
    'team' => [
        'template' => 'templates/sections/team/section',
        'fields' => [
            'button_text',
            'button_link',
            'artists',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'team-02' => [
        'template' => 'templates/sections/team-02/section',
        'fields' => [
            'button_text',
            'button_link',
            'artists',
            'sub_description'
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'show-event' => [
        'template' => 'templates/sections/show-event/section',
        'fields' => [
            'button_text',
            'button_link',
            'products',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'fs-class' => [
        'template' => 'templates/sections/fs-class/section',
        'fields' => [
            'button_text',
            'button_link',
            'products',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'fc-class-workshop' => [
        'template' => 'templates/sections/fc-class-workshop/section',
        'fields' => [
            'button_text',
            'button_link',
            'products',
        ],
        'extra_fields' => [
            'enable_container' => false,
        ],
    ],
    'class-workshop' => [
        'template' => 'templates/sections/class-workshop/section',
        'fields' => [
            'button_text',
            'button_link',
            'products',
        ],
        'extra_fields' => [
            'enable_container' => false,
        ],
    ],
    'about-us' => [
        'template' => 'templates/sections/about-us/section',
        'field_map' => [
            'image_id' => 'image'
        ],
        'fields' => [
            'counters',
            'primary_button_text',
            'primary_button_link',
            'secondary_button_text',
            'secondary_button_link',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'two-up-intro' => [
        'template' => 'templates/sections/two-up-intro/section',
        'field_map' => [
            'image_id' => 'image'
        ],
        'fields' => [
            'primary_button_text',
            'primary_button_link',
            'secondary_button_text',
            'secondary_button_link',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'text-infor' => [
        'template' => 'templates/sections/text-infor/section',
        'fields' => [
            'text-1',
            'text-2',
            'primary_button_text',
            'primary_button_link',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'text-infor-02' => [
        'template' => 'templates/sections/text-infor-02/section',
        'fields' => [
            'items',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'for-school' => [
        'template' => 'templates/sections/for-school/section',
        'field_map' => [
            'image_id' => 'image'
        ],
        'fields' => [
            'primary_button_text',
            'primary_button_link',
            'secondary_button_text',
            'secondary_button_link',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'for-company' => [
        'template' => 'templates/sections/for-company/section',
        'field_map' => [
            'image_id' => 'image'
        ],
        'fields' => [
            'primary_button_text',
            'primary_button_link',
            'secondary_button_text',
            'secondary_button_link',
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ],
    'logo-slider' => [
        'template' => 'templates/sections/logo-slider/section',
        'fields' => [
            'title',
            'description',
            'gallery',
        ],
        'extra_fields' => [
            'enable_container' => false,
        ],
    ],
    'testimonials' => [
        'template' => 'templates/sections/testimonials/section',
        'extra_fields' => [
            'enable_container' => false,
        ],
    ],
    'contact-us' => [
        'template' => 'templates/sections/contact-us/section',
        'field_map' => [
            'background_image_id' => 'background_image',
        ],
        'fields' => [
            'shortcode'
        ],
        'extra_fields' => [
            'enable_container' => true,
        ],
    ]
];
