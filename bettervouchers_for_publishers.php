<?php
/**
* Plugin Name:Better Vouchers for Publishers
* Plugin URI: http://bettervouchers.com/wp/bettervouchers_for_publishers.zip
* Description: This plugin brings out better vouchers to your blog posts.
* Version: 1.0.0
* Author: BetterVouchers
* Author URI: http://bettervouchers.com
* License: GPL2
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function BetterVouchersForPublishers_install() {
	global $wp_version;
	if (version_compare($wp_version, "2.7", "<"))
		die("This Plugin requires WordPress version 2.7 or higher to work!");
}
register_activation_hook(__FILE__, "BetterVouchersForPublishers_install");

function BetterVouchersForPublishers_insert_vouchers($att) {
	if(isset($att['token'])){
		if(isset($att['url'])){
			$bv_url = $att['url'];
		}else{
			$bv_url = "http://bettervouchers.com/publishers/";
		}
		$bv_response = wp_remote_get($bv_url.$att['token']);

		if(isset($bv_response['body']))
			return wp_remote_retrieve_body($bv_response);
		else
			// Invalid BV response
			return "";
	}
	else
		// No valid token
		return "";
}

add_shortcode("insert_vouchers", "BetterVouchersForPublishers_insert_vouchers");
?>