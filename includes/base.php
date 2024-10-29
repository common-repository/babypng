<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('wp_loaded', 'babypng_run_cmd');
add_action('admin_notices', 'babypng_admin_notice');
add_action('admin_get_key', 'babypng_getApikeyBabypng');
add_action('admin_generate_key', 'babypng_generateApiKeyBabypng');
add_action('add_attachment', 'babypng_replace_uploaded_image_with_compressed');
add_action('manage_media_custom_column', 'babypng_custom_media_column_content', 10, 2);
add_filter('manage_media_columns', 'babypng_custom_media_columns');
add_action('admin_init', 'babypng_custom_dashboard_notifications_init_babypng');
add_action('admin_apply_coupon', 'babypng_applyCouponCode');





function babypng_admin_notice()
{
    if (isset($_GET['babypng_msg'])) {
		 if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field( wp_unslash ( $_GET['_wpnonce'])), 'setting')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php echo esc_html(sanitize_text_field($_GET['babypng_msg'])); ?>
            </p>
        </div>
	
        <?php
			 
		}else{
			  wp_die('Security check failed');
		}
    } else if (isset($_GET['babypng_error'])) {
        ?>
            <div class="notice notice-error is-dismissible">
                <p>
                <?php echo esc_html(sanitize_text_field($_GET['babypng_error'])); ?>
                </p>
            </div>
        <?php
    }
}


function babypng_run_cmd()
{
    $allowed_functions = array(
        'babypng_save_settings',
        'babypng_getApikeyBabypng',
        'babypng_generateApiKeyBabypng',
        'babypng_saveBabyPngSettings',
        'babypng_applyCouponCode',
        'babypng_get_account_info'
    );
   $cmdkey = isset( $_REQUEST['babypng_cmd'] ) ? sanitize_text_field($_REQUEST['babypng_cmd']) : '' ;



    if ( $cmdkey != '' &&
		in_array($cmdkey, $allowed_functions ) 
        && is_callable($cmdkey) 
        && isset($_REQUEST['_wpnonce']) 
        && wp_verify_nonce(sanitize_text_field( wp_unslash($_REQUEST['_wpnonce'])), 'setting') 
        ){
   
         $command = sanitize_text_field($cmdkey);

        call_user_func( $command );
    }

}



function babypng_compress($file_path)
{
    $api_key = get_option('babypng_licence_key'); 
    $website = get_option('siteurl'); 
    $file_path_arr = explode("wp-content", $file_path);
    $act_path = site_url("wp-content" . $file_path_arr[1]);
    $data = ['img_url' => $act_path,'website'=> $website,'api_key' => $api_key];
    $url = "compressImage";
    $result = babypng_commonApiCall($url,$data,'GET');


    if ($result) {
        $result_arr = json_decode($result);
        if (isset($result_arr->image_url) && $result_arr->image_url != '') {
            return $result_arr->image_url;
        } else {
            return false;
        }
    } else {
        return false;
    }
}


function babypng_replace_uploaded_image_with_compressed($attachment_id)
{
    global $wp_filesystem;
    $compressed_image_url = '';
    // Get the attachment URL
    $attachment_url = wp_get_attachment_url($attachment_id);


    // Check if the attachment URL exists and is an image
    if ($attachment_url && preg_match('/\.(jpg|jpeg|png|gif)$/', $attachment_url)) {

        $file_path = get_attached_file($attachment_id);
        $beforeCompress = round(filesize($file_path)/1024);  

        // Check if the file is an image
        if (wp_attachment_is_image($attachment_id)) {

            
             wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file_path));
            // Compress the image using the BabyPNG API
            $compressed_image_url = babypng_compress($file_path);

            if ($compressed_image_url != '') {

                // URL of the compressed image
               

                // Fetch the compressed image from the URL
                $response = wp_remote_get($compressed_image_url);
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $compressed_image_data = wp_remote_retrieve_body($response);

                    // Get the file path
                    $file_path = get_attached_file($attachment_id);

                     
                    if (empty($wp_filesystem)) {
                        require_once ABSPATH . '/wp-admin/includes/file.php';
                        WP_Filesystem();
                    }
                    $write_result = false;
                     if ($wp_filesystem) {
                             // Update the attachment with the compressed image data
                            $write_result = $wp_filesystem->put_contents($file_path, $compressed_image_data, FS_CHMOD_FILE);
                     }

                    if ($write_result !== false) {

                    $afterCompress = round(filesize($file_path)/1024);

                    // Regenerate image sizes
                    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $file_path));

                    if(!empty($beforeCompress) && !empty($afterCompress)){
                        $meta_added = add_post_meta($attachment_id, 'babypng_img_compress',$beforeCompress.'-'.$afterCompress , true);
						
						 $imageCount = get_option('babypng_imageCount'); 
						 $spaceCount = get_option('babypng_savedspacecount'); 
						 $imageCount++;
						 $spaceCount = $spaceCount + (($beforeCompress - $afterCompress)/$beforeCompress * 100);
				
						
						update_option( 'babypng_imageCount', $imageCount);
						update_option( 'babypng_savedspacecount', $spaceCount);
                    }
                    // Refresh the cache
                    wp_cache_delete($attachment_id, 'post_meta');

                    // Clear any applied image edits
                    wp_remove_object_terms($attachment_id, 'image_edit', 'post_tag');

                    // Set the featured image for the parent post
                    $parent_post_id = wp_get_post_parent_id($attachment_id);
                    if ($parent_post_id !== 0) {
                        set_post_thumbnail($parent_post_id, $attachment_id);
                    }
                }
              }
            }
        }
    }
}

// genrate api key for domain
function babypng_generateApiKeyBabypng()
{

     $nonce = sanitize_text_field( wp_unslash ( ($_REQUEST['_wpnonce'])));
        if ( ! wp_verify_nonce( $nonce, 'setting' ) ) {
           wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_error='.urlencode('Not Valid CSRF Token') ) );
             exit; 
        }

    $url = 'generateKey';
    $send_data = array(
        'domain' => get_option('siteurl'),
        'email' => sanitize_email($_POST['email']),
        'username' => sanitize_text_field($_POST['username']),
        'password' => sanitize_text_field($_POST['password']),
    );


    $res = babypng_commonApiCall($url,$send_data,'POST');
    $res = json_decode($res, true);
    if (isset($res['status']) && $res['status'] == 200) {
            update_option( 'babypng_licence_key', $res['apikey']);
			$nonce = wp_create_nonce('redirect_to_babypng_settings');
            wp_redirect(admin_url('/admin.php?page=babypng-settings&ApiKey=' . $res['apikey'] .'&_wpnonce=' . $nonce));
		    
            exit;
    }else{
       
         wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_error='.urlencode('something went wrong') ) );
             exit; 
        
    }

}


function babypng_applyCouponCode(){

    

    $nonce = sanitize_text_field( wp_unslash ( ($_REQUEST['_wpnonce'])));
    if ( ! wp_verify_nonce( $nonce, 'setting' ) ) {
       wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_error='.urlencode('Not Valid CSRF Token') ) );
         exit; 
    }
  

    $url = 'apply_coupon_code';
    $send_data = array(
        'domain' => get_option('siteurl'),
        'coupon' => sanitize_text_field($_POST['couponcode']),
        'api_key' => get_option('babypng_licence_key'),
    );


    $res = babypng_commonApiCall($url,$send_data,'POST');



    $res = json_decode($res, true);


    if (isset($res['status']) && $res['status'] == 200) {
          
			$nonce = wp_create_nonce('redirect_to_babypng_settings_coupon');
            wp_redirect(admin_url('/admin.php?page=babypng-settings&message=' .$res['message'] .'&_wpnonce=' . $nonce));
		    
            exit;
    }else{
       
        if(isset($res['status']) && $res['status'] == 500 && $res['message'] != ''){
            wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_error='.urlencode($res['message']) ) );
            exit; 

        }else{

            wp_redirect( admin_url( '/admin.php?page=babypng-settings&babypng_error='.urlencode('something went wrong') ) );
            exit; 
        }
        
        
    }

    
}


// get api key for domain
function babypng_getApikeyBabypng()
{
     if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash ( $_POST['_wpnonce'])), 'setting')) {
        wp_die('Security check failed');
    }
    $domain = get_option('siteurl'); 
    $key = sanitize_text_field($_POST['babypng_licence_key']);
    $email = get_option('admin_email');
    $url = 'getKey';
    $data = array(
        'domain' => $domain,
        'key' => $key,
        'email' => $email,
    );

    $res = babypng_commonApiCall($url,$data,"POST");
    $res = json_decode($res, true);

    if (isset($res['status']) && $res['status'] == 201) {
        update_option( 'babypng_licence_key', '');
        wp_redirect(admin_url('/admin.php?page=babypng-settings&Apistatus=' . $res['status']));
        exit;
    } else {
        update_option( 'babypng_licence_key', $res['apikey']);
        wp_redirect(admin_url('/admin.php?page=babypng-settings&ApiKey=' . $res['apikey']));
        exit;
    }

}

// Add custom column header in media library at admin
function babypng_custom_media_columns($columns) {
    $columns['babypng_img_compress'] = 'BabyPNG';
   return $columns;
}


function babypng_custom_media_column_content($column_name, $post_id) {
    if ($column_name === 'babypng_img_compress') {
           $sizes = get_post_meta($post_id, '', true); 
         if(!empty($sizes) && isset($sizes['babypng_img_compress'][0]) && !empty($sizes['babypng_img_compress'][0])){
            $sizes = explode('-',$sizes['babypng_img_compress'][0]);
            $size = (end($sizes) - $sizes[0]) / $sizes[0] * 100;
            $size = str_replace('-', '', $size);
            if($sizes[0] == end($sizes)){
                echo wp_kses_post("<strong class='img_num_color'>0% </strong>");
            }else{
                if($size > 0){
                     echo wp_kses_post("<strong class='img_num_color'>".round($size)."% </strong>");
                }else{
                    echo '';
                 }
             }
         }else{
                echo '';
         }
    }
}

function babypng_custom_dashboard_notifications_init_babypng() {
    function babypng_custom_dashboard_notification() {
            $domain = str_replace('https://', '', get_option('siteurl')); 
            $domain = str_replace('http://', '', $domain); 
            $domain = str_replace('www.', '', $domain); 
            $msg = '';
            $url = 'get_img_compress_count';
            $data = ['website' => $domain];
            $res = babypng_commonApiCall($url,$data,'GET');
            $res = json_decode($res, true);

            if (isset($res['status']) && $res['status'] == 200) {
                    $msg = $res['msg'];                 
            }else{
                  $msg = '';
            } 
             echo wp_kses_post($msg) ? wp_kses_post('<div id="img_limit_msg_babypng" class="notice notice-error is-dismissible"><p>'.$msg.'</p></div>'):'';
             
             if(get_option('babypng_imageCount') > 0 && round(get_option('babypng_savedspacecount')/  get_option('babypng_imageCount'), 0) > 0){
		
		     $saveSpace =  "You`ve saved " . round(get_option('babypng_savedspacecount')/  get_option('babypng_imageCount'), 0) ."% in image size using BabyPng";
		     echo  wp_kses_post('<div id="size_msg_babypng" class="notice notice-success is-dismissible"><p>'.$saveSpace.'</p></div>');
		}

             wp_register_script('custom-script', plugins_url() . '/babypng/assets/js/custom-script.js', array('jquery'), '1.0', true);
             wp_enqueue_script('custom-script');
             wp_register_style('custom-style', plugins_url().'/babypng/assets/css/custom-style.css',array(),'1.0');
             wp_enqueue_style('custom-style');


    }
    add_action('admin_notices', 'babypng_custom_dashboard_notification');
}

function babypng_custom_settings_submenu() {
    add_options_page(
        'Babypng', // Page Title
        'Babypng', // Menu Title
        'manage_options', // Capability
        'babypng-setting', // Menu Slug
        'babypng_custom_settings_page' // Callback Function
    );
}

function babypng_custom_settings_page() {
    echo esc_html('<div class="wrap"><h2>Babypng Settings</h2></div>');
}

function babypng_saveBabyPngSettings(){
     if (isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field( wp_unslash ( $_POST['_wpnonce'])), '_wpnonce')) {
        $option_name = 'babypng_compression_timing' ;
        $new_value = sanitize_text_field($_POST['babypng_compression_timing']);
        if ( get_option( $option_name ) !== false ) {
            update_option( $option_name, $new_value );
        } else {
            $autoload = 'no';
            add_option( $option_name, $new_value, '', $autoload );
        }
    }
}



function babypng_commonApiCall($url,$data=[],$method){
      $args = array(
        'method'      => $method,
        'body'        => $data,
        'sslverify' => false,
          'httpversion'=> 1.1
    );
    $url = 'https://babypng.com/api/'.$url;

    $res = wp_remote_get($url,$args);



    if (is_wp_error($res)) {
        $error_message = $res->get_error_message();
        return wp_json_encode(['error' => $error_message]);
    } else {
        $res = wp_remote_retrieve_body($res);
        return $res;
    }
}


