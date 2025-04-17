<?php

/**
 * Plugin Name: UTexas EID Authentication
 * Version: 2.0.1
 * Description: UT-specific configuration for use with the WP SAML Auth plugin, including overrides for password resets and email notifications. DO NOT DISABLE THIS PLUGIN if you are using EID sign in on this site.
 * Author: Web Content Management Solutions, UT Austin
 * Text Domain: utexas-eid-auth
 *
 * @package utexas-eid-auth
 */

$utexas_eid_self_help_url = 'https://idmanager.its.utexas.edu/eid_self_help/';

// Add file includes, separated out for tidiness.
require_once plugin_dir_path( __FILE__ ) . 'wpsa-options.php';
require_once plugin_dir_path( __FILE__ ) . 'hide-passwords.php';
require_once plugin_dir_path( __FILE__ ) . 'manage-plugins.php';
require_once plugin_dir_path( __FILE__ ) . 'class-rename-wp-login.php';

// Add JavaScript to automatically fill the email field with an @eid.utetxas.edu
// address when creating a new user.
function utexas_wpsa_autofill_new_user_email( $hook ) {
	if ( 'user-new.php' === $hook ) {
		wp_enqueue_script( 'utexas_autofill_new_user_email', plugin_dir_url( __FILE__ ) . 'utexas-autofill-email.js' );
	}
}
add_action( 'admin_enqueue_scripts', 'utexas_wpsa_autofill_new_user_email' );

// After logging out of WordPress, redirect to Enterprise Authentication logout
// page to force the session to be ended as well.
// (More info: https://ut.service-now.com/utss/KAhome.do?number=KB0014366 )
function utexas_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
	return 'https://enterprise.login.utexas.edu/idp/profile/Logout';
}
add_filter( 'logout_redirect', 'utexas_logout_redirect', 10, 3 );

// Because we're adding the redirect with logout_redirect, that gets processed
// by wp_safe_redirect, which only allows "local" redirects - so we have to
// add "login.utexas.edu" to the list of hostnames that are "safe".
function utexas_allowed_redirect_hosts( $content ) {
	$content[] = 'enterprise.login.utexas.edu';
	$content[] = 'enterprise-test.login.utexas.edu';
	return $content;
}
add_filter( 'allowed_redirect_hosts', 'utexas_allowed_redirect_hosts', 10 );

// Same as wp_login_url, but drop the reauth param, ignore force_reauth.
function utexas_disable_reauth( $login_url, $redirect, $force_reauth ) {
	$login_url = site_url( 'wp-login.php', 'login' );
	if ( ! empty( $redirect ) ) {
		$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
	}
	return $login_url;
}
add_filter( 'login_url', 'utexas_disable_reauth', 10, 3 );

// Load the UT-default configuration options for WP SAML Auth.
// @see wpsa-options.php
add_filter( 'wp_saml_auth_option', 'utexas_wpsax_filter_option', 10, 2 );

// @see manage-plugins.php
register_activation_hook( __FILE__, 'utexas_wp_saml_auth_activate' );
