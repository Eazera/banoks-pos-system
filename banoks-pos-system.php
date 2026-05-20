<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and starts the plugin.
 *
 * @link              https://Eazera.ph
 * @since             1.0.0
 * @package           Banoks_POS
 *
 * @wordpress-plugin
 * Plugin Name:       Banoks POS System
 * Plugin URI:        https://Eazera.ph
 * Description:       Customized plugin POS system developed by Eazera Team.
 * Version:           1.2.3
 * Author:            Eazera
 * Author URI:        https://Eazera.ph
 * License:           GPL-2.0+
 * Text Domain:       banoks-pos
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org/
 */
define( 'BANOKS_POS_VERSION', '1.2.3' );
define( 'BANOKS_POS_PATH', plugin_dir_path( __FILE__ ) );
define( 'BANOKS_POS_URL', plugin_dir_url( __FILE__ ) );

/**
 * GitHub repository used for plugin updates.
 *
 * Replace this with your real public GitHub repository, for example:
 * define( 'BANOKS_POS_GITHUB_REPO', 'eazera/banoks-pos-system' );
 */
define( 'BANOKS_POS_GITHUB_REPO', 'Eazera/banoks-pos-system' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-banoks-pos-updater.php';
new Banoks_POS_Updater( __FILE__, BANOKS_POS_GITHUB_REPO );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-banoks-pos-activator.php
 */
function activate_banoks_pos() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-banoks-pos-activator.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/database/class-banoks-db.php';
	
	Banoks_POS_Activator::activate();
	Banoks_DB::create_tables();
	banoks_pos_add_cashier_role();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-banoks-pos-deactivator.php
 */
function deactivate_banoks_pos() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-banoks-pos-deactivator.php';
	Banoks_POS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_banoks_pos' );
register_deactivation_hook( __FILE__, 'deactivate_banoks_pos' );

/**
 * Add the cashier role and POS capability.
 *
 * @since    1.0.0
 */
function banoks_pos_add_cashier_role() {
	add_role(
		'cashier',
		'Cashier',
		array(
			'read'           => true,
			'banoks_use_pos' => true,
		)
	);

	$cashier = get_role( 'cashier' );

	if ( $cashier && ! $cashier->has_cap( 'banoks_use_pos' ) ) {
		$cashier->add_cap( 'banoks_use_pos' );
	}

	$administrator = get_role( 'administrator' );

	if ( $administrator && ! $administrator->has_cap( 'banoks_use_pos' ) ) {
		$administrator->add_cap( 'banoks_use_pos' );
	}
}

add_action( 'init', 'banoks_pos_add_cashier_role' );

/**
 * The core plugin class that is used to define admin-specific hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-banoks-pos.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything in the plugin is registered via hooks, then kicking off the
 * plugin from this point in the file will register the hooks with WordPress.
 *
 * @since    1.0.0
 */
function run_banoks_pos() {
	$plugin = new Banoks_POS();
	$plugin->run();
}

run_banoks_pos();

/**
 * Keep cashier users inside Banoks POS admin pages.
 *
 * @since    1.0.0
 */
function banoks_pos_restrict_cashier_admin_area() {
	if ( wp_doing_ajax() || current_user_can( 'manage_options' ) || ! current_user_can( 'banoks_use_pos' ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

	if ( 0 === strpos( $page, 'banoks-pos' ) ) {
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=banoks-pos' ) );
	exit;
}

add_action( 'admin_init', 'banoks_pos_restrict_cashier_admin_area' );
