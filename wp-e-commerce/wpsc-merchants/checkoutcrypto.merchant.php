<?php
include_once('cc.php');
/**
	* CheckoutCrypto WordPress E-Commerce Client
	* Version 0.8.2 Alpha
	* Copyright 2014 CheckoutCrypto.com Apache 2.0 License
	* @package wp-e-comemrce
	* @since 3.7.6
	* @subpackage wpsc-merchants
*/
$nzshpcrt_gateways[$num] = array(
	'name' => __( 'CheckoutCrypto', 'wpsc' ),
	'api_version' => 2.0,
	'class_name' => 'wpsc_merchant_CheckoutCrypto',
	'has_recurring_billing' => false,
	'display_name' => __( 'CheckoutCrypto Payment', 'wpsc' ),
	'wp_admin_cannot_cancel' => true,
	'requirements' => array(
		 /// so that you can restrict merchant modules to PHP 5, if you use PHP 5 features
		///'php_version' => 5.0,
	),

	'form' => 'form_CheckoutCrypto',
	'submit_function' => 'submit_CheckoutCrypto',
	'internalname' => 'wpsc_merchant_CheckoutCrypto',
);

/*
*  CheckoutCrypto Merchant Class
*/
class wpsc_merchant_CheckoutCrypto extends wpsc_merchant {

	var $name = '';


	function __construct( $purchase_id = null, $is_receiving = false ) {
		global $wpdb; 
		if ( ($purchase_id == null) && ($is_receiving == true) ) {
			$this->is_receiving = true;
			
		}
		if ( $purchase_id > 0 ) {
			$this->purchase_id = $purchase_id;
		}
		$this->name = __( 'CheckoutCrypto', 'wpsc' );
		parent::__construct( $purchase_id, $is_receiving );
	}
	/**
	* construct value array method, converts the data gathered by the base class code to something acceptable to the gateway
	* @access public
	*/
	function construct_value_array() {
   	 wp_deregister_script( 'jquery' ); // deregisters the default WordPress jQuery  
		wp_enqueue_script('jquery');

 		wp_register_script('wp_cc',  '/wp-content/plugins/wp-e-commerce/wpsc-merchants/checkoutcrypto/js/'. 'wp_cc.js', array('jquery') );
    	wp_enqueue_script('wp_cc');
		wp_register_style( 'cc-style', '/wp-content/plugins/wp-e-commerce/wpsc-merchants/checkoutcrypto/theme/checkoutcrypto.css' );
		wp_enqueue_style('cc-style');

		$this->parse_gateway_notification();

		//$collected_gateway_data
		$cc_form = array();

		// User settings to be sent to paypal
		$cc_form += array(
			'email' => $this->cart_data['email_address'],
			'first_name' => $this->cart_data['shipping_address']['first_name'],
			'last_name' => $this->cart_data['shipping_address']['last_name'],
			'address1' => $this->cart_data['shipping_address']['address'],
			'city' => $this->cart_data['shipping_address']['city'],
			'country' => $this->cart_data['shipping_address']['country'],
			'zip' => $this->cart_data['shipping_address']['post_code']
		);

		$this->collected_gateway_data = $cc_form;
	}

	/**
	* parse_gateway_notification method, receives data from the payment gateway
	* @access private
	*/
	function parse_gateway_notification() { 

		?>
		<div class="total_price" id="<?php echo $this->cart_data['total_price']; ?>"></div>
		<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>
		<?php

	}

	function submit() {
		//$this->set_purchase_processed_by_purchid(2);
		completed(false);
	}
}

/*
*  Order is complete, write to cc_orders table
*/
function completed($complete = true){
		global $wpdb;
		$table_name = "wp_cc_orders";
		$orderid = "1";
		$coin_code  = "POT";
		$coin_name = "potcoin";
		$coin_rate = "0.00013123";
		$coin_paid = "10";
		$coin_address = "pssdad3fvv2re2x32f";
		if($complete == true){
			$order_status = "completed";
		}else{
			$order_status = "pending";
		}
		$cc_queue_id = "5";
		$cc_queue_id_tmp = "5";		

		$wpdb->query("INSERT INTO " . $table_name . " (order_id, coin_code, coin_name, coin_rate, coin_paid, coin_address, order_status, cc_queue_id, cc_queue_id_tmp, timestamp) VALUES ('".$orderid."', '".$coin_code."','".$coin_name."', '".$coin_rate."',".$coin_paid.", '".$coin_address."', '".$order_status."','".$cc_queue_id."','".$cc_queue_id_tmp."',NOW())");

}

/*
*  CheckoutCrypto Admin API form
*/
function form_CheckoutCrypto() {
	$output = "

		<tr>
			<td>'" . __( 'CheckoutCrypto API Key', 'wpsc' ) . "'</td>

			<td>
				<input type='text' size='40' value='" . get_option( 'checkoutcrypto_api_key' ) . "' name='checkoutcrypto_api_key' />
				<p class='description'>
					'" . __( 'Register and enable coins at checkoutcrypto.com, copy and paste your API key here to begin.', 'wpsc' ) . "'
				</p>
			</td>
		</tr>\n";
	return $output;
}


function checkoutcrypto_main(){
	global $wpdb, $wpsc_cart;

}
/*
*  Submit validated API key, initialize dynamic coin cache
*/
function submit_CheckoutCrypto(){
	if ( isset ( $_POST['checkoutcrypto_api_key'] ) ) {
		// validate key
		$result = refresh($_POST['checkoutcrypto_api_key']);
			update_option( 'checkoutcrypto_api_key', $_POST['checkoutcrypto_api_key'] );
	}
	return true;
}

/*
*  Refresh all coins in cc_coins table as well as wpsc_currency_list
*  stores images in checkoutcrypto/image/
*  *reminder update broken
*/
function refresh($api){

		global $wpdb;
		global $cc_db_version;

		try {
        $ccApi = new CheckoutCryptoApi();
        $response = $ccApi->query(array('action' => 'refreshcoins','apikey' => $api));
	
		} catch (exception $e) {
		}

			if($response['response']['status'] == "success"){
					$base_url = plugin_dir_url(__FILE__) . 'checkoutcrypto/image/';
					$coins = $response['response']['coins'];

            		foreach($coins as $coin) {
				        $coin_name = $coin['coin_name'];
				       $coin_code = $coin['coin_code'];
				        $coin_rate = $coin['rate'];
				        $coin_img = $coin['coin_image'];
   						$table_name = $wpdb->prefix . "cc_coins";

               		 //check if coin exists
                    $query = $wpdb->get_var($wpdb->prepare("SELECT coin_rate FROM " . $table_name . " WHERE coin_code = %s",$coin_code));
		            if(!isset($query)) {
						savePhoto($coin_name, $coin_img);
						$coin_img = $base_url.$coin_name.".png";
						$wpdb->query("INSERT INTO " . $table_name . " (coin_code, coin_name, coin_rate, coin_img, date_added) VALUES ('".$coin_code."', '".$coin_name."', ".$coin_rate.", '".$coin_img."', NOW())");
						setCurrency($coin_name, $coin_code);
					}else{
                      //  $wpdb->query("UPDATE " . $table_name . " SET coin_rate = '".$coin_rate."', coin_img = '".$coin_img."' WHERE coin_code = '".$coin_code."'");					
					}

				}
			return true;
		}else{
			var_dump('incorrect API key, or server unavailable');
		}
	return false;
}

/*
*  set currencies in wpsc_currency_list
*  *reminder update broken
*/
function setCurrency($coin_name, $coin_code){
	global $wpdb;
	$table_name = $wpdb->prefix . 'wpsc_currency_list';
	$wpdb->query("INSERT INTO " . $table_name . " (country, isocode, currency, symbol, symbol_html, code, has_regions, tax, continent, visible) VALUES ('na', 'na', '".$coin_name."', '".$coin_code."', '".$coin_code."', '".$coin_code."', '0','0','world','0')");
}

/*
*  store image using curl and fopen
*  *reminder update broken
*/
function savePhoto($coin_name, $coin_img) {
	$base = plugin_dir_path(__FILE__) .'checkoutcrypto/image/';
	$file = $base.$coin_name.".png";
	$ch = curl_init($coin_img);
	$fp = fopen($file, 'wb');
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	chmod($file, 0755);
}
if ( in_array( 'wpsc_merchant_checkoutcrpyto_main', (array)get_option( 'custom_gateway_options' ) ) ) {
	add_action('init', 'checkoutcrpyto_main');
}

/*
*  CheckoutCrypto Callbacks
*/

// callback get all coin data
add_action( 'wp_ajax_cc_coin', 'cc_coin_callback' );
add_action( 'wp_ajax_nopriv_cc_coin', 'cc_coin_callback' );
function cc_coin_callback() {
    global $wpdb; 

    $coin = $_POST['coin_code'];
	$total = $_POST['total_price'];
	$qry = "SELECT coin_code, coin_img, coin_rate, coin_name FROM wp_cc_coins";
	$result = $wpdb->get_results($qry) or die(mysql_error());
	$arr = array();
	$count = 0;
	foreach($result as $res){	
		$arr[$count]['coin_code'] = $res->coin_code;
		$arr[$count]['coin_img'] = $res->coin_img;
		$arr[$count]['coin_name'] = $res->coin_name;
		$arr[$count]['coin_rate'] = $res->coin_rate;
		$arr[$count]['coin_total'] = $total * $res->coin_rate;
		$count = $count + 1;
	}
	echo json_encode($arr);
		completed();	
    die(); 
}
// callback specific coin data
add_action( 'wp_ajax_cc_specific_coin', 'cc_specific_coin_callback' );
add_action( 'wp_ajax_nopriv_cc_specific_coin', 'cc_specific_coin_callback' );
function cc_specific_coin_callback(){
		global $wpdb;

	    $coin = $_POST['coin_code'];
		$total = $_POST['total_price'];
		$qry = "SELECT coin_img, coin_rate, coin_name FROM wp_cc_coins WHERE coin_code = '".$coin."'";
		$result = $wpdb->get_results($qry) or die(mysql_error());
		$res = $result[0];
		$arr['coin_img'] = $res->coin_img;
		$arr['coin_rate'] = $res->coin_rate;
		$arr['coin_name'] = $res->coin_name;
		$arr['coin_code'] = $coin;

		$rate = cc_order_details($coin, 'rate', $coin);
		$queue = cc_order_details($coin, 'address',  $coin);
		$address = cc_order_details($coin, 'status' , $queue);	

		$arr['coin_amount']  = $rate['rate'] * $total;
		$arr['coin_address']  = $address['address'];

		echo json_encode($arr);	  
		die(); 
}

// callback getreceived address balance checks
add_action( 'wp_ajax_cc_checkReceived', 'cc_checkReceived_callback' );
add_action( 'wp_ajax_nopriv_cc_checkReceived', 'cc_checkReceived_callback' );
function cc_checkReceived_callback(){
	 $coin = $_POST['coin_code'];
	$address = $_POST['address'];
	$amount = $_POST['amount'];
	$queue = cc_order_details($coin, 'getreceived', $address);
	$received = cc_order_details($coin, 'status', $queue);

	if($received['status'] == 'success'){
		if($received['amount'] >= $amount){
			$arr['status'] = 1;
		}
		else{	
			if($received['amount'] < $amount){  /// partial payment
				$arr['status'] = 2;
			}
		}
	}else{
		$arr['status'] = 0;
	}
	echo json_encode($arr);
	die();
}

// control callbacks -> api
function cc_order_details($coin, $method, $queue){
	$api = get_option( 'checkoutcrypto_api_key' );
		
	switch($method){
	
	case 'address':
			$result = getnewaddress($api, $coin);		
			if($result['success'] == TRUE){
				return $result['queue_id'];
			}
	break;
	case 'rate':
			return getrate($api, $coin);
	break;
	case 'status':
			return getstatus($api, $queue);
	break;
	case 'getreceived':
			return getreceivedbyaddress($api, $coin, $queue);
	break;
	}
}

/*
*  CheckoutCrypto Core API
*/

	/// get a new coin address
	function getnewaddress($api, $coin){ 

		try {
		    $ccApi = new CheckoutCryptoApi();
		    $response = $ccApi->query(array('action' => 'getnewaddress','apikey' => $api, 'coin' => $coin));
		} catch (exception $e) {

		}
		if(isset($response['response']['queue_id'])) {
		    $result['queue_id'] = $response['response']['queue_id'];
		    $result['success'] = TRUE;
		    return $result;
		} else {
		    $result['success'] = FALSE;
		   return $result;
		}

	}
	/// get a single coin rate in usd
	function getrate($api, $coin){
		try {
		    $ccApi = new CheckoutCryptoApi();
		    $response = $ccApi->query(array('action' => 'getrate','apikey' => $api, 'coin' => $coin));
		} catch (exception $e) {

		}

		if(isset($response['response']['rates'])) {
		    $result['rate'] = $response['response']['rates']['USD_'.$request['coin_name']];
		    $result['success'] = TRUE;
		    return $result;
		} else {
		    $result['success'] = FALSE;
		   return $result;
		}

	}
	/// get status of any api call
	function getstatus($api, $queue){
		 try {
		    $ccApi = new CheckoutCryptoApi();
		    $response = $ccApi->query(array('action' => 'getstatus','apikey' => $api, 'orderid' => $queue));
		} catch (exception $e) {

		}
		if(isset($response['response']['status'])) {
		    $result['status'] = $response['response']['status'];
		    if(isset($response['response']['address'])) {
		        $result['address'] = $response['response']['address'];
		    } else {
		        $result['address'] = FALSE;
		    }
		    if(isset($response['response']['balance'])) {
		        $result['balance'] = $response['response']['balance'];
		    } else {
		        $result['balance'] = FALSE;
		    }
		    $result['success'] = TRUE;
		    return $result;
		} else {
		    $result['success'] = FALSE;
		   return $result;
		}
	}
	/// check balance of single address from a single coin
	function getreceivedbyaddress($api, $coin, $address){
	   try {
		    $ccApi = new CheckoutCryptoApi();
		    $response = $ccApi->query(array('action' => 'getreceivedbyaddress','apikey' => $api, 'coin' => $coin, 'address' => $address, 'confirms' => '1'));
		} catch (exception $e) {

		}

		if(isset($response['response']['orderid'])) {
		    $result['queue_id'] = $response['response']['orderid'];
		    $result['success'] = TRUE;
		    return $result;
		} else {
		    $result['success'] = FALSE;
		   return $result;
		}

	}

?>
