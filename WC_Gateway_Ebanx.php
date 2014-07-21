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
    echo $this->generate_ebanx_form($order);
  }

  protected function _renderCheckout($order_id)
  {
    global $woocommerce;

    $order = new WC_Order($order_id);

    $tplDir = dirname(__FILE__) . '/template/';

    $template = file_get_contents($tplDir . 'checkout.php');
    echo eval(' ?>' . $template . '<?php ');

    $jsCode = file_get_contents($tplDir . 'checkout.js');
    $woocommerce->add_inline_js($jsCode);

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

    // If is GET, do nothing, otherwise process the request
    if ($_SERVER['REQUEST_METHOD'] === 'GET')
    {
      $this->_renderCheckout($order_id);
      return;
    }

    $order = new WC_Order($order_id);

    $postBdate    = str_pad($_POST['ebanx']['birth_day'], 2, '0', STR_PAD_LEFT) . '/' .
                    str_pad($_POST['ebanx']['birth_month'], 2, '0', STR_PAD_LEFT) . '/' .
                    str_pad($_POST['ebanx']['birth_year'],   2, '0', STR_PAD_LEFT);
    $cpf          = isset($order->billing_cpf) ? $order->billing_cpf : $_POST['ebanx']['cpf'];
    $birthDate    = isset($order->billing_birthdate) ? $order->billing_birthdate : $postBdate;
    $streetNumber = isset($order->billing_number) ? $order->billing_number : '1';

    $params = array(
        'mode'      => 'full'
      , 'operation' => 'request'
      , 'payment'   => array(
            'merchant_payment_code' => $order_id
          , 'amount_total'      => $order->order_total
          , 'currency_code'     => get_woocommerce_currency()
          , 'name'              => $order->billing_first_name . ' ' . $order->billing_last_name
          , 'email'             => $order->billing_email
          , 'birth_date'        => $birthDate
          , 'document'          => $cpf
          , 'address'           => $order->billing_address_1
          , 'street_number'     => $streetNumber
          , 'city'              => $order->billing_city
          , 'state'             => $order->billing_state
          , 'zipcode'           => $order->billing_postcode
          , 'country'           => 'br'
          , 'phone_number'      =>  $order->billing_phone
          , 'payment_type_code' => 'boleto'
        )
    );

    // Add credit card fields if the method is credit card
    if ($_POST['ebanx']['method'] == 'creditcard')
    {
        $ccExpiration = str_pad($_POST['ebanx']['cc_expiration_month'], 2, '0', STR_PAD_LEFT) . '/'
                      . $_POST['ebanx']['cc_expiration_year'];

        $params['payment']['payment_type_code'] = $_POST['ebanx']['cc_type'];
        $params['payment']['creditcard'] = array(
            'card_name'     => $_POST['ebanx']['cc_name']
          , 'card_number'   => $_POST['ebanx']['cc_number']
          , 'card_cvv'      => $_POST['ebanx']['cc_cvv']
          , 'card_due_date' => $ccExpiration
        );
    }

    // For TEF and Bradesco, add redirect another parameter
    if ($_POST['ebanx']['method'] == 'tef')
    {
        $params['payment']['payment_type_code'] = $_POST['ebanx']['tef_bank'];

        // For Bradesco, set payment method as bank transfer
        if ($_POST['ebanx']['tef_bank'] == 'bradesco')
        {
          $params['payment']['payment_type_code_option'] = 'banktransfer';
        }
    }

    try
    {
      $response = \Ebanx\Ebanx::doRequest($params);

      if ($response->status == 'SUCCESS')
      {
        if ($_POST['ebanx']['method'] == 'boleto')
        {
          $boletoUrl = $response->payment->boleto_url;
          $orderUrl  = $this->get_return_url($order);

          $tplDir = dirname(__FILE__) . '/template/';

          $template = file_get_contents($tplDir . 'boleto.php');
          echo eval(' ?>' . $template . '<?php ');
        }
        else if ($_POST['ebanx']['method'] == 'tef')
        {
          wp_redirect($response->redirect_url);
        }
        else
        {
          wp_redirect($this->get_return_url($order));
        }
      }
      else
      {
        $_SESSION['ebanxError'] = $response->status_message;
        $this->_renderCheckout($order_id);
      }
    }
    catch (Exception $e)
    {
      $_SESSION['ebanxError'] = $e->getMessage();
      $this->_renderCheckout($order_id);
    }
  }
}