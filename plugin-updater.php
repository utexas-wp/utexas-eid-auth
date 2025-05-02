<?php

// This filter alters where WordPress looks for specific plugin update info.
add_filter( 'plugins_api', 'utexas_eid_auth_plugin_info', 20, 3 );
// This filter compares the latest version to the current version and reports an update.
add_filter( 'site_transient_update_plugins', 'utexas_eid_auth_plugin_update' );
// This filter ensures the plugin is deposited in the root plugin directory.
add_filter( 'upgrader_install_package_result', 'utexas_eid_auth_move_plugin', 10, 2 );

function utexas_eid_auth_plugin_info( $res, $action, $args ) {
	// Do nothing if this is not our plugin or a different action.
	if ( 'plugin_information' !== $action || UTexasEidAuthPluginUpdater::$slug !== $args->slug ) {
		return $res;
	}
	$latest = utexas_eid_auth_get_remote_data();
	if ( $latest === false ) {
		return $res;
	}
	$plugin_info                = utexas_eid_auth_get_local_data();
	$res                        = new stdClass();
	$res->name                  = $plugin_info['Name'];
	$res->slug                  = $plugin_info['TextDomain'];
	$res->version               = $latest['Version'];
	$res->requires_php          = $latest['RequiresPHP'] ?? '';
	$res->author                = $latest['Author'] ?? '';
	$res->tested                = UTexasEidAuthPluginUpdater::$tested;
	$res->sections              = array();
	$res->sections['Changelog'] = UTexasEidAuthPluginUpdater::$changelog;
	if ( isset( $latest['Description'] ) ) {
		$res->sections['Description'] = $latest['Description'];
	}
	return $res;
}

function utexas_eid_auth_plugin_update( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}
	$latest = utexas_eid_auth_get_remote_data();
	if ( $latest === false ) {
		return $transient;
	}
	$local_info = utexas_eid_auth_get_local_data();
	if ( version_compare( $local_info['Version'], $latest['Version'], '<' ) ) {
		$res              = new stdClass();
		$res->slug        = $local_info['TextDomain'];
		$res->plugin      = plugin_basename( plugin_dir_path( __FILE__ ) . UTexasEidAuthPluginUpdater::$slug . '.php' );
		$res->new_version = $latest['Version'];
		$res->package     = $latest['UpdateURI'];
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'icon.png' ) ) {
			$res->icons = array(
				'1x' => plugin_dir_path( __FILE__ ) . 'icon.png',
			);
		}
		$transient->response[ $res->plugin ] = $res;
		$transient->checked[ $res->plugin ]  = $latest['Version'];
	}
	return $transient;
}

function utexas_eid_auth_get_local_data() {
	return get_plugin_data( plugin_dir_path( __FILE__ ) . UTexasEidAuthPluginUpdater::$slug . '.php' );
}

function utexas_eid_auth_get_remote_data() {
	$plugin_name = UTexasEidAuthPluginUpdater::$slug;
	// Implement WordPress transient cache.
	// Compare https://github.com/eduardovillao/wp-self-host-updater-checker/blob/main/class-updater-checker.php
	$latest       = \get_transient( $plugin_name . 'cache' );
	$force_update = \sanitize_text_field( $_GET['force-check'] ?? '' );
	if ( $force_update === '1' || $latest === false ) {
		// Look for latest plugin info at the GitHub remote endpoint.
		$latest = get_plugin_data( UTexasEidAuthPluginUpdater::$info );
	}
	// Do nothing if we don't get the correct response.
	if ( ! isset( $latest['TextDomain'] ) ) {
		return false;
	}
	// Cache for one hour.
	\set_transient( $plugin_name . 'cache', $latest, 3600 );
	return $latest;
}

function utexas_eid_auth_move_plugin( $result, $options ) {
	// Only move if the original plugin was in the expected location.
	if ( $options['plugin'] !== UTexasEidAuthPluginUpdater::$slug . '/' . UTexasEidAuthPluginUpdater::$slug . '.php' ) {
		return $result;
	}
	$new_plugin_path = $result['destination'] ?? '';
	if ( ! file_exists( $new_plugin_path ) ) {
		return $result;
	}
	// Move to the root path for all plugins, e.g. `.../wp-content/plugins`
	$canonical_plugin_dir = trailingslashit( WP_PLUGIN_DIR ) . UTexasEidAuthPluginUpdater::$slug;
	move_dir( $new_plugin_path, $canonical_plugin_dir );
	// Update WordPress metadata to reflect changes above.
	$result['destination']        = $canonical_plugin_dir;
	$result['destination_name']   = UTexasEidAuthPluginUpdater::$slug;
	$result['remote_destination'] = $canonical_plugin_dir;
	return $result;
}
