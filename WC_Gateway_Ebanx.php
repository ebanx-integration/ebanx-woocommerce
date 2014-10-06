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

    // Images
    $this->icon_boleto = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_boleto.png';
    $this->icon_tef    = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_tef.png';
    $this->icon_cc     = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/icon_cc.png';

    // Set EBANX configs
    \Ebanx\Config::set(array(
        'integrationKey' => $this->merchant_key
      , 'testMode'       => $this->test_mode
      , 'directMode'     => true
    ));

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
    add_action('woocommerce_receipt_ebanx', array(&$this, 'receipt_page'));
    add_action('woocommerce_checkout_update_order_meta', array(&$this, 'checkout_fields_save'));
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
      'payment_methods' => array(
        'title'   => __('Enable payment methods', 'woocommerce'),
        'type'    => 'multiselect',
        'label'   => __('Enable payments methods for EBANX', 'woocommerce'),
        'description' => '',
        'default' => array('boleto', 'tef'),
        'options' => array(
          'boleto'       => 'Boleto',
          'tef'          => 'Bank transfer',
          'creditcards'  => 'Credit cards',
          'pagoefectivo' => 'PagoEfectivo'
        )
      ),
      'enable_installments' => array(
        'title'   => __('Enable installments', 'woocommerce'),
        'type'    => 'checkbox',
        'label'   => __('Enable installments for credit cards payments', 'woocommerce'),
        'default' => 'no',
        'description' => ''
      ),
      'max_installments' => array(
        'title'    => __('Maximum installments number', 'woocommerce'),
        'type'     => 'select',
        'default'  => '1',
        'desc_tip' => true,
        'description' => '',
        'options' => array(
          '1' => '1',
          '2' => '2',
          '3' => '3',
          '4' => '4',
          '5' => '5',
          '6' => '6',
          '7' => '7',
          '8' => '8',
          '9' => '9',
          '10' => '10',
          '11' => '11',
          '12' => '12'
        )
      ),
      'interest_mode' => array(
        'title'    => __('Interest calculation method', 'woocommerce'),
        'type'     => 'select',
        'default'  => 'simple',
        'desc_tip' => true,
        'description' => '',
        'options' => array(
          'compound' => 'Compound interest',
          'simple'   => 'Simple interest'
        )
      ),
      'interest_rate' => array(
        'title'    => __('Interest rate', 'woocommerce'),
        'type'     => 'text',
        'default'  => '0.00',
        'desc_tip' => true,
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

  /**
   * Returns the assets path
   * @param  string $filename The asset name
   * @return string
   */
  protected function getAssetPath($filename)
  {
    return dirname(__FILE__) . '/assets/' . $filename;
  }

  /**
   * Renders the EBANX checkout page
   * @param  int $order_id The order ID
   * @return boolean
   */
  protected function _renderCheckout($order_id)
  {
    global $woocommerce;

    // Loads the current order
    $order = new WC_Order($order_id);

    $ebanxCpf = (isset($order->billing_cpf)) ? $order->billing_cpf: '';

    if (isset($order->billing_birthdate))
    {
      $dateParts = explode('/', $order->billing_birthdate);
      $birthDate = array(
          'day'   => $dateParts[0]
        , 'month' => $dateParts[1]
        , 'year'  => $dateParts[2]
      );
    }
    else
    {
      $birthDate = array(
          'day'   => ($_POST['ebanx']['birth_day']) ?: 0
        , 'month' => ($_POST['ebanx']['birth_month']) ?: 0
        , 'year'  => ($_POST['ebanx']['birth_year']) ?: 0
      );
    }

    $installmentOptions = $this->calculateInstallmentOptions($order->order_total);
    $orderCountry       = $order->billing_country;

    $tplDir = dirname(__FILE__) . '/view/';

    $template = file_get_contents($tplDir . 'checkout.php');
    echo eval(' ?>' . $template . '<?php ');

    $jsCode = file_get_contents($this->getAssetPath('checkout.js'));
    $woocommerce->add_inline_js($jsCode);
  }

  protected function calculateInstallmentOptions($orderTotal)
  {
    $options = array();
    $options[1] = $orderTotal;

    for ($i = 2; $i <= $this->max_installments; $i++)
    {
      $total = $this->calculateTotalWithInterest($orderTotal, $i);

      // Enforce minimum 30 moneys for installments
      if ($total / $i >= 30)
      {
        $options[$i] = $total;
      }
      else
      {
        break;
      }
    }

    return $options;
  }

  /**
   * Generates the EBANX button link
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
    $cpf          = $_POST['ebanx']['cpf'];
    $birthDate    = $postBdate;
    $streetNumber = isset($order->billing_number) ? $order->billing_number : '1';

    // Append timestamp on test mode
    $orderId = ($this->test_mode) ? $order_id . time() : $order_id;

    $params = array(
        'mode'      => 'full'
      , 'operation' => 'request'
      , 'payment'   => array(
            'merchant_payment_code' => $orderId
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
          , 'country'           => $order->billing_country
          , 'phone_number'      => $order->billing_phone
          , 'payment_type_code' => $_POST['ebanx']['method']
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

        // If has installments, adjust total
        if (isset($_POST['ebanx']['cc_installments']))
        {
          $installments = intval($_POST['ebanx']['cc_installments']);

          if ($installments > 1)
          {
            $params['payment']['instalments']  = $installments;
            $params['payment']['amount_total'] = $this->calculateTotalWithInterest($order->order_total, $installments);
          }
        }
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
        // Clear cart
        $woocommerce->cart->empty_cart();

        if ($_POST['ebanx']['method'] == 'boleto')
        {
          $boletoUrl = $response->payment->boleto_url;
          $orderUrl  = $this->get_return_url($order);

          $tplDir = dirname(__FILE__) . '/view/';

          $template = file_get_contents($tplDir . 'boleto.php');
          echo eval(' ?>' . $template . '<?php ');
        }
        else if ($_POST['ebanx']['method'] == 'pagoefectivo')
        {
          $cipUrl   = $response->payment->cip_url;
          $cipCode  = $response->payment->cip_code;
          $orderUrl = $this->get_return_url($order);

          $tplDir = dirname(__FILE__) . '/view/';

          $template = file_get_contents($tplDir . 'pagoefectivo.php');
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
        $_SESSION['ebanxError'] = $this->getEbanxErrorMessage($response->status_code);
        $this->_renderCheckout($order_id);
      }
    }
    catch (Exception $e)
    {
      $_SESSION['ebanxError'] = $e->getMessage();
      $this->_renderCheckout($order_id);
    }
  }

  /**
   * Returns user friendly error messages
   * @param  string $errorCode The error code
   * @return string
   */
  protected function getEbanxErrorMessage($errorCode)
  {
    $errors = array(
        "BP-DR-1"  => "O modo deve ser full ou iframe"
      , "BP-DR-2"  => "É necessário selecionar um método de pagamento"
      , "BP-DR-3"  => "É necessário selecionar uma moeda"
      , "BP-DR-4"  => "A moeda não é suportada pelo EBANX"
      , "BP-DR-5"  => "É necessário informar o total do pagamento"
      , "BP-DR-6"  => "O valor do pagamento deve ser maior do que X"
      , "BP-DR-7"  => "O valor do pagamento deve ser menor do que"
      , "BP-DR-8"  => "O valor total somado ao valor de envio deve ser igual ao valor total"
      , "BP-DR-13" => "É necessário informar um nome"
      , "BP-DR-14" => "O nome não pode conter mais de 100 caracteres"
      , "BP-DR-15" => "É necessário informar um email"
      , "BP-DR-16" => "O email não pode conter mais de 100 caracteres"
      , "BP-DR-17" => "O email informado é inválido"
      , "BP-DR-18" => "O cliente está suspenso no EBANX"
      , "BP-DR-19" => "É necessário informar a data de nascimento"
      , "BP-DR-20" => "É necessário informar a data de nascimento"
      , "BP-DR-21" => "É preciso ser maior de 16 anos"
      , "BP-DR-22" => "É necessário informar um CPF ou CNPJ"
      , "BP-DR-23" => "O CPF informado não é válido"
      , "BP-DR-24" => "É necessário informar um CEP"
      , "BP-DR-25" => "É necessário informar o endereço"
      , "BP-DR-26" => "É necessário informar o número do endereço"
      , "BP-DR-27" => "É necessário informar a cidade"
      , "BP-DR-28" => "É necessário informar o estado"
      , "BP-DR-29" => "O estado informado é inválido. Deve se informar a sigla do estado (ex.: SP)"
      , "BP-DR-30" => "O código do país deve ser 'br'"
      , "BP-DR-31" => "É necessário informar um telefone"
      , "BP-DR-32" => "O telefone informado é inválido"
      , "BP-DR-33" => "Número de parcelas inválido"
      , "BP-DR-34" => "Número de parcelas inválido"
      , "BP-DR-35" => "Método de pagamento inválido: X"
      , "BP-DR-36" => "O método de pagamento não está ativo"
      , "BP-DR-39" => "CPF, nome e data de nascimento não combinam"
      , "BP-DR-40" => "Cliente atingiu o limite de pagamentos para o período"
      , "BP-DR-41" => "Deve-se escolher um tipo de pessoa - física ou jurídica."
      , "BP-DR-42" => "É necessário informar os dados do responsável pelo pagamento"
      , "BP-DR-43" => "É necessário informar o nome do responsável pelo pagamento"
      , "BP-DR-44" => "É necessário informar o CPF do responsável pelo pagamento"
      , "BP-DR-45" => "É necessário informar a data de bascunebti do responsável pelo pagamento"
      , "BP-DR-46" => "CPF, nome e data de nascimento do responsável não combinam"
      , "BP-DR-47" => "A conta bancário deve conter no máximo 10 caracteres"
      , "BP-DR-48" => "É necessário informar os dados do cartão de crédito"
      , "BP-DR-49" => "É necessário informar o número do cartão de crédito"
      , "BP-DR-50" => "É necessário selecionar o método de pagamento"
      , "BP-DR-51" => "É necessário informar o nome do titular do cartão de crédito"
      , "BP-DR-52" => "O nome do titular do cartão deve conter no máximo 50 caracteres"
      , "BP-DR-54" => "É necessário informar o CVV do cartão de crédito"
      , "BP-DR-55" => "O CVV deve conter no máximo 4 caracteres"
      , "BP-DR-56" => "É necessário informar a data de venciomento do cartão de crédito"
      , "BP-DR-57" => "A data de vencimento do cartão de crédito deve estar no formato dd/mm/aaaa"
      , "BP-DR-58" => "A data de vencimento do boleto é inválida"
      , "BP-DR-59" => "A data de vencimento do boleto é menor do que o permitido"
      , "BP-DR-61" => "Não foi possível criar um token para este cartão de crédito"
      , "BP-DR-62" => "Pagamentos recorrentes não estão habilitados para este merchant"
      , "BP-DR-63" => "Token não encontrado para este adquirente"
      , "BP-DR-64" => "Token não encontrado"
      , "BP-DR-65" => "O token informado já está sendo utilizado"
      , "BP-DR-66" => "Token inválido. O token deve ter entre 32 e 128 caracteres"
      , "BP-DR-67" => "A data de venciomento do cartão de crédito é inválida"
      , "BP-DR-68" => "É necessário informar o número da conta bancária"
      , "BP-DR-69" => "A conta bancária não pode conter mais de 10 caracteres"
      , "BP-DR-70" => "É necessário informar a agência bancária"
      , "BP-DR-71" => "O código do banco não pode ter mais de 5 caracteres"
      , "BP-DR-72" => "É necessário informar o código do banco"
      , "BP-DR-73" => "É necessário informar os dados da conta para débito em conta"
      , "BP-R-1" => "É necessário informar a moeda"
      , "BP-R-2" => "É necessário informar o valor do pagamento"
      , "BP-R-3" => "É necessário informar o código do pedido"
      , "BP-R-4" => "É necessário informar o nome"
      , "BP-R-5" => "É necessário informar o email"
      , "BP-R-6" => "É necessário selecionar o método de pagamento"
      , "BP-R-7" => "O método de pagamento não está ativo"
      , "BP-R-8" => "O método de pagamento é inválido"
      , "BP-R-9" => "O valor do pagamento deve ser positivo: X"
      , "BP-R-10" => "O valor do pagamento deve ser maior do que X"
      , "BP-R-11" => "O método de pagamento não suporta parcelamento"
      , "BP-R-12" => "O número máximo de parcelas é X. O valor informado foi de X parcelas."
      , "BP-R-13" => "O valor mínimo das parcelas é de R$ X."
      , "BP-R-17" => "O pagamento não está aberto"
      , "BP-R-18" => "O típo de pessoa é inválido"
      , "BP-R-19" => "O checkout com CNPJ não está habilitado"
      , "BP-R-20" => "A data de vencimento deve estar no formato dd/mm/aaaa"
      , "BP-R-21" => "A data de vencimento é inválida"
      , "BP-R-22" => "A data de vencimento é inválida"
      , "BP-R-23" => "A moeda não está ativa no sistema"
      , "BP-ZIP-1" => "O CEP não foi informado"
      , "BP-ZIP-2" => "O CEP não é válido"
      , "BP-ZIP-3" => "O endereço não pode ser encontrado"
    );

    if (array_key_exists($errorCode, $errors))
    {
      return $errors[$errorCode];
    }

    return 'Ocorreu um erro desconhecido. Por favor contacte o administrador.';
  }

  /**
   * Calculates the order total with interest
   * @param  float  $orderTotal   The order total
   * @param  int    $installments The installments number
   * @return float
   */
  protected function calculateTotalWithInterest($orderTotal, $installments)
  {
    switch ($this->interest_mode) {
      case 'compound':
        $total = $orderTotal * pow((1.0 + floatval($this->interest_rate / 100)), $installments);
        break;
      case 'simple':
        $total = (floatval($this->interest_rate / 100) * floatval($orderTotal) * intval($installments)) + floatval($orderTotal);
        break;
      default:
        throw new Exception("Interest mode {$interestMode} is unsupported.");
        break;
    }

    return $total;
  }
}