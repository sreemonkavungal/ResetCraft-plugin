<?php
/**
 * Uninstall handler for ResetCraft
 *
 * This file is executed when the plugin is uninstalled from the WordPress admin.
 * It must be placed in the plugin root and will be loaded by WordPress.
 *
 * IMPORTANT:
 *  Make a backup before uninstalling on production sites.
 *  Edit the option/table/meta names below to match what your plugin actually uses.
 */

// If uninstall not called from WP, bail.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean a single site (options, transients, usermeta, custom tables).
 *
 * Edit the option names, transient keys, usermeta keys and table names to match your plugin.
 *
 * @return void
 */
function resetcraft_clean_single_site() {
	global $wpdb;

	
	// 1) Unschedule plugin cron hooks (if any)

	// Example cron hook name used by plugin (change if different)
	if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
		wp_clear_scheduled_hook( 'resetcraft_cron_hook' );
	}


	// 2) Delete options (single-site options)
	
	// Replace these with your actual option names
	$option_names = array(
		'resetcraft_options',
		'resetcraft_version',
	);

	foreach ( $option_names as $opt ) {
		delete_option( $opt );
	}

-
	// 3) Delete network/site options (if your plugin used site options)

	// Note: delete_site_option() will fail gracefully on single-site installs.
	$site_option_names = array(
		'resetcraft_multisite_settings',
	);

	foreach ( $site_option_names as $sop ) {
		delete_site_option( $sop );
	}


	// 4) Delete transients created by the plugin

	// If you used known transient keys, list them here; otherwise cleanup is optional.
	$transients = array(
		'resetcraft_count_cache',
	);

	foreach ( $transients as $t ) {
		delete_transient( $t );
	}


	// 5) Delete usermeta entries the plugin created

	// To delete a usermeta key across all users:
	// delete_metadata( 'user', 0, 'your_meta_key', '', true );
	delete_metadata( 'user', 0, 'resetcraft_user_flag', '', true );


	// 6) Remove custom capabilities added by plugin

	// Example: if plugin added capability 'manage_resetcraft' to roles, remove it.
	$cap = 'manage_resetcraft';
	$roles_to_check = array( 'administrator', 'editor' ); // change as needed

	foreach ( $roles_to_check as $role_name ) {
		$role = get_role( $role_name );
		if ( $role && $role->has_cap( $cap ) ) {
			$role->remove_cap( $cap );
		}
	}


	// 7) Drop custom database tables (ONLY if they exist and are owned by plugin)

	// Replace table names with the ones your plugin created, if any.
	$custom_tables = array(
		$wpdb->prefix . 'resetcraft_logs', // example table
	);

	foreach ( $custom_tables as $table ) {
		// Check table exists before dropping.
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $table ) ) );
		if ( $exists === $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}


	// 8) Any other cleanup hooks or user-defined cleanup

	do_action( 'resetcraft_uninstall_cleanup' ); 
}


// Main uninstall flow


if ( is_multisite() ) {
	// If network uninstall (must remove per-site data)
	// get_sites() requires WordPress 4.6+. If not available, you could use wp_get_sites() fallback.
	if ( function_exists( 'get_sites' ) ) {
		$sites = get_sites( array( 'fields' => 'ids' ) );
		if ( ! empty( $sites ) ) {
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				resetcraft_clean_single_site();
				restore_current_blog();
			}
		}
	} else {
		// Fallback for very old WP versions (unlikely)
		$sites = wp_get_sites();
		if ( ! empty( $sites ) ) {
			foreach ( $sites as $s ) {
				switch_to_blog( intval( $s['blog_id'] ) );
				resetcraft_clean_single_site();
				restore_current_blog();
			}
		}
	}
} else {
	// Single-site uninstall
	resetcraft_clean_single_site();
}
