<div class="wrap doMainRelated">

	<h1>Account</h1>


	<?php if(isset($_GET['message']) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field( wp_unslash ( $_GET['_wpnonce'])), 'redirect_to_babypng_settings_coupon') )  { ?>
		
		<div class="api_key_sec notice notice-success is-dismissible"><p><?php echo esc_html(sanitize_text_field($_GET['message'])); ?></p></div>
		<br>
	<?php } ?>

	<?php if(isset($_GET['ApiKey']) 
		&& isset($_GET['_wpnonce']) 
		&& wp_verify_nonce(sanitize_text_field( wp_unslash ( $_GET['_wpnonce'])), 'redirect_to_babypng_settings') )  { 
	
	?>

		<h2 class="setting_head"> Api Key  :  </h2>
		<p class="api_key_sec"><?php echo esc_html(sanitize_text_field($_GET['ApiKey'])); ?></p>
	<?php  exit; 
	} ?>

	<form method="post" action="">
		<?php wp_nonce_field('setting'); ?>
		<table class="form-table">
			<?php if (isset($_GET['Apistatus']) || get_option('babypng_licence_key') == '') { ?>
				<tr>
					<th scope="row"><label for="username">Username</label></th>
					<td><input type="text" id="username" placeholder="Enter Name" class="regular-text" name="username" required> </td>
				</tr>
				<tr valign="">
					<th scope="row"><label for="email">Email</label></th>
					<td>
						<input type="hidden" name="babypng_cmd" value="babypng_generateApiKeyBabypng">
						<input required type="email" id="email" placeholder="Enter Email" class="regular-text" name="email"
							value="<?php echo esc_html(get_option('admin_email')); ?>" />
						<br />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="username">Password</label></th>
					<td><input type="password" id="password" placeholder="Enter Password" class="regular-text" name="password" required> </td>
				</tr>
			<?php } else { ?>
			
				<tr valign="top">
					<th scope="row"><label for="babypng_licence_key">API Key</label></th>
					<td>
						
						<input type="hidden" name="babypng_cmd" value="babypng_getApikeyBabypng">
						<input type="text" id="babypng_licence_key" class="regular-text" name="babypng_licence_key"
							value="<?php echo esc_html(get_option('babypng_licence_key')); ?>" />

						<br />
					</td>
				</tr>
			<?php } ?>

		</table>
		<div class="btn_center">
			<button class="button button-primary" id="send_btn" type="submit">Save</button>
		</div>
	</form>
	<div class="clear"></div>


	<a href="javascript:void(0)" onclick="showCoupon()" id="couponcodeshow"> Apply Coupon </a>
	


	<div id="couponcode">

	

	<form method="post" action="">
		<?php wp_nonce_field('setting'); ?>
		<table class="form-table">
			<?php if (get_option('babypng_licence_key') != '') { ?>
				<tr>
					<th scope="row"><label for="couponcode">Coupon</label></th>
					<td><input type="text" id="couponcode" placeholder="Enter Coupon Code" class="regular-text" name="couponcode" required> </td>
				</tr>
			<?php } ?>

		</table>
		<div class="btn_center">
		<input type="hidden" name="babypng_cmd" value="babypng_applyCouponCode">
			<button class="button button-primary" id="send_btn" type="submit">Apply</button>
		</div>
	</form>
			<a href="javascript:void(0)" onclick="hideCoupon()" id="couponcodehide"> Cancel  </a>
			</div>
	<div class="clear"></div>

	<label for="couponcode"></label>

	<?php 
	$plandata = json_decode(get_option('babypng_plandata'),true); 

	
	if(!empty($plandata)){ ?>
	<hr>
	<br>
	<p>Your account is connected </p>
	<p>Current  Plan: <b><?php echo esc_html(sanitize_text_field($plandata['plan_name'])); ?> </b></p>

	<?php if(isset($plandata['coupon']) && $plandata['coupon'] != '' ) { ?>
	<p>Coupon Applied: <b><?php echo esc_html(sanitize_text_field($plandata['coupon'])); ?> </b></p>
	<?php  } ?>
	<p> <b><?php echo esc_html(sanitize_text_field($plandata['imageLeft'])); ?></b> Compressions left for this month</p> 

	<?php } ?>

</div>
