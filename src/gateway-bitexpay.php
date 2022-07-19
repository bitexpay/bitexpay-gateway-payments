<?php

/**
 * Class WC_Gateway_Bitexpay
 * @description: The WC_Gateway_Bitexpay class for payment
*/
final class WC_Gateway_Bitexpay extends WC_Payment_Gateway {

    private $bitexpayAddresss = "http://pay.bitexblock.com/";

    /**
     * Constructor
     * @description: Init params of plugin bitexpay gateway payment
    */
    public function __construct(){
        
        $this->id = 'bitexpay_gateway';
        $this->icon = plugins_url().'/bitexpay-wp/src/assets/logo.jpg';
        $this->has_fields = true;
        $this->method_title = __( 'Bitexpay', 'woocommerce' );
        $this->method_description = __('Bitexpay Payment Gateway for WooCommerce whit crytocurrency', 'woocommerce' );
        
        $this->init_form_fields();
        $this->init_settings();  
        
        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('description');
        $this->api_key = $this->get_option('api_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->domain = $this->get_option('domain');
        $this->merchant_id = $this->get_option('merchant_id');

        // Logs
        $this->log = new WC_Logger();

        add_action('woocommerce_receipt_'. $this->id, array( $this, 'receipt_page'));

        //process settings with parent method
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
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
                'title' => __( 'Settings merchant', 'woocommerce' ),
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

        // var_dump($order);
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

        $bitexpayArg = $this->get_bitexpay_args($order);
        return $this->get_return_url( $order );
    }

    public function get_bitexpay_args($order){

        $order_id = $order->get_id();
        $data = $order->get_data();

        if ( in_array( $order->get_billing_country(), array( 'US','CA' ) ) ) {
            $order->set_billing_phone(str_replace( array( '( ', '-', ' ', ' )', '.' ), '', $order->get_billing_phone() ));
        }

        $bitexpay_args = [

        ];
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