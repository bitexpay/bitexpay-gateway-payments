<?php

/**
 * Class WC_Gateway_Bitexpay
 * @description: The WC_Gateway_Bitexpay class for payment
*/
final class WC_Gateway_Bitexpay extends WC_Payment_Gateway {

    // private $bitexpayAddresss = "http://pay.bitexblock.com/#/auth/login_/";
    private $bitexpayAddresss = "http://localhost:4200/#/auth/login_/";
    private $ipn_url;

    /**
     * Constructor
     * @description: Init params of plugin bitexpay gateway payment
    */
    public function __construct(){
        
        $this->id = 'bitexpay';
        $this->icon = plugins_url().'/bitexpay-wp/src/assets/logo.jpg';
        $this->has_fields = true;
        $this->method_title = __( 'Bitexpay', 'woocommerce' );
        $this->method_description = __('Bitexpay Payment Gateway for WooCommerce whit crytocurrency', 'woocommerce' );
        $this->ipn_url   = add_query_arg( 'wc-api', 'WC_Gateway_Bitexpay', home_url( '/' ) );
        
        $this->init_form_fields();
        $this->init_settings();  

        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('description');
        $this->api_key = $this->get_option('api_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->domain = $this->get_option('domain');
        $this->send_shipping = $this->get_option( 'send_shipping' );
        $this->monetize = $this->get_option( 'monetize' ) == 'yes' ? true : false;
        $this->merchant_id = $this->get_option('merchant_id');
        $this->simple_total = $this->get_option( 'simple_total' ) == 'yes' ? true : false;

        // Logs
        $this->log = new WC_Logger();

        //Page of thank you
        add_action('woocommerce_receipt_'. $this->id, array( $this, 'receipt_page'));

        //process settings with parent method
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));

        // Payment listener/API hook
        add_action( 'woocommerce_api_wc_gateway_' .$this->id, array( $this, 'check_ipn_response' ) );
    }

    /**
     * init_form_fields
     * @description: Init for fields of plugin for admin settings
    */
    public function init_form_fields(){

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Bitexpay Payment Gateway', 'woocommerce'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => 'This controls the payment method title',
                'default' => __( 'Bitexpay gateway payments', 'woocommerce' ),
                'desc_tip' => true
            ],
            'description' => [
                'title' => __( 'Description', 'woocommerce' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                'default' => __( 'Pay with Bitcoin, Tron, or other altcoins via Bitexpay.com', 'woocommerce' ),
                'desc_tip' => true
            ],
            'credentials' => [
                'title' => __( 'Credentials', 'woocommerce' ),
                'type' => 'title',
                'description' => ''
            ],
            'api_key' => [
                'title' => __('Api Key', 'woocommerce' ),
                'type' => 'text',
                'description' => __( 'Api key provided by bitexpay technology', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ],
            'secret_key' => [
                'title' => __('Secret Key', 'woocommerce'),
                'type' => 'password',
                'default' => '',
                'description' => __( 'Secret key provided by bitexpay technology', 'woocommerce'),
                'desc_tip' => true
            ],
            'domain' => [
                'title' => 'Domain',
                'type' => 'text',
                'default' => '',
                'description' => __( 'Domain allowe d to communicate with the api ', 'woocommerce'),
                'desc_tip' => true
            ],
            'setting' => [
                'title' => __( 'Settings merchant and Order', 'woocommerce' ),
                'type' => 'title',
                'description' => ''
            ],
            'merchant_id' => [
                'title' => __('Merchant ID', 'woocommerce'), 
                'type' => 'text',
                'description' => __('Merchant id of user Bitexpay', 'woocommerce'),
                'default' => '',
                'desc_tip' => true
            ],
            'monetize' => [
                'title' => __('Monetize', 'woocommerce'),
                'type' => 'checkbox',
                'description' => __('Receive the payment converted in usdt or in the cryptocurrency that the client selects', 'woocommerce'),
                'label' => __('Yes/Not', 'woocommerce'),
                'default' => 'not',
                'desc_tip' => true
            ],
            'send_shipping' => [
                'title' => __( 'Shipping Info', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Shipping Information on Checkout page', 'woocommerce' ),
                'default' => 'yes',
                'description' => __( 'Enable Shipping Information on Checkout page', 'woocommerce' ),
                'desc_tip' => true
            ],
            'simple_total' => [ 
                'title' => __( 'Compatibility Mode', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( "This may be needed for compatibility with certain addons if the order total isn't correct.", 'woocommerce' ),
                'default' => ''
            ]
        ];
    }

    /**
     * process_payment
     * @description: Method for processing payment of client
    */
    public function process_payment($order_id){
        
        global $woocommerce;
        $order = new WC_Order( $order_id );

        $order->update_status('processing','Additional data like transaction id or reference number');

        //once the order is updated clear the cart and reduce the stock
        $woocommerce->cart->empty_cart();

        //if the payment processing was successful, return an array with result as success and redirect to the order-received/thank you page.
        return array(
            'result' => 'success',
            'redirect' => $this->generate_bitexpay_url( $order )
        );
    }

    public function payment_fields(){
        ?>
        <fieldset>
            <p class="form-row form-row-wide">
                <?php echo esc_attr($this->description); ?>
            </p>                        
            <div class="clear"></div>
        </fieldset>
        <?php
    }

    /**
     * Generate the bitexpay button link
     *
     * @access public
     * @param mixed $order_id
     * @return string
    */
    public function generate_bitexpay_url( $order ){

        if($order->get_status() != 'completed' && get_post_meta($order->get_id(), '_bitexpay_payments_complete', true) != 'Yes'){
            $order->update_status('pending', 'Customer is being redirected to Bitexpay...');
        }

        $args = $this->get_bitexpay_args($order);
        $query = http_build_query( $args, '', '&' );
        $signature = hash_hmac('sha512', $query, $this->secret_key);
        $bitexpayUrl = $this->bitexpayAddresss . 'wordpress/filter?' . $query. '&signature=' . $signature;

        return $bitexpayUrl;
    }

    /**
     *  get_bitexpay_args
     *  @description: Function fot get args 
    */
    public function get_bitexpay_args($order){

        $order_id = $order->get_id();
        $data = $order->get_data();

        if ( in_array( $data['billing']['country'], array( 'US','CA' ) ) ) {
            $order->set_billing_phone(str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $data['billing']['phone'] ));
        }

        $bitexpay_args = [
            'merchant' 		=> $this->merchant_id,
            'currency' 		=> $data['currency'],
            'reset' 		=> 1,
            'success_url' 	=> $this->get_return_url( $order ),
            'cancel_url'	=> esc_url_raw($order->get_cancel_order_url_raw()),

            // Order key + ID
            'invoice'		=> 'wc-'. $order->get_order_number(),
            'custom' 		=> serialize([ $order_id, $order->get_order_key() ]),

            // IPN
            'ipn_url'		=> esc_url_raw($this->ipn_url),

            // Billing Address info
            'first_name'	=> urlencode($data['billing']['first_name']),
            'last_name'		=> urlencode($data['billing']['last_name']),
            'email'			=> trim($data['billing']['email']),

            //Create order in Bitexpay
            'accesskey'     => $this->api_key,
            'nonce'         => time(),
            'usd'           => $order->get_total(),
            "tipo"          => "private",
            "monetizar"     => $this->monetize,
            "enviarCorreo"  => 'S',
            "descripcion"   => urlencode('Pay via woocomerce whit wordpress technology'),
            "tipo_fee_monetizar"=> 'owner'
        ];

        if($this->send_shipping == 'yes'){
            $bitexpay_args = array_merge($bitexpay_args, [
                'want_shipping' => 1,
                'company'				=> urlencode(trim($data['billing']['company'])),
                'address1'				=> urlencode(trim($data['billing']['address_1'])),
                'address2'				=> urlencode(trim($data['billing']['address_2'])),
                'city'					=> urlencode(trim($data['billing']['city'])),
                'state'					=> urlencode(trim($data['billing']['state'])),
                'zip'					=> urlencode(trim($data['billing']['postcode'])),
                'country'				=> urlencode(trim($data['billing']['country'])),
                'phone'					=> urlencode(trim($data['billing']['phone']))
            ]);
        }else{
            $bitexpay_args['want_shipping'] = 0;
        }

        $bitexpay_args['item_name'] = 'order-' . $order->get_order_number();
        $bitexpay_args['quantity']  = count($order->get_items());

        if($this->simple_total){
            $bitexpay_args['amountf'] 	= number_format( $order->get_total(), 8, '.', '' );
            $bitexpay_args['taxf'] 		= 0.00;
            $bitexpay_args['shippingf']	= 0.00;
        }else
        if(wc_tax_enabled() && wc_prices_include_tax()){
            $bitexpay_args['amountf'] 		= number_format( $order->get_total() - $order->get_total_shipping() - $order->get_shipping_tax(), 8, '.', '' );
            $bitexpay_args['taxf'] 			= 0.00;
            $bitexpay_args['shippingf']	    = number_format( $order->get_total_shipping() + $order->get_shipping_tax() , 8, '.', '' );
        }else{
            $bitexpay_args['amountf'] 		= number_format( $order->get_total() - $order->get_total_shipping() - $order->get_total_tax(), 8, '.', '' );
            $bitexpay_args['shippingf']		= number_format( $order->get_total_shipping(), 8, '.', '' );
            $bitexpay_args['taxf']			= number_format( $order->get_total_tax(), 8, '.', '' );
        }

        return $bitexpay_args;
    }

    public function check_ipn_response(){
        @ob_clean();

        
        if(isset($_GET['status']) && ($_GET['status'] == 'process payment' || $_GET['status'] == 'incomplete')){
            
            if(! empty($_GET) && $this->check_ipn_and_signature($_GET['signature'])){
                
                parse_str($_GET['params'], $params);
                
                if(isset($_GET['txid']) && !empty($_GET['txid'])){
                    $this->request_success_order($params, $_GET['status'], $_GET['status_code'], $_GET['txid']);
                }else
                wp_die("Bitexpay Error: not send txid");
            }else{
                wp_die('Bitexpay Error signature and ipn url', 'Bitexpay Gateway Payment');
            }
        } else{
            wp_die('Bitexpay Error whit status fail', 'Bitexpay Gateway Payment');
        }

    }

    /**
     * buildingHmac
     * 
     * @description  Separate the parameter string and decode and re-encode in PHP_QUERY_RFC1738 format, to create 
     * the hmac built from the beginning, since passing through the browser url changes the character encoding
     * @type private
     * @return hcmac
     */
    private function buildingHmac($params){
       
        $arrayUrl = ["success_url", "cancel_url", "ipn_url", "custom", "email"];
        $arrayParams = explode('&', $params);
        // var_dump($arrayParams);  

        for($i = 0; $i < count($arrayParams); $i++){
            foreach($arrayUrl as $url){
                if( str_contains ( $arrayParams[$i] , $url )){
                    $parts = explode('=',$arrayParams[$i]);
                    $parts[1] = rawurlencode(rawurldecode($parts[1]));
                    $arrayParams[$i] = implode('=', $parts);
                }
            }
        }

        $paramsBuilding = implode('&', $arrayParams);
        // var_dump($paramsBuilding);  
        
        $hmac = hash_hmac('sha512', $paramsBuilding, $this->secret_key);
        // var_dump($hmac); 
        // die();
        return $hmac;
    }

    /**
     * check_ipn_and_signature
     * 
     * @description Validate ipn_url
    */
    public function check_ipn_and_signature($signature){

        $error  = "";   $success = false;
        if(isset($signature)){
            if(isset($_GET['params'])){
                $hmac = $this->buildingHmac($_GET['params']);
       
                if($signature === $hmac){
                    $success = true;
                }else{
                    $error = "HMAC signature does not match";
                }
            }else{
                $error = "Not sended params";
            }
        }else{
            $error = "Not sended signature in headers";
        }

        if($success){
            $params = [];
            parse_str($_GET['params'], $params);

            if(isset($params['invoice']) && isset($params['custom'])){
                $order = $this->get_order_bitexpay($params);
            }

            if($order != false){
                if($params['merchant'] == $this->merchant_id){
                    if($params['currency'] == $order->get_currency()){
                        if($params['usd'] >= $order->get_total()){
                            print "IPN check OK\n";
                            return true;
                        }else
                        $error = "Amount received is less than the total!";
                    }else{
                        $error = "Original currency doesn't match!";
                    }
                }else
                $error = "Merchant ID doesn't match!";
            }else{
                $error = 'Not found info of order '. $params['invoice'];
            }

            if($order){
                $order->update_status('on-hold', 'Bitexpay Error ipn: '. $error);
            }

            die('IPN Error: '. $error);
            return false;
        }
    }

    /**
     * get_order_bitexpay
     * 
     * @description Get order by parms ulr sended by user in the gateway bitexpay
     * @return order
    */
    public function get_order_bitexpay($params){
        $custom = maybe_unserialize( stripslashes_deep($params['custom']) );

        if(is_numeric($custom)){
            $order_id = (int) $custom;
            $order_key = $params['invoice'];
        }elseif(is_string( $custom )){
            $order_id = (int) str_replace( 'wc-', '', $custom );
            $order_key = $custom;
        }else{
            list( $order_id, $order_key ) = $custom;
        }

        $order = wc_get_order( $order_id );
       
        if($order == false || $order->get_order_key() !== $order_key){
            return false;
        }

        return $order;
    }

    public function request_success_order($params, $statusBitexpay, $statusCode, $txid){

        if(isset($params['invoice']) && isset($params['custom'])){
            $order = $this->get_order_bitexpay($params);

            if($order == false){
                die('IPN Error, Could not find order information: ' . $params['invoice']);
            }

            $this->log->add( 'bitexpay', 'Order #'.$order->get_id().' payment status: ' . $statusBitexpay );
            $order->add_order_note('Bitexpay Payment Status: ' . $statusBitexpay );

            if($order->get_status('completed') && get_post_meta( $order->get_id(), '_bitexpay_payments_complete', true) != 'Yes'){
                if(!empty($params['first_name'])){
                    update_post_meta( $order->get_id(), 'Payer first name', $params['first_name'] );
                }

                if(!empty($params['last_name'])){
                    update_post_meta( $order->get_id(), 'Payer last name', $params['last_name'] );
                }

                if(!empty($params['email'])){
                    update_post_meta( $order->get_id(), 'Payer email', $params['email'] );
                }

                if(!empty($txid)){
                    update_post_meta( $order->get_id(), 'txid', $txid );
                }
                
                // 0 Not payed, 1 => Complete, 2 Incomplete, 3 => Cancelado
                if($statusBitexpay == 'process payment' && $statusCode == '1'){
                    print("Orde complete\n");
                    update_post_meta( $order->get_id(), '_bitexpay_payments_complete', 'Yes');
                    $order->add_order_note('Bitexpay Payment done ' );
                    $order->payment_complete();

                    return json_encode(['success' => 'ok' ]);
                }else 
                if($statusCode == '2'){
                    print "Marking pending\n";
                    $order->add_order_note('Bitexpay Payment incomplete ' );
                    $order->update_status('pending', 'Bitexpay Payment pending: ' . $statusBitexpay);

                    return json_encode(['success' => 'ok' ]);
                }else
                if($statusCode == '3'){
                    print "Marking cancelled\n";
                    $order->update_status('cancelled', 'Bitexpay Payment pending: ' . $statusBitexpay);
                    mail( get_option( 'admin_email' ), "Order cancelled " . $order->get_order_number(), $statusBitexpay );
                }
            }

            die("IPN OK");
        }
    }

    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
    */
    public function receipt_page($oder){

        echo '<p>'.__( 'Thank you for your order, please click the button below to pay with Bitexpay.', 'woocommerce' ).'</p>';
    }
}