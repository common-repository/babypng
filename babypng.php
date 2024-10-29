<?php
/**
*Plugin Name: BabyPng
*Description: BabyPNG is your ultimate solution for effortlessly compressing images to enhance your website's speed and performance. With BabyPNG, optimizing JPEG, PNG, and WebP images becomes a breeze.
*Version: 1.0.1
*Author: BabyPng
*Author URI: https://babypng.com/
*License: GPLv2 or later
*License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'BABYPNG_URL', plugin_dir_url(__FILE__) );
define( 'BABYPNG_PATH', plugin_dir_path(__FILE__) );
define("BABYPNG_PLUGIN_DIR",addslashes(dirname(__FILE__)));
$pinfo = pathinfo(BABYPNG_PLUGIN_DIR);
define("BABYPNG_PLUGIN_FILE",addslashes(__FILE__));
define('BABYPNG_SLUG','babypng' );

$upload_dir = wp_upload_dir();

add_action('wp_head','BabypngHookHeader', -1000);
add_action( 'after_switch_theme', 'flush_rewrite_rules' );

function BabypngWpActivate() {
	update_option( 'babypng_plugin_status', 1);
  update_option('babypng_permalinks_flushed', 0);
}
register_activation_hook( __FILE__, 'BabypngWpActivate' );

function BabypngHookHeader() { 
}
  

require_once(BABYPNG_PLUGIN_DIR . '/init.php');

register_deactivation_hook( __FILE__, 'BabypngDeactivate' );
function BabypngDeactivate(){
	update_option( 'babypng_plugin_status', 0);
}


add_action( 'upgrader_process_complete', 'BabypngActionUpgraderProcessComplete' );

function BabypngActionUpgraderProcessComplete(){

}

function BabyPNG_plugin_uninstall() {
  
 delete_option('babypng_imageCount');
 delete_option('babypng_savedspacecount');
  
}


register_uninstall_hook(__FILE__, 'BabyPNG_plugin_uninstall');
