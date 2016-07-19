<p>Por favor preencha os campos abaixo para finalizar o pagamento:</p>

<?php if (isset($_SESSION['ebanxError'])): ?>
  <div class="ebanx-error">
    Erro: <?php echo $_SESSION['ebanxError'] ?>
  </div>
  <?php unset($_SESSION['ebanxError']) ?>
<?php endif ?>

<form method="POST" id="ebanx-checkout-form">
  <ul class="form-list" id="payment_ebanx_direct">
    <li>
      <label for="payment-method" class="required">Método de pagamento</label>
      <div class="input-box ebanx-methods">
        <ul>
          <?php if ($this->enable_cc && $orderCountry == 'BR'): ?>
          <li class="payment-method payment-method-toggle">
            <input type="radio" name="ebanx[method]" id="ebanx_method_creditcard" value="creditcard" checked="checked"/>
            <label for="ebanx_method_creditcard">Cartão de crédito</label>
          </li>
          <?php endif ?>
        </ul>
      </div>
    </li>

    <?php if ($this->enable_cc && $orderCountry == 'BR'): ?>
      <li class="ebanx-cc-field">
        <label for="ebanx_cc_name" class="required">Titular do cartão</label>
        <div class="input-box">
          <input type="text" title="Name on Card" class="input-text required-entry" id="ebanx_cc_name" name="ebanx[cc_name]"
            value="<?php echo isset($_POST['ebanx']['cc_name']) ? $_POST['ebanx']['cc_name'] : '' ?>" autocomplete="off">
        </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_number" class="required ">Número do cartão</label>
        <div class="input-box">
          <input type="text" id="ebanx_cc_number" name="ebanx[cc_number]" title="Credit Card Number"
            class="input-text required-entry validate-cc-ebanx validate-length minimum-length-12 maximum-length-19"
            value="<?php echo isset($_POST['ebanx']['cc_number']) ? $_POST['ebanx']['cc_number'] : '' ?>" autocomplete="off">
        </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_cvv" class="required">Código de segurança do cartão (CVV)</label>
        <div class="input-box">
          <input type="text" id="ebanx_cc_cvv" name="ebanx[cc_cvv]" title="Credit Card CVV"
          class="cvv input-text validate-length minimum-length-3 maximum-length-4"
            value="<?php echo isset($_POST['ebanx']['cc_cvv']) ? $_POST['ebanx']['cc_cvv'] : '' ?>" autocomplete="off">
        </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_type" class="required">Bandeira do Cartão</label>
        <div class="input-box">
          <select id="ebanx_cc_type" name="ebanx[cc_type]" title="Credit Card Type" class="required-entry" autocomplete="off">
            <option value="" selected="selected">Por favor selecione</option>
              <option value="aura">Aura</option>
              <option value="amex">American Express</option>
              <option value="diners">Diners</option>
              <option value="discover">Discover</option>
              <option value="elo">Elo</option>
              <option value="hipercard">Hipercard</option>
              <option value="mastercard">MasterCard</option>
              <option value="visa">Visa</option>
            </select>
          </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_expiration_month" class="required">Validade do cartão</label>
        <div class="input-box">
          <div class="v-fix">
            <select id="ebanx_cc_expiration_month" name="ebanx[cc_expiration_month]" class="month required-entry" autocomplete="off">
              <option value="" selected="selected">Mês</option>
              <option value="1">01 - Janeiro</option>
              <option value="2">02 - Fevereiro</option>
              <option value="3">03 - Março</option>
              <option value="4">04 - Abril</option>
              <option value="5">05 - Maio</option>
              <option value="6">06 - Junho</option>
              <option value="7">07 - Julho</option>
              <option value="8">08 - Agosto</option>
              <option value="9">09 - Setembro</option>
              <option value="10">10 - Outobro</option>
              <option value="11">11 - Novembro</option>
              <option value="12">12 - Dezembro</option>
            </select>
          </div>

          <div class="v-fix">
            <select id="ebanx_cc_expiration_year" name="ebanx[cc_expiration_year]" class="year required-entry" autocomplete="off">
              <option value="" selected="selected">Ano</option>
              <?php for ($i = date('Y'); $i <= date('Y') + 20; $i++): ?>
                <option value="<?php echo $i ?>"><?php echo $i ?></option>
              <?php endfor ?>
            </select>
          </div>
        </div>
      </li>
      <?php if ($this->enable_installments): ?>
        <li class="ebanx-cc-field">
          <label for="ebanx_cc_installments" class="required">Parcelas</label>
          <div class="input-box">
            <div class="v-fix">
              <select id="ebanx_cc_installments" name="ebanx[cc_installments]" class="required-entry" autocomplete="off">
                <?php foreach ($installmentOptions as $number => $total): ?>
                  <option value="<?php echo $number ?>"><?php echo $number ?>x <?php echo get_woocommerce_currency_symbol() . money_format('%i', $total / $number) ?></option>
                <?php endforeach ?>
              </select>
            </div>
          </div>
        </li>
      <?php endif ?>
    <?php endif ?>

    <li class="buttons">
      <input type="submit" class="button alt" id="submit_ebanx_payment_form" value="Finalizar pagamento" />
      <a class="button cancel" href="<?php echo esc_url($order->get_cancel_order_url()) ?>">Cancelar</a>
    </li>
  </ul>
</form>
