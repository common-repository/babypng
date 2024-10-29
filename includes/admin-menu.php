<?php

function babypng_register_menu() {

	$babypng_licence_key = get_option('babypng_licence_key');

	if(!empty($babypng_licence_key)) {

		add_menu_page('Settings', 'BabyPNG', 'manage_options',BABYPNG_SLUG.'-settings','babypng_settings', BABYPNG_URL.'assets/img/babypng-logo.png');
	} else {

		add_menu_page('Settings', 'BabyPNG', 'manage_options',BABYPNG_SLUG.'-settings','babypng_settings', BABYPNG_URL.'assets/img/babypng-logo.png');
	}

}



function babypng_settings() {
	require_once(BABYPNG_PATH . 'views/settings.php');
}


add_action( 'admin_menu', 'babypng_register_menu' );
