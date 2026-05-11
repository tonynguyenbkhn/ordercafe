import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Customize the ready links.
 */
addFilter( 'barn2_setup_wizard_ready_links', 'wro-wizard', ( links ) => {

	const listItems = [
		{
			title: __('Add opening hours'),
			href: `${barn2_setup_wizard.opening_hours_link}`,
		},
		{
			title: __('Go to settings page'),
			href: `${barn2_setup_wizard.skip_url}`,
		},
	];

	return listItems

} );

/**
 * Disable the settings button.
 */
addFilter( 'barn2_setup_wizard_show_settings_button', 'wro-wizard', () => false )