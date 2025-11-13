<?php
/**
 * Plugin Name: ResetCraft
 * Description: Powerful reset toolkit featuring one-click database cleanup, selective reset controls, and a professional admin dashboard.
 * Version: 1.0.0
 * Author: SREEMON KS
 * Text Domain: resetcraft
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RESETCRAFT_VERSION', '1.0.0' );
define( 'RESETCRAFT_PLUGIN_FILE', __FILE__ );
define( 'RESETCRAFT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RESETCRAFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once RESETCRAFT_PLUGIN_DIR . 'includes/class-resetcraft.php';
require_once RESETCRAFT_PLUGIN_DIR . 'admin/class-resetcraft-admin.php';

/**
 * Begins execution of the plugin.
 *
 * @return void
 */
function resetcraft_run() {
	$plugin = new ResetCraft\ResetCraft();
	$plugin->run();
}

resetcraft_run();

