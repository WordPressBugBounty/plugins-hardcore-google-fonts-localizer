<?php
/**
 * Plugin Name:       Hardcore Google Fonts Localizer
 * Description:       A Plugin that enables you to host google fonts on your server with no effort (GDPR safe)
 * Version:           1.2.2
 * Author:            Lorem Ipsum web.solutions GmbH
 * Author URI:        https://www.loremipsum.at/
 * License:           GPL v3
 * Text Domain:       google-fonts-localizer, admin-page-framework
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

load_plugin_textdomain( 'admin-page-framework', false, basename( dirname( __FILE__ ) ) . '/lib/apf/languages' );
load_plugin_textdomain( 'google-fonts-localizer', false, basename( dirname( __FILE__ ) ) . '/languages' );

define( 'GOOGLE_FONTS_LOCALIZER_PLUGIN_VERSION', '1.2.2' );
define( 'GOOGLE_FONTS_LOCALIZER_PLUGIN_FILE', __FILE__);
define( 'GOOGLE_FONTS_LOCALIZER_PLUGIN_TITLE', 'Hardcore Google Fonts Localizer');

if (is_admin()) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-google-fonts-localizer-admin.php';
    new Google_Fonts_Localizer_Admin();
} else {
    require_once plugin_dir_path( __FILE__ ) . 'frontend/class-google-fonts-localizer-frontend.php';
    new Google_Fonts_Localizer_Frontend();
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-google-fonts-localizer-filters.php';
new Google_Fonts_Localizer_Filters();