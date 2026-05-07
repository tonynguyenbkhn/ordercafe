<?php

/**
 * Template Name: Calendar
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

$calendar_service = \TWMP_THEME\Inc\Calendar_Theme::get_instance();
$filter_options = $calendar_service->get_filter_options();
$current_year = (int) wp_date('Y');
$current_month = (int) wp_date('n');
$start_month = $current_month <= 6 ? 1 : 7;
$end_month = $start_month + 5;
$initial_title = sprintf('%s %d', wp_date('M'), $current_year);
$settings = [
    'endpoint' => esc_url_raw(rest_url('twmp/v1/calendar-events')),
    'initialYear' => $current_year,
    'initialMonth' => $current_month,
    'initialWindowStartMonth' => $start_month,
    'initialMode' => 'year',
    'filters' => $filter_options,
];

get_header();
?>

<div class="page page-calendar">
    <section
        class="calendar-page"
        data-block="calendar"
        data-settings="<?php echo esc_attr(wp_json_encode($settings)); ?>">
        <div class="container calendar-page__container">
            <nav class="calendar-page__breadcrumbs" aria-label="<?php echo esc_attr__('Breadcrumb', 'twmp-ath'); ?>">
                <a class="calendar-page__breadcrumb-link" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php echo twmp_get_svg_icon('home'); ?>
                    <span><?php echo esc_html__('Home', 'twmp-ath'); ?></span>
                </a>
                <span class="calendar-page__breadcrumb-separator">›</span>
                <span class="calendar-page__breadcrumb-current"><?php echo esc_html__('Calendar', 'twmp-ath'); ?></span>
            </nav>

            <header class="calendar-page__header">
                <h1 class="calendar-page__title"><?php echo esc_html(get_the_title() ?: __('Calendar', 'twmp-ath')); ?></h1>
                <div class="calendar-page__subhead">
                    <div class="calendar-page__navigator">
                        <button class="calendar-page__nav-button" type="button" data-calendar-prev aria-label="<?php echo esc_attr__('Previous period', 'twmp-ath'); ?>">
                            <?php echo twmp_get_svg_icon('arrow-right'); ?>
                        </button>
                        <div class="calendar-page__range" data-calendar-range><?php echo esc_html($initial_title); ?></div>
                        <button class="calendar-page__nav-button" type="button" data-calendar-next aria-label="<?php echo esc_attr__('Next period', 'twmp-ath'); ?>">
                            <?php echo twmp_get_svg_icon('arrow-right'); ?>
                        </button>
                    </div>

                    <div class="calendar-page__modes" role="tablist" aria-label="<?php echo esc_attr__('Calendar views', 'twmp-ath'); ?>">
                        <button class="calendar-page__mode-button is-active" type="button" data-calendar-mode="month">
                            <?php echo esc_html__('Month', 'twmp-ath'); ?>
                        </button>
                        <button class="calendar-page__mode-button" type="button" data-calendar-mode="year">
                            <?php echo esc_html__('Year', 'twmp-ath'); ?>
                        </button>
                    </div>

                    <div class="calendar-page__filters">
                        <label class="calendar-page__select-wrap">
                            <span class="screen-reader-text"><?php echo esc_html__('Location', 'twmp-ath'); ?></span>
                            <select class="calendar-page__select" data-calendar-filter="location">
                                <?php foreach ($filter_options['locations'] as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="calendar-page__select-wrap">
                            <span class="screen-reader-text"><?php echo esc_html__('Type', 'twmp-ath'); ?></span>
                            <select class="calendar-page__select" data-calendar-filter="type">
                                <?php foreach ($filter_options['types'] as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="calendar-page__legend" aria-hidden="true">
                    <span class="calendar-page__legend-item" style="--legend-color:#F8B2A5;"><?php echo esc_html__('Show/Event/Festival', 'twmp-ath'); ?></span>
                    <span class="calendar-page__legend-item" style="--legend-color:#B8E0AA;"><?php echo esc_html__('Class/Workshop', 'twmp-ath'); ?></span>
                </div>
            </header>

            <div class="calendar-page__layout">
                <div class="calendar-page__panel calendar-page__panel--month is-active" data-calendar-panel="month">
                    <div class="calendar-page__calendar-shell">
                        <div class="calendar-page__calendar" data-calendar-month></div>
                    </div>
                </div>

                <div class="calendar-page__panel calendar-page__panel--year" data-calendar-panel="year">
                    <div class="calendar-year-grid" data-calendar-year></div>
                </div>
            </div>
        </div>
    </section>

    <div
        class="modal modal--calendar"
        id="calendar-sidebar"
        role="dialog"
        aria-hidden="true">
        <div class="modal__wrapper">
            <div class="modal__header calendar-sidebar__header">
                <div class="calendar-sidebar__heading">
                    <span class="calendar-sidebar__eyebrow"><?php echo esc_html__('Selected date', 'twmp-ath'); ?></span>
                    <h2 class="modal__title calendar-sidebar__title" data-calendar-sidebar-title><?php echo esc_html__('Calendar', 'twmp-ath'); ?></h2>
                </div>
                <button class="modal__close-button calendar-sidebar__close" type="button" data-close-modal="calendar-sidebar" aria-label="<?php echo esc_attr__('Close sidebar', 'twmp-ath'); ?>">
                    <?php echo twmp_get_svg_icon('close'); ?>
                </button>
            </div>
            <div class="modal__content js-content calendar-sidebar__content" data-calendar-sidebar-content></div>
        </div>
    </div>
</div>

<?php
get_footer();
