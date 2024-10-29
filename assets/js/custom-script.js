jQuery(document).ready(function($) {
    setTimeout(function() {
        $('#img_limit_msg_babypng').fadeOut('slow');
    }, 5000);


    $('#couponcode').hide();


});

function showCoupon(){
    jQuery('#couponcode').show();
    jQuery('#couponcodeshow').hide();
    jQuery('#couponcodehide').show();
}

function hideCoupon(){
    jQuery('#couponcode').hide();
    jQuery('#couponcodehide').show();
    jQuery('#couponcodeshow').show();

}
