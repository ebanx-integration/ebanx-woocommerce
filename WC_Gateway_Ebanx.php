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
    $this->enable_installments = $this->get_option('enable_installments');
    $this->max_installments    = $this->get_option('max_installments');
    $this->interest_rate       = $this->get_option('interest_rate');

    // Set EBANX configs
    \Ebanx\Config::set(array(
        'integrationKey' => $this->merchant_key
      , 'testMode'       => $this->test_mode
    ));

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
    add_action('woocommerce_receipt_ebanx', array(&$this, 'receipt_page'));
    add_filter('woocommerce_after_order_notes', array(&$this, 'checkout_fields_installments'));
    add_action('woocommerce_checkout_update_order_meta', array(&$this, 'checkout_fields_installments_save'));
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
        'default'  => __('EBANX', 'woocommerce'),
        'desc_tip' => true,
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce')
      ),
      'description' => array(
        'title'   => __('Customer message', 'woocommerce'),
        'type'    => 'textarea',
        'default' => __('EBANX is the market leader in e-commerce payment solutions for International Merchants selling online to Brazil.', 'woocommerce'),
        'description' => __('Give the customer instructions for paying via EBANX.', 'woocommerce')
      ),
      'test_mode' => array(
        'title'   => __('Test mode', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable test mode', 'woocommerce'),
        'default' => 'yes',
        'description' => ''
      ),
      'enable_installments' => array(
        'title'   => __('Installments', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable installments', 'woocommerce'),
        'default' => 'no'
      ),
      'max_installments' => array(
        'title'   => __('Maximum number of installments', 'woocommerce'),
        'type'    => 'select',
        'default' => 1,
        'options' => array(
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
          5 => 5,
          6 => 6
        )
      ),
      'interest_rate' => array(
        'title'   => __('Installments interest rate (%)', 'woocommerce'),
        'type'    => 'text',
        'default' => 0.0,
      )
    );
  }

  function checkout_fields_installments_save($order_id)
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
  public function checkout_fields_installments($checkout)
  {
    global $woocommerce;

    if ($this->enable_installments == 'yes')
    {
      $options = array();
      $total = $woocommerce->cart->total;

      // 1x - no interest
      $options[1] = '1 x $' . $total;

      // 2x or more - with interest (optional)
      $total = ($total * (100 + $this->interest_rate)) / 100.0;

      // Enforce installment value of R$20,00
      $maxInstallments = $this->max_installments;
      $totalReal = (strtoupper(get_woocommerce_currency()) == 'BRL') ? $total : $total * 2.5;
      if (($totalReal / 20) < $maxInstallments)
      {
        $maxInstallments = $totalReal / 20;
      }

      for ($i = 2; $i <= $maxInstallments; $i++)
      {
        $value = money_format('%i', $total / floatval($i));
        $label = $i . ' x $' . $value;
        $options[$i] = $label;
      }

      echo '<div id="ebanx_installments"><h3>' . __('Installments') . '</h3>';

      woocommerce_form_field('installments_number', array(
        'type'    => 'select',
        'class'   => array('form-row-wide'),
        'options' => $options
        ), $checkout->get_value('installments_number'));

      woocommerce_form_field('installments_card', array(
        'type'    => 'select',
        'class'   => array('form-row-wide'),
        'options' => array('visa' => 'Visa', 'mastercard' => 'Mastercard')
        ), $checkout->get_value('installments_card'));

      echo '</div>';

      $woocommerce->add_inline_js("
        var installmentsBlock = $('#ebanx_installments');
        installmentsBlock.hide();

        // Fix to show installments when EBANX is the default payment method
        setTimeout(function() {
          if ($('input[name=payment_method]:checked').val() == 'ebanx') {
            installmentsBlock.show();
          }}, 1000);

        $('body').on('click', 'input[name=payment_method]', function() {
          if ($(this).val() == 'ebanx') {
            installmentsBlock.show();
          } else {
            installmentsBlock.hide();
          }
        });

        var cardPicker = $('#installments_card');
        cardPicker.hide();

        jQuery('#installments_number').change(function() {
          var value = $(this).val();

          if (value == 1) {
            cardPicker.hide();
          } else {
            cardPicker.show();
          }
        });
      ");
    }
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
    echo '<p>'.__('Thank you for your order, please click the button below to pay with ebanx.', 'woocommerce').'</p>';
    echo $this->generate_ebanx_form( $order );
  }

  /**
   * Generate the EBANX button link
   * @return string
   */
  public function generate_ebanx_form($order_id)
  {
    global $woocommerce;
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