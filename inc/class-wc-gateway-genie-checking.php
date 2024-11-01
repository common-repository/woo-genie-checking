<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Genie Checking Payment Gateway
 *
 * Provides a Genie Checking Payment Gateway, mainly for checkout using Genie Checking.
 *
 * @class 		WC_Gateway_Genie_Checking
 * @extends		WC_Payment_Gateway
 */
class WC_Gateway_Genie_Checking extends WC_Payment_Gateway {

  /**
   * Constructor for the gateway.
   */
	public function __construct() {
		$this->id                 = 'geniechecking';
		$this->icon               = apply_filters( 'woo_geniechecking_logo', plugins_url( 'images/geniecashbox.png', dirname( __FILE__ ) ) );
		$this->has_fields         = true;
		$this->method_title       = __( 'Genie Checking', 'woo-genie-checking' );
		$this->method_description = __( 'Genie Checking sends customers to Genie CashBox to complete payment from checkout page.', 'woo-genie-checking' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->order_button_text  = $this->get_option( 'btn_text_paynow' );
		$this->instructions = $this->get_option( 'instructions', $this->description );
		$this->default_order_status  = $this->get_option( 'default_order_status' );
		$this->genie_payment_confirm_order_status  = $this->get_option( 'genie_payment_confirm_order_status' );

		//Gateway API credentials
		$this->cashbox_number  = $this->get_option('cashbox_number');
		$this->environment = $this->get_option('environment');
		$this->liveurl = 'https://geniecashbox.com/pol/?';
		$this->testurl = 'https://geniecashbox.com/olb/test.php?';
		$this->setupurl = 'https://geniechecking.com/';

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Customer Emails
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

  }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woo-genie-checking' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Genie Checking payment', 'woo-genie-checking' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'woo-genie-checking' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woo-genie-checking' ),
					'default'     => __( 'Genie Checking Payment', 'woo-genie-checking' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'woo-genie-checking' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'woo-genie-checking' ),
					'default'     => __( 'You can proceed payment using Genie CashBox.', 'woo-genie-checking' ),
					'desc_tip'    => true,
				),
				'btn_text_paynow' => array(
					'title'       => __( 'Button Text', 'woo-genie-checking' ),
					'type'        => 'text',
					'description' => __( 'Place order button text', 'woo-genie-checking' ),
					'default'     => __( 'Genie Checking Payment', 'woo-genie-checking' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woo-genie-checking' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions message that display on checkout confirmation page.', 'woo-genie-checking' ),
					'default'     => __( 'Thank you for staying with us.', 'woo-genie-checking' ),
					'desc_tip'    => true,
				),
				'default_order_status' => array(
					'title'       => __( 'Order Status', 'woo-genie-checking' ),
					'type'        => 'select',
					'description' => __( 'Choose immediate order status at customer checkout.', 'woo-genie-checking' ),
					'default'     => 'pending',
					'desc_tip'    => true,
					'options'     => array(
						'pending'          => __( 'Pending payment', 'woo-genie-checking' ),
						'genie-pending'    => __( 'Genie Pending', 'woo-genie-checking' ),
						'on-hold'          => __( 'On Hold', 'woo-genie-checking' ),
						'processing' => __( 'Processing', 'woo-genie-checking' ),
						'completed' => __( 'Completed', 'woo-genie-checking' )
					)
				),
				'genie_payment_confirm_order_status' => array(
					'title'       => __( 'Payment Confirm Order Status', 'woo-genie-checking' ),
					'type'        => 'select',
					'description' => __( 'Choose immediate order status when payment confirm by Genie CashBox.', 'woo-genie-checking' ),
					'default'     => 'processing',
					'desc_tip'    => true,
					'options'     => array(
						'processing' => __( 'Processing', 'woo-genie-checking' ),
						'on-hold'   => __( 'On Hold', 'woo-genie-checking' ),
						'completed' => __( 'Completed', 'woo-genie-checking' )
					)
				),
				'api_details' => array(
					'title'       => __( 'API credentials', 'woo-genie-checking' ),
					'type'        => 'title',
					'description' => sprintf( __( 'Enter your Genie CashBox Number to process payment. Learn how to access your <a target="_blank" href="%s">Genie CashBox Number</a>.', 'woo-genie-checking' ), 'https://geniechecking.com/' ),
				),
				'cashbox_number' => array(
						'title' => __('CashBox Number', 'woo-genie-checking'),
						'type' => 'text',
						'description' => __('Genie CashBox Number', 'woo-genie-checking'),
						'default' => __('', 'woo-genie-checking'),
						'desc_tip' => true
				),
				'environment' => array(
						'title'   => __( 'Genie Test Mode', 'woocommerce' ),
						'label'   => __( 'Enable Test Mode', 'woocommerce' ),
						'type'    => 'checkbox',
						'description' => __( 'Place the payment gateway in test mode.', 'woocommerce' ),
						'default' => 'no',
				)
			);
    }

    /**
     * Output for the order received page.
     */
		public function thankyou_page() {
			if ( $this->instructions ){
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}


    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	    if ( $this->instructions && ! $sent_to_admin && 'geniechecking' === $order->get_payment_method() && $order->has_status( $this->default_order_status ) ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}

		/**
     * Add Genie CashBox Payment option field
     *
     * @param NULL
     * @return mixed
     */
		public function payment_fields(){
			if ( $this->description ){
					echo wpautop( wptexturize( $this->description ) );
			}
		}

		/**
     * Process call back result after Genie CashBox payment
     *
     * @return void
     */
		public function process_genie_checking_order(){
			global $woocommerce;
			if(!isset($_GET['geniechecking_callback'])){ return; }

			$return_callback =  esc_url($_GET['geniechecking_callback']);

			$var_callback = $this->decrypt_pol(substr($return_callback,1));  //Dycrypt Values

			$call_backed_returned = array();
			if(isset($var_callback)){
				$callback_value = array();
				$callback_value = explode('&', $var_callback);
				if(isset($callback_value) && (count($callback_value) > 0)){
						foreach ($callback_value as $key => $result_value) {
							if($result_value != ''){
								$result_single_value = explode('=', $result_value);
								if(isset($result_single_value[0]) && isset($result_single_value[1])){
										$call_backed_returned[$result_single_value[0]] = $result_single_value[1];
								}
							}
						}
				}
			}

			// Get Order ID
			$order_id = 0;
			if(isset($call_backed_returned['orderid'])){
				$order_id    = $call_backed_returned['orderid'];
			}

			// Get Order Status
			$status = '';
			if(isset($call_backed_returned['status'])){
				$status    = $call_backed_returned['status'];
			}

			// Get Genie Number
			$genienumber = 0;
			if(isset($call_backed_returned['genienumber'])){
				$genienumber    = $call_backed_returned['genienumber'];
			}

			// Request No
			$requestno = '';
			if(isset($call_backed_returned['requestno'])){
				$requestno    = $call_backed_returned['requestno'];
			}

			// Transaction Result 0 for success
			$result = '';
			if(isset($call_backed_returned['result'])){
				$result    = $call_backed_returned['result'];
			}

			// Retured Message
			$msg = '';
			if(isset($call_backed_returned['msg'])){
				$msg    = $call_backed_returned['msg'];
			}

			$this->update_genie_order($order_id, $result, $requestno, $msg, $status, $genienumber);

		}

		public function custom_thank_you($url){
				echo '<script>window.location.href="'.$url.'" </script>';
		}

		public function decrypt_pol($string) {

				$key = "A4GE_G5D73"; //key to encrypt and decrypts.
				$result = '';
				$string = base64_decode(urldecode($string));
				for($i=0; $i<strlen($string); $i++) {
						$char = substr($string, $i, 1);
						$keychar = substr($key, ($i % strlen($key))-1, 1);
						$char = chr(ord($char) - ord($keychar));
						$result.=$char;
				}
				return $result;
		}

		public function encrypt_pol($string) {
				$key            = "A4GE_G5D73";
				$result         = '';
				$test           = "";
				for($i=0; $i<strlen($string); $i++) {
						$char       = substr($string, $i, 1);
						$keychar    = substr($key, ($i % strlen($key))-1, 1);
						$char       = chr(ord($char)+ord($keychar));
						$test[$char]= ord($char)+ord($keychar);
						$result.=$char;
				}
				return urlencode(base64_encode($result));
		}

		public function update_genie_order($order_id_value, $result_value, $requestno_value, $msg_value, $status, $genienumber){
				global $woocommerce;
				$order = new WC_Order( $order_id_value );
				if($status == 'cancel'){
					 $order->update_status( 'cancelled', __( 'Payment was cancelled', 'woo-genie-checking' ) );
					 $checkout_url = wc_get_cart_url();
					 $this->custom_thank_you($checkout_url);
				}

				if ($result_value == 0 ) {
						$result = "success";
						//waiting for payment confirmation
						if($status == 'pending'){
								$order->update_status( 'pending', __( 'Waiting for payment confirmation.', 'woo-genie-checking' ) );
								//You will be required to respond to a text message or E-Mail to complete this payment.
								WC()->cart->empty_cart();
						}

						if(($status == 'approve') || ($status == 'approved')){
								if($this->genie_payment_confirm_order_status == 'complete'){
									$order->payment_complete();
								}else{
									$order->update_status( 'processing', __( 'Genie checking request <strong>'.$requestno_value.'</strong>  Genie No <strong>'.$genienumber.'</strong> '.$status.'.', 'woo-genie-checking' ) );
								}
								$order->add_order_note( __('Genie Checking Payment Confirmed', 'woo-genie-checking') );
						}

						$redirect_url =  $order->get_checkout_order_received_url();
						$this->custom_thank_you($redirect_url);

				}elseif ($result_value == 1 ){
						$result = "Unknown Error";
						if($status == 'expire'){
								$order->update_status( 'failed', __( 'Payment expired', 'woo-genie-checking' ) );
						}elseif($status == 'decline'){
								$order->update_status( 'failed', __( 'Payment failed', 'woo-genie-checking' ) );
						}elseif($status == 'cancel'){
								$order->update_status( 'failed', __( 'Payment cenceled', 'woo-genie-checking' ) );
						}
						$redirect_url =  $order->get_checkout_order_received_url();
						$this->custom_thank_you($redirect_url);
				}
		}


    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
		public function process_payment( $order_id ) {
			global $woocommerce;
			$order = wc_get_order( $order_id );
			$order->update_status( $this->default_order_status, __( 'Pending Genie Checking Payment', 'woo-genie-checking' ) );

			$order_id    = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->id          : $order->get_id();
			$userID      = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->user_id     : $order->get_user_id();
			$order_total = (true === version_compare(WOOCOMMERCE_VERSION, '3.0', '<')) ? $order->order_total : $order->get_total();

			$customer_order     = new WC_Order( $order_id );
			$items = $customer_order->get_items();
			$description = '';
			foreach ( $items as $item ) {
					$description .= $item['name'].',';
			}
			$description = rtrim($description, ',');

			$environment        = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
			$environment_url    = ( "FALSE" == $environment ) ? $this->liveurl : $this->testurl;
			$amount_item        = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() ) );
			$carttotal          = $woocommerce->cart->subtotal;
			$cashbox            = $this->cashbox_number;
			//$amount             = $carttotal;
			$orderid            = $order_id;
			$description        = $description;
			$amount             = $order->get_total();
			$cellphone          = $customer_order->get_billing_phone();
			$email              = $customer_order->get_billing_email();
			$firstname          = $customer_order->get_billing_first_name();
			$lastname           = $customer_order->get_billing_last_name();
			$company            = $customer_order->get_billing_company();
			$country            = $customer_order->get_billing_country();
			$address1           = $customer_order->get_billing_address_1();
			$address2           = $customer_order->get_billing_address_2();
			$city               = $customer_order->get_billing_city();
			$state              = $customer_order->get_billing_state();
			$zip                = $customer_order->get_billing_postcode();
			$expiremin  				= '60';
			$get_callback_url   = home_url('/?geniechecking_callback=');
			$param              = sprintf("cashbox=%s&amount=%s&orderid=%s&cellphone=%s&email=%s&firstname=%s&lastname=%s&company=%s&country=%s&address1=%s&address2=%s&city=%s&state=%s&zip=%s&description=%s&expiremin=%s&plugin=%s&ReturnURL=%s",$cashbox,$amount,$orderid,$cellphone,$email,$firstname,$lastname,$company,$country,$address1,$address2,$city,$state,$zip,$description,$expiremin,'woocommerce',$get_callback_url);
			$urlenc             = $this->encrypt_pol($param);
			$new_request        = $environment_url . $urlenc;

			return array(
					'result' => 'success',
					'redirect' => $new_request
			);

		}


}
