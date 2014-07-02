<?php

/**
 * Copyright (c) 2013, EBANX Tecnologia da Informação Ltda.
 *  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * Neither the name of EBANX nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

class WC_Gateway_Ebanx extends WC_Payment_Gateway
{
  public function __construct()
  {
    $this->id           = 'ebanx';
    $this->icon         = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/ebanx.png';
    $this->has_fields   = false;
    $this->method_title = __('EBANX', 'woocommerce');

    // Load the settings
    $this->init_form_fields();
    $this->init_settings();

    // Define user set variables
    $this->title               = $this->get_option('title');
    $this->description         = $this->get_option('description');
    $this->merchant_key        = $this->get_option('merchant_key');
    $this->test_mode           = $this->get_option('test_mode');
    $this->enable_boleto       = $this->get_option('method_boleto') == 'yes';
    $this->enable_tef          = $this->get_option('method_tef') == 'yes';
    $this->enable_cc           = $this->get_option('method_cc') == 'yes';

    // Images
    $this->icon_boleto = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_boleto.png';
    $this->icon_tef    = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_tef.png';
    $this->icon_cc     = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_cc.png';

    // Set EBANX configs
    \Ebanx\Config::set(array(
        'integrationKey' => $this->merchant_key
      , 'testMode'       => ($this->test_mode == 'yes')
      , 'directMode'     => true
    ));

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
    add_action('woocommerce_receipt_ebanx', array(&$this, 'receipt_page'));
    add_filter('woocommerce_after_order_notes', array(&$this, 'checkout_fields'));
    add_action('woocommerce_checkout_update_order_meta', array(&$this, 'checkout_fields_save'));
  }

  /**
   * Render the administration form fields
   * @return void
   */
  public function init_form_fields()
  {
    $this->form_fields = array(
      'enabled' => array(
        'title'   => __('Enable/Disable', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable EBANX payment gateway', 'woocommerce'),
        'default' => 'yes'
      ),
      'merchant_key' => array(
        'title'   => __('Merchant key', 'woocommerce'),
        'type'    => 'text',
        'default' => '',
        'description' => ''
      ),
      'title' => array(
        'title'    => __('Title', 'woocommerce'),
        'type'     => 'text',
        'default'  => __('Boleto bancário, cartão de crédito e transferência eletrônica', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce')
      ),
      'description' => array(
        'title'   => __('Customer message', 'woocommerce'),
        'type'    => 'textarea',
        'default' => __('Pagamentos para clientes do Brasil.', 'woocommerce'),
        'description' => __('Give the customer instructions for paying via EBANX.', 'woocommerce')
      ),
      'test_mode' => array(
        'title'   => __('Test mode', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable test mode', 'woocommerce'),
        'default' => 'yes',
        'description' => ''
      ),
      'method_boleto' => array(
        'title'   => __('Enable boleto', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable boleto payments', 'woocommerce'),
        'default' => 'yes',
        'description' => ''
      ),
      'method_tef' => array(
        'title'   => __('Enable bank transfer', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable bank transfer payments', 'woocommerce'),
        'default' => 'yes',
        'description' => ''
      ),
      'method_cc' => array(
        'title'   => __('Enable credit cards', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable credit cards payments', 'woocommerce'),
        'default' => 'no',
        'description' => ''
      )
    );
  }

  function checkout_fields_save($order_id)
  {
    global $woocommerce;

    if (isset($_POST['installments_number']))
    {
      update_post_meta($order_id, 'installments_number', esc_attr($_POST['installments_number']));
      update_post_meta($order_id, 'installments_card', esc_attr($_POST['installments_card']));
    }
  }

  /**
   * Adds installments fields to the checkout page
   */
  public function checkout_fields($checkout)
  {
    global $woocommerce;
  }

  /**
   * Process the payment and return the result
   * @return array
   */
  public function process_payment($order_id)
  {
    $order = new WC_Order($order_id);
    return array(
      'result'   => 'success',
      'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
    );
  }

  /**
   * Receipt page content
   * @return void
   */
  public function receipt_page($order)
  {
    echo '<p>'.__('Por favor preencha os campos abaixo para finalizar o pagamento:', 'woocommerce').'</p>';
    echo $this->generate_ebanx_form( $order );
  }

  /**
   * Generate the EBANX button link
   * @return string
   */
  public function generate_ebanx_form($order_id)
  {
    global $woocommerce;

    // Loads the current order
    $order = new WC_Order($order_id);

    $tplDir = dirname(__FILE__) . '/template/';

    $template = file_get_contents($tplDir . 'checkout.php');
    echo eval(' ?>' . $template . '<?php ');

    $jsCode = file_get_contents($tplDir . 'checkout.js');
    $woocommerce->add_inline_js($jsCode);

    // If is GET, do nothing, otherwise process the request
    if ($_SERVER['REQUEST_METHOD'] === 'GET')
    {
      return;
    }

    echo 'post!';


    return;

    $order = new WC_Order($order_id);

    $cpf          = isset($order->billing_cpf) ? $order->billing_cpf : '';
    $birthDate    = isset($order->billing_birthdate) ? $order->billing_birthdate : '';
    $streetNumber = isset($order->billing_number) ? $order->billing_number : '';

    $params = array(
        'name'              => $order->billing_first_name . ' ' . $order->billing_last_name
      , 'email'             => $order->billing_email
      , 'payment_type_code' => '_all'
      , 'amount'            => $order->order_total
      , 'currency_code'     => get_woocommerce_currency()
      , 'merchant_payment_code' => $order_id
      , 'address'           => $order->billing_address_1
      , 'cpf'               => $cpf
      , 'birth_date'
      , 'zipcode'           => $order->billing_postcode
      , 'street_number'     => ''
      , 'phone_number'      => $order->billing_phone
    );

    if (isset($order->order_custom_fields['installments_number'][0]) && $order->order_custom_fields['installments_number'][0] > 1)
    {
      $params['instalments'] = $order->order_custom_fields['installments_number'][0];
      $params['payment_type_code'] = $order->order_custom_fields['installments_card'][0];
      $params['amount'] = ($params['amount'] * (100 + $this->interest_rate)) / 100.0;
    }

    $response = \Ebanx\Ebanx::doRequest($params);

    if ($response->status == 'SUCCESS')
    {
      $woocommerce->add_inline_js( '
        jQuery("body").block({
            message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to EBANX.', 'woocommerce' ) ) . '",
            baseZ: 99999,
            overlayCSS:
            {
              background: "#fff",
              opacity: 0.6
            },
            css: {
              padding:        "20px",
              zindex:         "9999999",
              textAlign:      "center",
              color:          "#555",
              border:         "3px solid #aaa",
              backgroundColor:"#fff",
              cursor:         "wait",
              lineHeight:   "24px",
            }
          });
        jQuery("#submit_ebanx_payment_form").click();
      ' );
      return '<form action="'.esc_url( $response->redirect_url ).'" method="post" id="ebanx_payment_form" target="_top">
        <input type="hidden" name="hash" value="'.$response->payment->hash.'" />
        <input type="submit" class="button alt" id="submit_ebanx_payment_form" value="' . __( 'Pay via EBANX', 'woocommerce' ) . '" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
      </form>';
    }
    else
    {
      return 'Something went wrong, please contact the administrator.';
    }
  }
}