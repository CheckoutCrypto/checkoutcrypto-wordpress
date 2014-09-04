<?php
include_once('./checkoutcrypto/lib/cc.php');
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
		'php_version' => 5.0,
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
 		wp_register_script('wp_cc',  '/wp-content/plugins/wp-e-commerce/wpsc-merchants/checkoutcrypto/js/'. 'wp_cc.js', array('jquery') );
                wp_enqueue_script('wp_cc');
                wp_register_script('wp_cc_colorbox',  '/wp-content/plugins/wp-e-commerce/wpsc-merchants/checkoutcrypto/js/'. 'jquery.colorbox-min.js', array('jquery') );
                wp_enqueue_script('wp_cc_colorbox');
                wp_register_style( 'wp_cc', '/wp-content/plugins/wp-e-commerce/wpsc-merchants/checkoutcrypto/theme/checkoutcrypto.css' );
		wp_enqueue_style('wp_cc');
                wp_register_style( 'wp_cc_colorbox', '/wp-content/plugins/wp-e-commerce/wpsc-merchants/checkoutcrypto/theme/colorbox.css' );
                wp_enqueue_style('wp_cc_colorbox');

		$this->parse_gateway_notification();

		//$collected_gateway_data
		$cc_form = array();

		// User cart data
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
		if($_POST['wpsc_action'] == 'submit_checkout' && $_POST['custom_gateway'] == 'wpsc_merchant_CheckoutCrypto') {
        global $wpsc_cart;

        global $wpdb;
        $table_name = $wpdb->prefix . "cc_orders";
        $orderid = $this->purchase_id;
        $order_status = "in_checkout";
        refresh(get_option('checkoutcrypto_api_key')); //make sure we've got fresh data

        //check if order exists and status
        $order_existing = $wpdb->query($wpdb->prepare("SELECT order_id, order_status FROM " . $table_name . " WHERE order_id = %d ", $orderid));
            wpsc_update_customer_meta( 'checkoutcrypto_total_amount', $this->cart_data['total_price']);
            wpsc_update_customer_meta( 'checkoutcrypto_session_id', $this->cart_data['session_id'] );
            wpsc_update_customer_meta( 'checkoutcrypto_purchase_id', $orderid );

            $wpdb->query("INSERT INTO " . $table_name . " (order_id, order_status, timestamp) VALUES ('".$orderid."','".$order_status."',NOW())");

  		?>
		<a href="#" id="cc-hidden-purchase-btn" class="inline cboxElement" style="display:none">Link</a>
		<script type="text/javascript">
        var cc_payment = true;
        var total_price = <?php echo $this->cart_data['total_price'];?>;
		var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		</script>
		<?php
		}
	}

    function submit() {
        global $wpdb, $wpsc_cart;
        
        $table_name = $wpdb->prefix . "cc_orders";
        $orderid = $this->purchase_id;
        $order_status = "order_submit";
        $session_id = (string)wpsc_get_customer_meta( 'checkoutcrypto_session_id' );

        //check if order exists and status
        $order_existing = $wpdb->query($wpdb->prepare("SELECT order_id, order_status FROM " . $table_name . " WHERE order_id = %d ", $orderid));
        
        wpsc_update_purchase_log_status( $session_id, 2, 'sessionid' );
        $wpdb->query($wpdb->prepare("UPDATE  " . $table_name . " SET order_status = %s WHERE order_id = %d", $order_status, $orderid));
	}
}

/*
*  Order is complete, write to cc_orders table
*/
function completed($complete){
    $session_id = (string)wpsc_get_customer_meta( 'checkoutcrypto_session_id' );
    $orderid = (string)wpsc_get_customer_meta( 'checkoutcrypto_purchase_id' );
    wpsc_update_purchase_log_status( $session_id, 3, 'sessionid' );

    global $wpdb;
    $table_name =  $wpdb->prefix . "cc_orders";
    $order_status = "completed";

    $wpdb->query($wpdb->prepare("UPDATE " . $table_name . " SET order_status = %s WHERE order_id = %d", $order_status, $orderid));

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
					$upload_dir = wp_upload_dir();
					$base_url = $upload_dir['baseurl'] . '/checkoutcrypto/image/';
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
						savePhoto(strtolower($coin_name), $coin_img);
						$coin_img = $base_url.strtolower($coin_name).".png";
						$wpdb->query("INSERT INTO " . $table_name . " (coin_code, coin_name, coin_rate, coin_img, date_added) VALUES ('".$coin_code."', '".$coin_name."', ".$coin_rate.", '".$coin_img."', NOW())");
						setCurrency($coin_name, $coin_code);
					} else {
                      $wpdb->query("UPDATE " . $table_name . " SET coin_rate = '".$coin_rate."' WHERE coin_code = '".$coin_code."'");					
					}

				}
			return true;
		}else{
			var_dump('Server gav a bad response.');
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
        $upload_dir = wp_upload_dir();
	$base = $upload_dir['basedir'] .'/checkoutcrypto/image/';

  	if (!is_dir($base)) {
	    wp_mkdir_p($base);
	}

	$file = $base.$coin_name.".png";
	$ch = curl_init($coin_img);
	$fp = fopen($file, 'wb');
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
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

    global $wpdb, $wpsc_cart; 

    $total_price = (string)wpsc_get_customer_meta( 'checkoutcrypto_total_amount' );

    $table_name =  $wpdb->prefix . "cc_coins";
	$qry = "SELECT coin_code, coin_img, coin_rate, coin_name FROM ".$table_name;
	$result = $wpdb->get_results($qry) or die(mysql_error());
	$arr = array();
    $count = 0;
    foreach($result as $res){
		$arr[$count]['coin_code'] = $res->coin_code;
		$arr[$count]['coin_img'] = $res->coin_img;
		$arr[$count]['coin_name'] = $res->coin_name;
		$arr[$count]['coin_rate'] = $res->coin_rate;
		$arr[$count]['coin_total'] = number_format(($total_price / $res->coin_rate),4);
		$count = $count + 1;
	}
	echo json_encode($arr);
    die(); 
}
// callback specific coin data
add_action( 'wp_ajax_cc_specific_coin', 'cc_specific_coin_callback' );
add_action( 'wp_ajax_nopriv_cc_specific_coin', 'cc_specific_coin_callback' );
function cc_specific_coin_callback(){
        global $wpdb, $wpsc_cart;

        $coin = $_POST['coin_code'];

        $session_id = (string)wpsc_get_customer_meta( 'checkoutcrypto_session_id' );
        $total_price = (string)wpsc_get_customer_meta( 'checkoutcrypto_total_amount' );

        $table_name =  $wpdb->prefix . "cc_coins";
		$qry = "SELECT coin_img, coin_rate, coin_name FROM ".$table_name." WHERE coin_code = '".$coin."'";
		$result = $wpdb->get_results($qry);
        $res = $result[0];

		$arr['coin_img'] = $res->coin_img;
		$arr['coin_rate'] = $res->coin_rate;
		$arr['coin_name'] = $res->coin_name;
		$arr['coin_code'] = $coin;

		$queue_id = cc_order_details($coin, 'address');
        $status = cc_order_details($coin, 'status' , $queue_id);

        $table_name = $wpdb->prefix . "cc_coins";
        $result =  $wpdb->get_row($wpdb->prepare("SELECT coin_rate FROM ".$table_name." WHERE coin_code = %s", $arr['coin_code']), ARRAY_A);
        $arr['coin_rate'] = (float)$result['coin_rate'];
        $arr['coin_total'] = number_format(($total_price / $arr['coin_rate']), 4); //format to 4 decimals
        
        $table_name = $wpdb->prefix . "wpsc_purchase_logs";
        $result = $wpdb->get_row($wpdb->prepare("SELECT id FROM ".$table_name." WHERE sessionid = %s", $session_id), ARRAY_A);

        if(isset($result['id'])) {
            (int)$wp_cc_id = $result['id'];
            $table_name =  $wpdb->prefix . "cc_orders";
            $result = $wpdb->query($wpdb->prepare("UPDATE ".$table_name." SET order_status = %s, coin_code = %s, coin_rate = %s, coin_name = %s, cc_queue_id_tmp = %d, cc_queue_id = %d WHERE order_id = %d", 'pending_address', $arr['coin_code'], $arr['coin_rate'], $arr['coin_name'], (int)$queue_id, (int)$queue_id, $wp_cc_id));

        } else {
            $arr = array('status' => 'order_failed');
        }
	    echo json_encode($arr);
		die(); 
}

// callback getreceived address balance checks
add_action( 'wp_ajax_cc_checkReceived', 'cc_checkReceived_callback' );
add_action( 'wp_ajax_nopriv_cc_checkReceived', 'cc_checkReceived_callback' );
function cc_checkReceived_callback(){

    global $wpdb, $wpsc_cart;

    $session_id = (string) wpsc_get_customer_meta( 'checkoutcrypto_session_id' );
    if(isset($_POST['coin_code'])) {
        $coin_code = $_POST['coin_code'];
    } else {
        return FALSE;
    }

    $table_name = $wpdb->prefix . "wpsc_purchase_logs";
    $result = $wpdb->get_row($wpdb->prepare("SELECT id, totalprice FROM ".$table_name." WHERE sessionid = %s", $session_id), ARRAY_A);

    if(isset($result['id'])) {
        $wp_cc_id = $result['id'];
        $total_price = $result['totalprice'];
        $table_name = $wpdb->prefix . "cc_orders";
        $result = $wpdb->get_row($wpdb->prepare("SELECT coin_address, coin_rate, cc_queue_id_tmp FROM ".$table_name." WHERE order_id = %d", $wp_cc_id), ARRAY_A);
        $coin_rate = $result['coin_rate'];
        $arr['coin_total'] = $total_price / $coin_rate;
        if(!isset($result['coin_address'])) {
            $queue_id = $result['cc_queue_id_tmp'];
            $received = cc_order_details($coin_code, 'status', $queue_id);
    	    if(isset($received['success']) AND $received['success'] == 'success'){
                if(isset($received['address'])){
			        $arr['status'] = 'success';
                    $arr['address'] = $received['address'];
                    $status = "pending_payment";

                    $result = $wpdb->get_row($wpdb->prepare("UPDATE ".$table_name." SET order_status =%s, coin_address = %s WHERE order_id = %d", $status, $arr['address'], $wp_cc_id));

                    wpsc_update_purchase_log_details(
                        $session_id,
                        array(
                            'processed' => 2,
                            'date' => time(),
                            'transactid' => $arr['address'],
                        ),
                        'sessionid'
                    );
		        }
            } else{
        		$arr['status'] = 0;
	        }
        } else {
            $arr['address'] = $result['coin_address'];


            $received = cc_order_details($coin_code, 'getreceived', $arr['address']);
            if(isset($received['success']) AND $received['success'] === TRUE){
                $arr['status'] = 'success';
                if(isset($received['coin_paid'])) {
                    $arr['coin_paid'] = $received['coin_paid'];
                    $result = $wpdb->get_row($wpdb->prepare("UPDATE ".$table_name." SET coin_paid = %s WHERE order_id = %d", $arr['coin_paid'], $wp_cc_id));
                    $coin_total = $arr['coin_total'];
                    if((float)$arr['coin_paid'] >= (float)$coin_total) {
                        $transact_url = (string)(get_option('transact_url'));
                        $transact_url = $transact_url."&sessionid=".$session_id;

                        completed(true);
                        do_action('wpsc_payment_successful');

                        $arr['status'] = 'completed';
                        $arr['url'] = $transact_url;

                        transaction_results( $session_id, true, $arr['address'] );

                    } else {
                        //var_dump('not enough');//payment received but not enough
                    }
                } else {
                    $queue_id = $received['queue_id'];
                    $status = cc_order_details($coin_code, 'status', $queue_id);
                    $result = $wpdb->get_row($wpdb->prepare("UPDATE ".$table_name." SET cc_queue_id_tmp = %d WHERE order_id = %d", $queue_id, $wp_cc_id));
                }
            } else {
                $arr['status'] = 'pending';
            }
        }
    } else {
        $arr['status'] = '3';
    }
    echo json_encode($arr);
	die();
}

// control callbacks -> api
function cc_order_details($coin, $method, $queue = FALSE){
	$api = get_option( 'checkoutcrypto_api_key' );
		
	switch($method){
	
	case 'address':
			$result = getnewaddress($api, $coin);		
			if($result['success'] == TRUE){
				return $result['queue_id'];
			}
	break;
	case 'rate':
            $result = getrate($api, $coin);
            if($result['success'] == TRUE) {
    			return $result['rate'];
            } else {
                return FALSE;
            }
	break;
	case 'status':
			$result = getstatus($api, $queue);
			return $result;
	break;
    case 'getreceived':
			$result = getreceivedbyaddress($api, $coin, $queue);
			return $result;
	default:
		echo "Unmatched";
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
		    $result['rate'] = $response['response']['rates']['USD_'.$coin];
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
        } elseif (isset($response['response']['status'])) {
            $result['success'] = TRUE;

            if($response['response']['status'] == 'confirmed') {
                if((float)$response['response']['pending'] > (float)$response['response']['balance']) {
                    $coin_amount = $response['response']['pending'];
                } else {
                    $coin_amount = $response['response']['pending'];
                }
            }
            $result['coin_paid'] = (isset($coin_amount)) ? $coin_amount : 0 ;
        } else {
		    $result['success'] = FALSE;
		}
        return $result;
	}

?>
