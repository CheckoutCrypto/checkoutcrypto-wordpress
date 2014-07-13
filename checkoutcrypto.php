<?php
/**
 * @package checkoutcrypto
 */
/*
Plugin Name: CheckoutCrypto
Plugin URI: https://checkoutcrypto.com
Description: CheckoutCrypto PoS for Cryptocurrencies
Author: checkoutcrypto.com
Version: 0.2
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

//if(is_plugin_active('wp-e-commerce')){


define( 'CC_VERSION', '0.0.1' );
define( 'CC__MINIMUM_WP_VERSION', '3.0' );
define( 'CC__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CC__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CC_DELETE_LIMIT', 100000 );

register_activation_hook( CC__PLUGIN_DIR, array( 'checkoutcrypto', 'plugin_activation' ) );
register_deactivation_hook( CC__PLUGIN_DIR, array( 'checkoutcrypto', 'plugin_deactivation' ) );

require_once( CC__PLUGIN_DIR . 'cc_install.php');

/// create tables
register_activation_hook(  __FILE__ , 'cc_install' );
register_deactivation_hook(  __FILE__ , 'cc_uninstall' );
/// add inital data
///register_activation_hook( $CC__PLUGIN_DIR.'/cc_install.php', 'cc_install_data' );


//}else{


//}
