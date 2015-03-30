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
    $this->test_mode           = ($this->get_option('test_mode') == 'yes');
    $this->enable_boleto       = $this->paymentMethodisEnabled('boleto');
    $this->enable_tef          = $this->paymentMethodisEnabled('tef');
    $this->enable_cc           = $this->paymentMethodisEnabled('creditcards');
    $this->enable_pagoefectivo = $this->paymentMethodisEnabled('pagoefectivo');
    $this->enable_installments = $this->get_option('enable_installments') == 'yes';
    $this->max_installments    = intval($this->get_option('max_installments'));
    $this->interest_mode       = $this->get_option('interest_mode');
    $this->interest_rate       = floatval($this->get_option('interest_rate'));
    $this->enable_business_checkout = ($this->get_option('enable_business_checkout') == 'yes');
    $this->enable_ruc_peru     = ($this->get_option('enable_ruc_peru') == 'yes');

    // Images
    $this->icon_boleto = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_boleto.png';
    $this->icon_tef    = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_tef.png';
    $this->icon_cc     = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_cc.png';

    // Set EBANX configs
    \Ebanx\Config::set(array(
        'integrationKey' => $this->merchant_key
      , 'testMode'       => $this->test_mode
      , 'directMode'     => false
    ));

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
  }

  /**
   * Checks if a payment method is enabled
   * @param  string $paymentMethod The payment method name
   * @return boolean
   */
  protected function paymentMethodisEnabled($paymentMethod)
  {
    $options = $this->get_option('payment_methods');
    return in_array($paymentMethod, $options);
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
      'test_mode' => array(
        'title'   => __('Test mode', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable test mode', 'woocommerce'),
        'default' => 'yes',
        'description' => ''
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
        'default'  => __('Payments for Latin Americans', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce')
      ),
      'description' => array(
        'title'   => __('Customer message', 'woocommerce'),
        'type'    => 'textarea',
        'default' => __("You will be redirected to the EBANX website when you place an order.", 'woocommerce'),
        'description' => __('Give the customer instructions for paying via EBANX.', 'woocommerce')
      )
    );
  }

  /**
   * Process the payment and return the result
   * @return array
   */
  public function process_payment($order_id)
  {
    global $woocommerce;

    // Loads the current order
    $order = new WC_Order($order_id);

    $params = array(
        'merchant_payment_code' => $order_id . time()
      , 'order_number'          => $order_id
      , 'amount'                => $order->order_total
      , 'currency_code'         => get_woocommerce_currency()
      , 'name'                  => $order->billing_first_name . ' ' . $order->billing_last_name
      , 'email'                 => $order->billing_email
      , 'address'               => $order->billing_address_1
      , 'street_number'         => $order->billing_number
      , 'city'                  => $order->billing_city
      , 'state'                 => $order->billing_state
      , 'zipcode'               => $order->billing_postcode
      , 'country'               => $order->billing_country
      , 'phone_number'          => $order->billing_phone
      , 'payment_type_code'     => '_all'
    );

    try
    {
      $response = \Ebanx\Ebanx::doRequest($params);

      if ($response->status == 'SUCCESS')
      {
        // Clear cart
        $woocommerce->cart->empty_cart();

        return array(
          'result'   => 'success',
          'redirect' => $response->redirect_url
        );
      }
      else
      {
        wc_add_notice($this->getEbanxErrorMessage($response->error_code, $order->billing_country), 'error');
      }
    }
    catch (Exception $e)
    {
      wc_add_notice($e->getMessage(), 'error');
      return;
    }
  }

  /**
   * Returns user friendly error messages
   * @param string $errorCode The error code
   * @param string $countryCode The country code
   * @return string
   */
  protected function getEbanxErrorMessage($errorCode, $countryCode)
  {
    $countryCode = strtolower($countryCode);

    switch ($countryCode)
    {
      case 'pe':
        $lang = 'es';
        break;
      default:
        $lang = 'pt';
        break;
    }

    $messages = array(
        'BP-DR-13' => array(
            'pt' => 'É necessário fornecer seu nome.'
          , 'es' => 'Por favor introduzca su nombre.'
        )
      , 'BP-DR-15' => array(
            'pt' => 'É necessário fornecer seu email.'
          , 'es' => 'Por favor introduzca su correo electrónico.'
        )
      , 'BP-DR-17' => array(
            'pt' => 'O email fornecido não é válido.'
          , 'es' => 'La dirección de correo electrónico no es válida.'
        )
      , 'BP-DR-19' => array(
            'pt' => 'É necessário fornecer sua data de nascimento.'
          , 'es' => 'Usted debe proporcionar su fecha de nacimiento.'
        )
      , 'BP-DR-20' => array(
            'pt' => 'A data de nascimento não é válida.'
          , 'es' => 'La fecha de nacimiento no es válida.'
        )
      , 'BP-DR-21' => array(
            'pt' => 'É preciso ser maior de 16 anos para comprar.'
          , 'es' => 'Usted debe ser mayor de 16 años para comprar.'
        )
      , 'BP-DR-22' => array(
            'pt' => 'É preciso fornecer um CPF e/ou CNPJ válido.'
          , 'es' => 'Debe proporcionar un RUC válido.'
        )
      , 'BP-DR-23' => array(
            'pt' => 'É preciso fornecer um CPF e/ou CNPJ válido.'
          , 'es' => 'Debe proporcionar un RUC válido.'
        )
      , 'BP-DR-35' => array(
            'pt' => 'Por favor revise os detalhes de pagamento.'
          , 'es' => 'Por favor revise los detalles del pago.'
        )
      , 'BP-DR-39' => array(
            'pt' => 'O CPF é inválido ou está irregular na Receita Federal.'
          , 'es' => 'El RUC es inválido o irregular en IRS.'
        )
      , 'BP-DR-43' => array(
            'pt' => 'É necessário informar o nome do responsável.'
          , 'es' => 'Debe proporcionar el nombre del responsable.'
        )
      , 'BP-DR-44' => array(
            'pt' => 'É necessário informar o CPF do responsável.'
          , 'es' => 'Debe proporcionar el RUC del responsable.'
        )
    );

    if (isset($messages[$errorCode][$lang]))
    {
      return $messages[$errorCode][$lang];
    }

    return 'Unknown error. Please contact the store administrator.';
  }
}