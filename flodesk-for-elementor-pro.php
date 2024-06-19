<?php
/*
Plugin Name: Flodesk for Elementor Pro
Plugin URI: https://github.com/studiocotton/flodesk-for-elementor-pro
Description: Adds Flodesk to your Actions After Submit in the Elementor Pro Form widget.
Version: 1.0.0
Author: Studio Cotton
Author URI: https://studiocotton.co.uk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: flodesk-for-elementor-pro
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'elementor_pro/forms/actions/register', function( $form_actions_registrar ) {
    require_once( __DIR__ . '/includes/class-flodesk-for-elementor-pro.php' );
    $form_actions_registrar->register( new \Flodesk_Elementor_Pro() );
} );
