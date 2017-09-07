<?php
/*
	Plugin Name: Paymexx WooCommerce Payment Gateway
	Plugin URI: https://developers.paymexx.com/1.2/plugins
	Description: Paymexx WooCommerce Payment Gateway allows you to accept local and International payment via Verve Card, MasterCard & Visa Card.
	Version: 1.2.0
	Author: Micheal Ojemoron
	License:           GPL-2.0+
 	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) )
	exit;

add_action( 'plugins_loaded', 'mk_wc_paymexx_init', 0 );

function mk_wc_paymexx_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	/**
 	 * Gateway class
 	 */
	class WC_Mk_Paymexx_Gateway extends WC_Payment_Gateway {

		public function __construct() {

			$this->id 					= 'mk_paymexx_gateway';
    		$this->icon 				= apply_filters( 'woocommerce_paymexx_icon', plugins_url( 'assets/images/paymexx_logo.png' , __FILE__ ) );
			$this->has_fields 			= false;
			$this->order_button_text    = 'Make Payment';
			$this->notify_url        	= WC()->api_request_url( 'WC_Mk_Paymexx_Gateway' );
        	$this->method_title     	= 'Paymexx';
        	$this->method_description  	= 'Payment Methods Accepted: MasterCard, Visa and Verve Cards';

			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title 				= $this->get_option( 'title' );

			$this->description 			= $this->get_option( 'description' );
            $this->testmode             = $this->get_option( 'testmode' ) === 'yes' ? true : false;

             $this->merchant_id              = $this->get_option( 'merchant_id' );
			$this->shop_name             = $this->get_option( 'shop_name' );

            $this->test_secret_key  	= $this->get_option( 'test_secret_key' );
            $this->live_secret_key  	= $this->get_option( 'live_secret_key' );

            $this->test_application_key  	= $this->get_option( 'test_application_key' );
            $this->live_application_key  	= $this->get_option( 'live_application_key' );

			$this->secret_key      		= $this->testmode ? $this->test_secret_key : $this->live_secret_key;
			$this->application_key      	= $this->testmode ? $this->test_application_key : $this->live_application_key;

			//Actions
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			add_action( 'woocommerce_receipt_mk_paymexx_gateway', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_mk_paymexx_gateway', array( $this, 'verify' ) );

			// Check if the gateway can be used
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}

		}


		/**
	 	* Check if the store curreny is set to NGN
	 	**/
		public function is_valid_for_use() {

			if( ! in_array( get_woocommerce_currency(), array( 'NGN' ) ) ) {
				$this->msg = 'Paymexx doesn\'t support your store currency, set it to Nigerian Naira &#8358; <a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=general">here</a>';
				return false;
			}

			return true;

		}


		/**
		 * Check if this gateway is enabled
		 */
		public function is_available() {

			if ( $this->enabled == "yes" ) {

				if ( ! ( $this->test_secret_key || $this->live_secret_key ) ) {
					return false;
				}

				return true;

			}

			return false;

		}


        /**
         * Admin Panel Options
         **/
        public function admin_options() {

            echo '<h3>Paymexx</h3>';
            echo '<p>Paymexx WooCommerce Payment Gateway allows you to accept local and International payment on your WooCommerce store via MasterCard, Visa and Verve Cards.</p>';
            echo '<p>To open a Paymexx merchant account click <a href="https://paymexx.com/merchant/register" target="_blank">here</a>';

			if ( $this->is_valid_for_use() ) {

	            echo '<table class="form-table">';
	            $this->generate_settings_html();
	            echo '</table>';

            } else {	 ?>

				<div class="inline error"><p><strong>Paymexx Payment Gateway Disabled</strong>: <?php echo $this->msg ?></p></div>

			<?php }

        }


	    /**
	     * Initialise Gateway Settings Form Fields
	    **/
		function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> 'Enable/Disable',
					'type' 			=> 'checkbox',
					'label' 		=> 'Enable Paymexx Payment Gateway',
					'description' 	=> 'Enable or disable the gateway.',
            		'desc_tip'      => true,
					'default' 		=> 'yes'
				),
				'title' => array(
					'title' 		=> 'Title',
					'type' 			=> 'text',
					'description' 	=> 'This controls the title which the user sees during checkout.',
        			'desc_tip'      => false,
					'default' 		=> 'Paymexx'
				),
				'description' => array(
					'title' 		=> 'Description',
					'type' 			=> 'textarea',
					'description' 	=> 'This controls the description which the user sees during checkout.',
					'default' 		=> 'Payment Methods Accepted: MasterCard, VisaCard, Verve Card & eTranzact'
				),
				'merchant_id' => array(
					'title'       => 'Merchant Id',
					'type'        => 'text',
					'description' => 'Enter your Merchant ID here.',
					'default'     => ''
				),
                'shop_name' => array(
					'title'       => 'Shop Name',
					'type'        => 'text',
					'description' => 'Enter your Shop Name here.',
					'default'     => ''
				),
				'test_secret_key' => array(
					'title'       => 'Test Secret Key',
					'type'        => 'text',
					'description' => 'Enter your Test Secret Key here.',
					'default'     => ''
				),

				'live_secret_key' => array(
					'title'       => 'Live Secret Key',
					'type'        => 'text',
					'description' => 'Enter your Live Secret Key here',
					'default'     => ''
				),
				'test_application_key' => array(
					'title'       => 'Test Application Key',
					'type'        => 'text',
					'description' => 'Enter your Test Application Key here.',
					'default'     => ''
				),
				'live_application_key' => array(
					'title'       => 'Live Application Key',
					'type'        => 'text',
					'description' => 'Enter your Live Application Key here.',
					'default'     => ''
				),
				'testmode' => array(
					'title'       		=> 'Gateway Environment',
					'type'        		=> 'checkbox',
					'label'       		=> 'Enable Test Mode',
					'default'     		=> 'no',
					'description' 		=> 'Gateway Environment enables you to test payments before going live. <br />If you ready to start receiving payment on your site, kindly uncheck this.',
				)
			);

		}


		/**
		 * Outputs scripts used for Paymexx payment
		 */
		public function payment_scripts() {

			if ( ! is_checkout_pay_page() ) {
				return;
			}
			wp_enqueue_script( 'mk_paymexx', 'https://paymexx.com/gateway/1.2/paymexx.min.js', array( 'jquery' ), '1.0.0', true );

			wp_enqueue_script( 'wc_paymexx', plugins_url( 'assets/js/paymexx.js', __FILE__ ), array( 'mk_paymexx' ), null, true );

			if ( is_checkout_pay_page() && get_query_var( 'order-pay' ) ) {

				$order_key 			= urldecode( $_GET['key'] );
				$order_id  			= absint( get_query_var( 'order-pay' ) );

				$order        		= wc_get_order( $order_id );

				$email  			= method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
				$firstname	        = method_exists( $order, 'get_billing_first_name' ) ? $order->get_billing_first_name(): $order->get_billing_first_name;
				$lastname	        = method_exists( $order, 'get_shipping_last_name' ) ? $order->get_billing_last_name() : $order->get_billing_last_name;
				$billing_address_1 	= method_exists( $order, 'get_billing_address_1' ) ? $order->get_billing_address_1() : $order->billing_address_1;
				$billing_address_2 	= method_exists( $order, 'get_billing_address_2' ) ? $order->get_billing_address_2() : $order->billing_address_2;
				$city  				= method_exists( $order, 'get_billing_city' ) ? $order->get_billing_city() : $order->billing_city;
				$country  			= method_exists( $order, 'get_billing_country' ) ? $order->get_billing_country() : $order->billing_country;

				$amount 			= $order->get_total() ;
				$address 			= $billing_address_1 . ' ' . $billing_address_2;

				$description 		= 'Payment for Order #' . $order_id;

	            $the_order_id 		= method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
	            $the_order_key 		= method_exists( $order, 'get_order_key' ) ? $order->get_order_key() : $order->order_key;

                if ( $the_order_id == $order_id && $the_order_key == $order_key ) {
					$paymexx_params['key'] 			= $this->public_key;
					$paymexx_params['firstname'] 			= $firstname;
					$paymexx_params['lastname'] 			= $lastname;
					$paymexx_params['shop_name'] 			= $this->shop_name;
					$paymexx_params['merchant_id'] 			= $this->merchant_id;
                    $paymexx_params['email'] 			= $email;
                    $paymexx_params['address'] 		= $address;
                    $paymexx_params['city'] 			= $city;
                    $paymexx_params['country'] 		= $country;
                    $paymexx_params['amount']  		= $amount;
                    $paymexx_params['order_id']  		= $order_id;
                    $paymexx_params['txn_ref']  		= strtoupper(substr($this->shop_name,0,3)).time();
                    $paymexx_params['description']	= $description;
                    $paymexx_params['currency']  		= 234;
                    $paymexx_params['env']			= $this->testmode ? 'test': 'live';
                    $paymexx_params['return_url']			=  get_home_url().'/wc-api/WC_Mk_Paymexx_Gateway/?order_no='.$order_id;

				}

			}
			wp_localize_script( 'wc_paymexx', 'wc_paymexx_params', $paymexx_params );

		}


	    /**
	     * Process the payment and return the result
	    **/
		public function process_payment( $order_id ) {

			$order 			= wc_get_order( $order_id );
			return array(
	        	'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
	        );

		}


	    /**
	     * Output for the order received page.
	    **/
		public function receipt_page( $order_id ) {

			//$order = wc_get_order( $order_id );


                $order_key 			= urldecode( $_GET['key'] );
                $order_id  			= absint( get_query_var( 'order-pay' ) );

                $order        		= wc_get_order( $order_id );

                $email  			= method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : $order->billing_email;
                $billing_address_1 	= method_exists( $order, 'get_billing_address_1' ) ? $order->get_billing_address_1() : $order->billing_address_1;
                $billing_address_2 	= method_exists( $order, 'get_billing_address_2' ) ? $order->get_billing_address_2() : $order->billing_address_2;
                $city  				= method_exists( $order, 'get_billing_city' ) ? $order->get_billing_city() : $order->billing_city;
                $country  			= method_exists( $order, 'get_billing_country' ) ? $order->get_billing_country() : $order->billing_country;

                $amount 			= $order->get_total() * 100;
                $address 			= $billing_address_1 . ' ' . $billing_address_2;

                $description 		= 'Payment for Order #' . $order_id;

                $the_order_id 		= method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
                $the_order_key 		= method_exists( $order, 'get_order_key' ) ? $order->get_order_key() : $order->order_key;

			echo '<p>Thank you for your order, please click the button below to pay with debit/credit card using Paymexx.</p>';

			echo '<div id="paymexx_form"><form id="order_review"></form><button class="button alt" onclick="payWithPaymexx()">Pay Now</button> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">Cancel order &amp; restore cart</a></div>
			';
		}


		/**
		 * Verify a payment
		**/
		public function verify() {
        if( isset(  $_GET['transaction_ref'] ) ) {

            $verify_url 	= $this->testmode?'https://paymexx.com/sandbox/api/transaction/':'https://paymexx.com/v1/api/transaction/';

            $order_id 		= (int) $_GET['order_no'];
            $order 			= wc_get_order( $order_id );
            $order_total	= $order->get_total();
            $txn_ref =$_GET['transaction_ref'];


            $body = array(
                'reference' 			=> $txn_ref,
                'amount'			=> $order_total,
                'key'			=> $this->testmode ? $this->test_secret_key : $this->live_secret_key,
            );

            $args = array(
                'body'		=>  $body ,
                'timeout'	=> 60,
                'method'	=> 'GET'
            );

            $request = wp_remote_get( $verify_url, $args );


            if ( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {

                $paymexx_response = json_decode( wp_remote_retrieve_body( $request ) );
//                print_r($paymexx_response->data);exit;

                $amount_paid 		= $paymexx_response->data->amount;
                $transaction_id		= $paymexx_response->id;

//                do_action( 'tbz_wc_simplepay_after_payment', $simplepay_response );

                if($paymexx_response->status ) {

                    // check if the amount paid is equal to the order amount.
                    if( $paymexx_response->data->status !== 'successful' ) {

                        //Update the order status
                        $order->update_status( 'on-hold', '' );

                        add_post_meta( $order_id, '_transaction_id', $transaction_id, true );

                        //Error Note
                        $notice = 'Thank you for shopping with us.<br />The payment was successful, but the amount paid is not the same as the order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';

                        $notice_type = 'notice';

                        //Add Admin Order Note
                        $order->add_order_note( 'Look into this order. <br />This order is currently on hold.<br />Reason: Amount paid is less than the order amount.<br />Amount Paid was &#8358;'. $amount_paid .' while the order amount is &#8358;'. $order_total .'<br />Paymexx Transaction ID: '.$transaction_id );

                        // Reduce stock levels
                        $order->reduce_order_stock();

                        wc_add_notice( $notice, $notice_type );

                    } else {

                        $order->payment_complete( $transaction_id );

                        $order->add_order_note( sprintf( 'Payment via Paymexx successful (Transaction ID: %s)', $transaction_id ) );
                    }

                    wc_empty_cart();

                    wp_redirect( $this->get_return_url( $order ) );

                    exit;

                } else {

                    wp_redirect( wc_get_page_permalink( 'checkout' ) );

                    exit;
                }

            }
        }

        wp_redirect( wc_get_page_permalink( 'checkout' ) );

        exit;

      }
    }
	function mk_wc_add_paymexx_gateway( $methods ) {

		$methods[] = 'WC_Mk_Paymexx_Gateway';
		return $methods;

	}
	add_filter('woocommerce_payment_gateways', 'mk_wc_add_paymexx_gateway' );


	/**
	* Add Settings link to the plugin entry in the plugins menu
	**/
	function mk_paymexx_plugin_action_links( $links, $file ) {

	    static $this_plugin;

	    if ( ! $this_plugin ) {

	        $this_plugin = plugin_basename( __FILE__ );

	    }

	    if ( $file == $this_plugin ) {

	        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_mk_paymexx_gateway">Settings</a>';
	        array_unshift($links, $settings_link);

	    }

	    return $links;

	}
	add_filter( 'plugin_action_links', 'mk_paymexx_plugin_action_links', 10, 2 );


	/**
 	* Display the testmode notice
 	**/
	function mk_wc_paymexx_testmode_notice() {

		$paymexx_settings = get_option( 'woocommerce_mk_paymexx_gateway_settings' );

		$testmode 			     = $paymexx_settings['testmode'] === 'yes' ? true : false;
        $test_secret_key 	     = $paymexx_settings['test_secret_key'];
        $live_secret_key  	     = $paymexx_settings['live_secret_key'];

        $test_application_key  	= $paymexx_settings['test_application_key'];
        $live_application_key  	= $paymexx_settings['live_application_key'];

        $test_secret_key      	= $testmode ? $test_secret_key : $live_secret_key;
        $live_secret_key      	= $testmode ? $live_secret_key : $test_secret_key;

		if ( $testmode ) {
	    ?>
		    <div class="update-nag">
                Paymexx testmode is still enabled. Click <a href="<?php echo get_bloginfo('wpurl') ?>/wp-admin/admin.php?page=wc-settings&tab=checkout&section=mk_paymexx_gateway">here</a> to disable it when you want to start accepting live payment on your site.
		    </div>
	    <?php
		}
		// Check required fields
		if (  !( $test_secret_key || $live_secret_key ) ) {
			echo '<div class="error"><p>' . sprintf( 'Please enter your Paymexx test or live keys <a href="%s">here</a> to be able to use the Paymexx WooCommerce plugin.', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=mk_paymexx_gateway' ) ) . '</p></div>';
		}

	}
	add_action( 'admin_notices', 'mk_wc_paymexx_testmode_notice' );

}