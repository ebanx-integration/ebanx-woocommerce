<style type="text/css">
#payment_ebanx_direct {
  list-style: none;
  margin: 0;
  padding: 0;
}
#payment_ebanx_direct li {
  margin: 0 0 15px 0;
  overflow: hidden;
}
#payment_ebanx_direct li.buttons {
  margin: 25px 0 0 0;
  overflow: hidden;
}
#payment_ebanx_direct li > label {
  font-weight: bold;
  display: inline-block;
  margin: 0 0 5px 0;
}
.ebanx-birth {
  overflow: hidden;
}
#payment_ebanx_direct .v-fix {
  float: left;
  display: inline-block;
  margin: 0 5px 0 0;
}
#payment_ebanx_direct .cvv {
  width: 50px;
}
.ebanx-cc-field,
.ebanx-tef-field {
  display: none;
}
.ebanx-method {
  overflow: hidden;
}
.ebanx-method .payment-method {
  float: left;
  display: block;
  overflow: hidden;
  width: 120px;
  height: 130px;
  margin: 0 20px 0 0;
}
.ebanx-method .payment-method input {
  display: none;
}
#payment_ebanx_direct select {
  color: #141412;
  background-color: #fff;
  border: 2px solid #d4d0ba;
  padding: 5px;
}
.ebanx-method .payment-method label {
  cursor: pointer;
  border-radius: 4px;
  border: 1px solid #ddd;
  background-color: #fff;
  width: 120px;
  height: 130px;
  padding: 20px;
  text-align: center;
  overflow: hidden;
  margin: 0 !important;
  display: block;
  position: relative;
}
.ebanx-method .payment-method.active label,
.ebanx-method .payment-method label:hover {
  border-color: #f26624;
  border-width: 2px;
}
.ebanx-method .payment-method label img {
  margin: 0 auto 10px;
}
.ebanx-method .payment-method label .desc {
  font-size: 13px;
  line-height: 12px;
  margin: 0;
}
</style>

<form method="POST">
  <ul class="form-list" id="payment_ebanx_direct">
    <li>
      <label for="ebanx_cpf" class="required">CPF</label>
      <div class="input-box">
        <input type="text" title="CPF" class="input-text required-entry validate-cpf" id="ebanx_cpf" name="ebanx[cpf]" value="">
      </div>
    </li>

    <li>
      <label for="ebanx_birth_day" class="required">Birth Date</label>
      <div class="input-box ebanx-birth">
        <div class="v-fix">
          <select id="ebanx_birth_day" name="ebanx[birth_day]" class="day required-entry" autocomplete="off">
            <option value="" selected="selected">Day</option>
            <? for ($i = 1; $i <= 31; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <? endfor ?>
          </select>
        </div>

        <div class="v-fix">
          <select id="ebanx_birth_month" name="ebanx[birth_month]" class="month required-entry" autocomplete="off">
            <option value="" selected="selected">Month</option>
            <? for ($i = 1; $i <= 12; $i++): ?>
              <option value="<?= $i ?>"><?= date("F", mktime(0, 0, 0, $i, 10)) ?></option>
            <? endfor ?>
          </select>
        </div>

        <div class="v-fix">
          <select id="ebanx_birth_year" name="ebanx[birth_year]" class="year required-entry" autocomplete="off">
            <option value="" selected="selected">Year</option>
            <? for ($i = date('Y') - 16; $i > 1920; $i--): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <? endfor ?>
          </select>
        </div>
      </div>
    </li>

    <li>
      <label for="payment-method" class="required">Payment Method</label>
      <div class="input-box ebanx-method">
        <? if ($this->enable_boleto): ?>
        <div class="payment-method active payment-method-toggle">
          <input type="radio" name="ebanx[method]" id="ebanx_method_boleto" value="boleto" checked="checked" />
          <label for="ebanx_method_boleto">
            <img src="<?= $this->icon_boleto ?>">
            <p class="desc">Boleto bancário</p>
          </label>
        </div>
        <? endif ?>

        <? if ($this->enable_cc): ?>
        <div class="payment-method payment-method-toggle">
          <input type="radio" name="ebanx[method]" id="ebanx_method_creditcard" value="creditcard" />
          <label for="ebanx_method_creditcard">
            <img src="<?= $this->icon_cc ?>">
            <p class="desc">Cartão de crédito</p>
          </label>
        </div>
        <? endif ?>

        <? if ($this->enable_tef): ?>
        <div class="payment-method payment-method-toggle">
          <input type="radio" name="ebanx[method]" id="ebanx_method_tef" value="tef" />
          <label for="ebanx_method_tef">
            <img src="<?= $this->icon_tef ?>">
            <p class="desc">Trasferência bancária</p>
          </label>
        </div>
        <? endif ?>
      </div>
    </li>

    <? if ($this->enable_cc): ?>
      <li class="ebanx-cc-field">
        <label for="ebanx_cc_name" class="required">Name on Card</label>
        <div class="input-box">
          <input type="text" title="Name on Card" class="input-text required-entry" id="ebanx_cc_name" name="ebanx[cc_name]" value="" autocomplete="off">
        </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_number" class="required ">Credit Card Number</label>
        <div class="input-box">
          <input type="text" id="ebanx_cc_number" name="ebanx[cc_number]" title="Credit Card Number" class="input-text required-entry validate-cc-ebanx validate-length minimum-length-12 maximum-length-19" value="" autocomplete="off">
        </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_cvv" class="required">Credit Card CVV</label>
        <div class="input-box">
          <input type="text" id="ebanx_cc_cvv" name="ebanx[cc_cvv]" title="Credit Card CVV" class="cvv input-text validate-length minimum-length-3 maximum-length-4" value="" autocomplete="off">
        </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_type" class="required">Credit Card Type</label>
        <div class="input-box">
          <select id="ebanx_cc_type" name="ebanx[cc_type]" title="Credit Card Type" class="required-entry" autocomplete="off">
            <option value="" selected="selected">--Please Select--</option>
              <option value="aura">Aura</option>
              <option value="amex">American Express</option>
              <option value="diners">Diners</option>
              <option value="discover">Discover</option>
              <option value="elo">Elo</option>
              <option value="mastercard">MasterCard</option>
              <option value="visa">Visa</option>
            </select>
          </div>
      </li>

      <li class="ebanx-cc-field">
        <label for="ebanx_cc_expiration_month" class="required">Expiration Date</label>
        <div class="input-box">
          <div class="v-fix">
            <select id="ebanx_cc_expiration_month" name="ebanx[cc_expiration_month]" class="month required-entry" autocomplete="off">
              <option value="" selected="selected">Month</option>
              <option value="1">01 - January</option>
              <option value="2">02 - February</option>
              <option value="3">03 - March</option>
              <option value="4">04 - April</option>
              <option value="5">05 - May</option>
              <option value="6">06 - June</option>
              <option value="7">07 - July</option>
              <option value="8">08 - August</option>
              <option value="9">09 - September</option>
              <option value="10">10 - October</option>
              <option value="11">11 - November</option>
              <option value="12">12 - December</option>
            </select>
          </div>

          <div class="v-fix">
            <select id="ebanx_cc_expiration_year" name="ebanx[cc_expiration_year]" class="year required-entry" autocomplete="off">
              <option value="" selected="selected">Year</option>
              <? for ($i = date('Y'); $i <= date('Y') + 20; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
              <? endfor ?>
            </select>
          </div>
        </div>
      </li>
    <? endif ?>

    <? if ($this->enable_tef): ?>
      <li class="ebanx-tef-field">
        <label for="ebanx_tef_bank" class="required">Bank</label>
        <div class="input-box">
          <select id="ebanx_tef_bank" name="ebanx[tef_bank]" title="Credit Card Type" class="required-entry" autocomplete="off">
            <option value="" selected="selected">--Please Select--</option>
            <option value="banrisul">Banrisul</option>
            <option value="bradesco">Bradesco</option>
            <option value="bancodobrasil">Banco do Brasil</option>
            <option value="hsbc">HSBC</option>
            <option value="itau">Itaú</option>
          </select>
        </div>
      </li>
    <? endif ?>

    <li class="buttons">
      <input type="submit" class="button alt" id="submit_ebanx_payment_form" value="Finalizar pagamento" />
      <a class="button cancel" href="<?= esc_url($order->get_cancel_order_url()) ?>">Cancelar</a>
    </li>
  </ul>
</form>