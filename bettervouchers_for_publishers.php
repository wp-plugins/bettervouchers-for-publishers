<?php
/**
* Plugin Name:Better Vouchers for Publishers
* Plugin URI: http://bettervouchers.com/wp/bettervouchers_for_publishers.zip
* Description: This plugin brings out better vouchers to your blog posts.
* Version: 2.0.0
* Author: BetterVouchers
* Author URI: http://bettervouchers.com
* License: GPL2
*/



defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

register_activation_hook(__FILE__, "BetterVouchersForPublishers_install");
add_action('admin_menu', 'BetterVouchersForPublishers_plugin_setup_menu');
add_shortcode("insert_vouchers", "BetterVouchersForPublishers_insert_vouchers");
add_action('the_content', 'addVouchersToContent');


function BetterVouchersForPublishers_install() {
	global $wp_version;
	if (version_compare($wp_version, "2.7", "<")){
		die("This Plugin requires WordPress version 2.7 or higher to work!");
	}
}


function BetterVouchersForPublishers_insert_vouchers($att) {
	if(isset($att['token'])) {
		
		if(isset($att['url'])) {
			$bv_url = $att['url'];
		}else{
			$bv_url = "http://bettervouchers.com/publishers/";
		}

		$bv_response = wp_remote_get($bv_url.$att['token'].'?key='.$att['key'].'&title='.urlencode($att['title']).'&mid='.$att['mid'].'&source='.urlencode($att['source']));

		if(is_array($bv_response) && array_key_exists('body', $bv_response) && strpos(wp_remote_retrieve_body($bv_response), '-BetterVouchers-') > 0) {
			return wp_remote_retrieve_body($bv_response);
		}else{
			// Invalid BV response
			return "";
		}

	}else{
		// No valid token
		return "";
	}
}


function addVouchersToContent($content) {
	
	$mids = get_option('bv_pid_'.get_the_ID());

	if($mids!=''){
		$content .= '[insert_vouchers token="-" url="http://bettervouchers.com/publishers/" key="'.get_option('bv_key').'" title="'.get_the_title().'" mid="'.$mids.'" source="'.get_permalink().'"]';	
 		
	}
	return $content;
}   



function BetterVouchersForPublishers_plugin_setup_menu(){
        
        add_menu_page( 	'Better Vouchers for Publishers Plugin Settings',
        				'Better Vouchers for Publishers',
        				'manage_options',
        				'BetterVouchersForPublishers-plugin',
        				'BetterVouchersForPublishers_init',
        				 plugin_dir_url( __FILE__ ) . 'images/icon.png' );
}

function BetterVouchersForPublishers_init() {

	$debug_mode = false;
     
    if(isset($_POST["submit"])){ 
		
		$bv_key = $_POST['bv_key_input'];
		update_option('bv_key', $bv_key);
		
		echo '<div id="message" class="updated fade"><p>Key Updated</p></div>';
	}

	if(isset($_POST['update']) || (isset($_POST["submit"]) && !get_option('bv_updated_at') ) ) {
    
	    $marray = array();
	    $mkeywords = array();
	    $debug_list = array();

	    update_option('bv_updated_at', date("d-m-Y h:i:s"));

	    $response = wp_remote_get( 'http://www.bettervouchers.com/wpmerchants.json' );
		
		if(is_array($response) && array_key_exists('body', $response)) {
			
			$merchants = json_decode($response['body']);
			
			foreach($merchants as $merchant){
				
				$marray[$merchant->id] = $merchant->url;
				$mkeywords[$merchant->id] = $merchant->keywords;

			}
		}

		$args = array(	'numberposts' => -1,
	              		'post_type' => 'post',
	              		'order'     => 'DESC',
	              		'post_status' => 'any' );

	    $posts = get_posts($args);

		if( $posts ) {
			   		
	   		$tot_match = 0;
	      	foreach ( $posts as $post ) {
	      		
	      		$match = 0;
	      		$matched_url = '';
	      		$mids = '';

	    		$debug_link = '<a href="'.get_permalink( $post->ID ).'"">'.get_the_title( $post->ID ).'</a>'; 

	     		foreach($marray as $mid => $murl) {
	     			if(isset($murl) && (strlen($murl) > 0) && strpos($post->post_content, $murl) > 0) {
	     				$match++; $tot_match++;
	     				$matched_url .= " ".$murl.',';
	     				$mids .= $mid.',';
	     			}
	     		}

	     		if($mids == '') {

	     			foreach($mkeywords as $mid => $mkeyword) {
		 				
		 				if(strlen($mkeyword ) > 0) {
			 				
			 				$tkeywords = explode('|', $mkeyword);
			 				
			 				foreach($tkeywords as $tkey) {

					 			if(isset($tkey) && (strlen($tkey) > 3) && strpos($post->post_content, ' '.$tkey.' ') > 0) {
					 				
					 				$match++; $tot_match++;
	         						$matched_url .= " ".$tkey.',';
	         						$mids .= $mid.',';
					 			}
					 		}
					 	}
		 			}
		 		}

		 		$debug_list[] = $debug_link.' => '.$match.' matches ('.$matched_url.') - '.$mids;
	         	update_option('bv_pid_'.$post->ID, $mids);
	    	}
	    	echo '<div id="message" class="updated fade"><p>Vouchers Updated</p></div>';
	    }
	    
	}
	echo "<div class='wrap'><h1>BetterVouchers for Publishers</h1></div>";
	?>

	<fieldset>
	    <form method="post" action=""> 
	        <span>BetterVouchers for Publishers Key: &nbsp; </span> 
	        <input type="text" style="width: 250px;" name="bv_key_input" value="<?php echo get_option('bv_key'); ?>" /> &nbsp; 
	        <input type="submit" value="Update key" class="button button-primary" name="submit" />
		    <br /><br />
		    <span>Last vouchers update at: <?php echo get_option('bv_updated_at'); ?> &nbsp; </span> <input type="submit" value="Update vouchers" class="button button-primary" name="update" />
		</form>
	</fieldset>
	<?php
   		
		
		if($debug_mode) {
	?>
		<h3>Posts: </h3>
		<ul>
	<?php
		foreach($debug_list as $li_item) {
		?>
			<li> 
				<?php echo $li_item; ?>
			</li>
		<?php
		}
	?>
		</ul>
	<?php
	echo "=== Total matches: ".$tot_match." ===";
	}	
}

?>