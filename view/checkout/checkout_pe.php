<p>Por favor introduzca los datos de pago:</p>

<?php if (isset($_SESSION['ebanxError'])): ?>
  <div class="ebanx-error">
    Erro: <?php echo $_SESSION['ebanxError'] ?>
  </div>
  <?php unset($_SESSION['ebanxError']) ?>
<?php endif ?>

<form method="POST" id="ebanx-checkout-form">
  <ul class="form-list" id="payment_ebanx_direct">
    <?php if ($this->enable_ruc_peru): ?>
      <li>
        <label for="ebanx_document" class="required">RUC</label>
        <div class="input-box">
          <input type="text" title="RUC" class="input-text required-entry" id="ebanx_document" name="ebanx[document]"
          value="<?php echo isset($_POST['ebanx']['document']) ? $_POST['ebanx']['document'] : $ebanxDocument ?>">
        </div>
      </li>

      <li>
        <label for="ebanx_birth_day" class="required">Fecha de nacimiento</label>
        <div class="input-box ebanx-birth">
          <div class="v-fix">
            <select id="ebanx_birth_day" name="ebanx[birth_day]" class="day required-entry" autocomplete="off">
              <option value="" selected="selected">Día</option>
              <?php for ($i = 1; $i <= 31; $i++): ?>
                <option value="<?php echo $i ?>" <?php if (isset($birthDate['day']) && $birthDate['day'] == $i) echo 'selected'?>>
                  <?php echo $i ?>
                </option>
              <?php endfor ?>
            </select>
          </div>

          <div class="v-fix">
            <select id="ebanx_birth_month" name="ebanx[birth_month]" class="month required-entry" autocomplete="off">
              <option value="" selected="selected">Mes</option>
              <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i ?>" <?php if (isset($birthDate['month']) && $birthDate['month'] == $i) echo 'selected'?>>
                  <?php echo date("F", mktime(0, 0, 0, $i, 10)) ?>
                </option>
              <?php endfor ?>
            </select>
          </div>

          <div class="v-fix">
            <select id="ebanx_birth_year" name="ebanx[birth_year]" class="year required-entry" autocomplete="off">
              <option value="" selected="selected">Año</option>
              <?php for ($i = date('Y') - 16; $i > 1920; $i--): ?>
                <option value="<?php echo $i ?>" <?php if (isset($birthDate['year']) && $birthDate['year'] == $i) echo 'selected'?>>
                  <?php echo $i ?>
                </option>
              <?php endfor ?>
            </select>
          </div>
        </div>
      </li>
    <?php endif ?>

    <li>
      <label for="payment-method" class="required">Forma de pago</label>
      <div class="input-box ebanx-methods">
        <ul>
          <?php if ($this->enable_pagoefectivo && $orderCountry == 'PE'): ?>
          <li class="payment-method payment-method-toggle">
            <input type="radio" name="ebanx[method]" id="ebanx_method_pagoefectivo" value="pagoefectivo" />
            <label for="ebanx_method_pagoefectivo">PagoEfectivo</label>
          </li>
          <?php endif ?>
        </ul>
      </div>
    </li>

    <li class="buttons">
      <input type="submit" class="button alt" id="submit_ebanx_payment_form" value="Confirmar pedido" />
      <a class="button cancel" href="<?php echo esc_url($order->get_cancel_order_url()) ?>">Cancelar</a>
    </li>
  </ul>
</form>
