<?php

/**
 * Class WC_Gateway_Bitexpay
 * @description: The WC_Gateway_Bitexpay class for payment
*/
final class WC_Gateway_Bitexpay extends WC_Payment_Gateway {

    /**
     * Constructor
     * @description: Init params of plugin bitexpay gateway payment
    */
    public function __construct(){
        
        $this->id = 'bitexpay_gateway';
        $this->icon = plugins_url().'/bitexpay-wp/src/assets/logo.jpg';
        $this->has_fields = true;
        // $this->title = __('Bitexpay', 'woocommerce');
        $this->method_title = __( 'Bitexpay', 'woocommerce' );;
        $this->method_description = 'Bitexpay Payment Gateway for WooCommerce whit crytocurrency';
        
        $this->init_form_fields();
        $this->init_settings();  
        
        $this->title = $this->get_option('title');
        $this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('description');

        //process settings with parent method
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
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
                'default' => __( 'Pay with Bitcoin, Tron, or other altcoins via Bitexpay.com', 'woocommerce' )
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
        $order->reduce_order_stock();

        //if the payment processing was successful, return an array with result as success and redirect to the order-received/thank you page.
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url( $order )
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
}