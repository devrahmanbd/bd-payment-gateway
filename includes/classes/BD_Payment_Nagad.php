<?php

class bd_Payment_Nagad extends WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id                     = 'bd_nagad';
        $this->title                 = $this->get_option('bd_nagad_title', 'Payment With Nagad');
        $this->description             = $this->get_option('bd_nagad_description', 'Verify & Complete Your Payment');
        $this->method_title         = esc_html("Nagad Gateway", "bd-payment-gateway-domain");
        $this->method_description     = esc_html("Integrate Nagad for seamless payments and lower charges.
Secure verification with Transaction ID and Customer Billing Number", "bd-payment-gateway-domain");
        $this->bd_nagad_number = $this->get_option('bd_nagad_number');
        $this->bd_nagad_account_type     = $this->get_option('bd_nagad_account_type');
        $this->bd_nagad_charge = $this->get_option('bd_nagad_charge');
        $this->cdd_nagad_order_status = $this->get_option('cdd_nagad_order_status');
        $this->bd_nagad_instructions = $this->get_option('bd_nagad_instructions');
        $this->has_fields = true;

        $this->bd_nagad_payment_options_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_filter('woocommerce_thankyou_order_received_text', array($this, 'bd_nagad_thankyou_page_function'));
        add_action('woocommerce_email_before_order_table', array($this, 'bd_nagad_email_instructions_function'), 15, 5);
    }

    public function bd_nagad_payment_options_fields()
    {
        $this->form_fields = array(
            'enabled'     =>    array(
                'title'        => esc_html('Enable/Disable', "bd-payment-gateway-domain"),
                'type'         => 'checkbox',
                'default'    => 'yes'
            ),
            'bd_nagad_title'     => array(
                'title'     => esc_html('Title', "bd-payment-gateway-domain"),
                'type'         => 'text',
                'default'    => esc_html('Payment With Nagad', "bd-payment-gateway-domain")
            ),
            'bd_nagad_description' => array(
                'title'        => esc_html('Description', "bd-payment-gateway-domain"),
                'type'         => 'textarea',
                'default'    => esc_html('Verify & Complete Your Payment', "bd-payment-gateway-domain"),
                'desc_tip'    => true
            ),
            'bd_nagad_instructions' => array(
                'title'           => esc_html('Thank you page message', "bd-payment-gateway-domain"),
                'type'            => 'textarea',
                'description'     => esc_html('Thank you page message that will be added to the thank you page and emails.', "bd-payment-gateway-domain"),
                'default'         => esc_html('Thanks for being with Marvel Gadget', "bd-payment-gateway-domain"),
                'desc_tip'        => true
            ),
            'bd_nagad_number'    => array(
                'title'            => esc_html('Nagad Number', "bd-payment-gateway-domain"),
                'description'     => esc_html('Add a Nagad mobile no which will be shown in checkout page', "bd-payment-gateway-domain"),
                'type'            => 'text',
                'desc_tip'      => true
            ),
            'bd_nagad_account_type'    => array(
                'title'            => esc_html('Nagad Account Type', "bd-payment-gateway-domain"),
                'type'            => 'select',
                'class'           => 'wc-enhanced-select',
                'description'     => esc_html('Select Nagad account type', "bd-payment-gateway-domain"),
                'options'    => array(
                    'Agent'        => esc_html('Agent', "bd-payment-gateway-domain"),
                    'Personal'    => esc_html('Personal', "bd-payment-gateway-domain")
                ),
                'desc_tip'      => true
            ),
            'bd_nagad_charge'     =>    array(
                'title'            => esc_html__('Enable Nagad Charge', "bd-payment-gateway-domain"),
                'type'             => 'checkbox',
                'label'            => esc_html__('Add 1.45% Nagad "Send Money" charge to the net price', "bd-payment-gateway-domain"),
                'default'        => 'no',
                'description'     => esc_html__('If a product price is 1000 then customer have to pay ( 1000 + 14.5 ) = 1014.5 Here 14.5 is Nagad send money charge', "bd-payment-gateway-domain"),
                'desc_tip'        => true
            ),
            'cdd_nagad_order_status' => array(
                'title'       => esc_html('Order Status', "bd-payment-gateway-domain"),
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'description' => esc_html('Choose whether status you wish after checkout.', "bd-payment-gateway-domain"),
                'default'     => 'wc-on-hold',
                'desc_tip'    => true,
                'options'     => wc_get_order_statuses()
            )
        );
    }

    public function payment_fields()
    {
        global $woocommerce;

        $bd_nagad_charge = '';

        if (($this->bd_nagad_charge == 'yes')) {
            $bd_nagad_charge = '<div class="bd_extra_charge_note">' . wpautop(wptexturize(esc_html__(' Note: 1.45% Nagad "Send Money" cost will be added with the net price. Total amount: ', "bd-payment-gateway-domain") . ' ' . get_woocommerce_currency_symbol() . $woocommerce->cart->total)) . '</div>';
        }

        echo $bd_nagad_charge;

        echo wpautop(wptexturize(esc_html__($this->description, "bd-payment-gateway-domain")));

        if (isset($this->bd_nagad_account_type)) {
            echo wpautop(wptexturize("Nagad " . $this->bd_nagad_account_type . " Number : " . $this->bd_nagad_number));
        }
?>
        <div class="payment_box_bd_child">
            <table>
                <tr>
                    <td><label for="bd_nagad_number"><?php esc_html_e('Nagad Number', "bd-payment-gateway-domain"); ?></label></td>
                    <td><input class="widefat" type="text" name="bd_nagad_number" id="bd_nagad_number" placeholder="Ex. 018XXXXXXXX"></td>
                </tr>
                <tr>
                    <td><label for="bd_nagad_transaction_id"><?php esc_html_e('Nagad Transaction ID', "bd-payment-gateway-domain"); ?></label></td>
                    <td><input class="widefat" type="text" name="bd_nagad_transaction_id" id="bd_nagad_transaction_id" placeholder="Ex. 8N7A6D5EE7M"></td>
                </tr>
            </table>
        </div>
<?php
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $status = null;
        if ('wc-' === substr($this->order_status, 0, 3)) {

            $status = substr($this->order_status, 3);
        } else {

            $status = $this->order_status;
        }

        // Mark as on-hold (we're awaiting the bKash)
        $order->update_status($status, esc_html('Checkout with Nagad payment. ', "bd-payment-gateway-domain"));

        // Reduce stock levels
        $order->reduce_order_stock();

        // Remove cart
        $woocommerce->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    public function bd_nagad_thankyou_page_function()
    {
        $order_id = get_query_var('order-received');
        $order = new WC_Order($order_id);
        if ($order->get_payment_method() == $this->id) {

            $thankyou = $this->bd_nagad_instructions;
            return $thankyou;
        } else {

            return esc_html__('Thank you. Your order has been received.', "bd-payment-gateway-domain");
        }
    }


    public function bd_nagad_email_instructions_function($order, $sent_to_admin, $plain_text = false)
    {
        if ($order->get_payment_method() != $this->id)
            return;
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }
}
