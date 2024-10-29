<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

    function babypng_register_settings() {

      
        add_option( 'babypng_licence_key', '');
		add_option( 'babypng_imageCount', 0);
		add_option( 'babypng_savedspacecount', 0);
        add_option( 'babypng_plandata', '');
        

        register_setting( 'babypng_options_group', 'babypng_licence_key', 'babypng_callback' );
        register_setting( 'babypng_options_group', 'babypng_imageCount', 'babypng_callback' );
        register_setting( 'babypng_options_group', 'babypng_savedspacecount', 'babypng_callback' );
        register_setting( 'babypng_options_group', 'babypng_plandata', 'babypng_callback' );

    }

    function babypng_save_settings() {
	
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_REQUEST['_wpnonce'])), 'setting' ) ) {
            wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_error='.urlencode('Not Valid CSRF Token') ) );
            exit; 
    	}
        $babypng_licence_key = sanitize_text_field($_POST['babypng_licence_key']);


        update_option( 'babypng_licence_key', $babypng_licence_key);
        wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_msg='.urlencode('Settings saved') ) );
        exit;
 
        
        // check if keys are valid //
    }
    add_action( 'admin_init', 'babypng_register_settings' );


    function babypng_get_account_info(){
        if(get_option('babypng_licence_key') && get_option('babypng_licence_key') != ''){

           
            $domain = str_replace('https://', '', get_option('siteurl')); 
            $domain = str_replace('http://', '', $domain); 
            $domain = str_replace('www.', '', $domain); 
            $msg = '';
            $url = 'get_account_info';
            $data = ['website' => $domain];
            $res = babypng_commonApiCall($url,$data,'GET');
            $res = json_decode($res, true);

           

            if (isset($res['planinfo']) && !empty($res['planinfo'])) {

                if (isset($res['imagecount']) && !empty($res['imagecount'])) {
                    $countarray = json_decode($res['imagecount']);
                    $res['planinfo']['imageLeft'] = $countarray->planimagecount - $countarray->imageCount;
                }

                update_option( 'babypng_plandata', wp_json_encode($res['planinfo']));                
            }

            
        }
        
    }

    add_action( 'admin_init', 'babypng_get_account_info' );