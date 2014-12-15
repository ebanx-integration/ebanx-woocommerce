<li>
  <label for="ebanx_cpf" class="required">CPF</label>
  <div class="input-box">
    <input type="text" title="CPF" class="input-text required-entry validate-cpf" id="ebanx_document" name="ebanx[document]"
    value="<?php echo isset($_POST['ebanx']['document']) ? $_POST['ebanx']['document'] : $ebanxDocument ?>">
  </div>
</li>

<li>
  <label for="ebanx_birth_day" class="required">Data de nascimento</label>
  <div class="input-box ebanx-birth">
    <div class="v-fix">
      <select id="ebanx_birth_day" name="ebanx[birth_day]" class="day required-entry" autocomplete="off">
        <option value="" selected="selected">Dia</option>
        <?php for ($i = 1; $i <= 31; $i++): ?>
          <option value="<?php echo $i ?>" <?php if (isset($birthDate['day']) && $birthDate['day'] == $i) echo 'selected'?>>
            <?php echo $i ?>
          </option>
        <?php endfor ?>
      </select>
    </div>

    <div class="v-fix">
      <select id="ebanx_birth_month" name="ebanx[birth_month]" class="month required-entry" autocomplete="off">
        <option value="" selected="selected">Mês</option>
        <?php for ($i = 1; $i <= 12; $i++): ?>
          <option value="<?php echo $i ?>" <?php if (isset($birthDate['month']) && $birthDate['month'] == $i) echo 'selected'?>>
            <?php echo date("F", mktime(0, 0, 0, $i, 10)) ?>
          </option>
        <?php endfor ?>
      </select>
    </div>

    <div class="v-fix">
      <select id="ebanx_birth_year" name="ebanx[birth_year]" class="year required-entry" autocomplete="off">
        <option value="" selected="selected">Ano</option>
        <?php for ($i = date('Y') - 16; $i > 1920; $i--): ?>
          <option value="<?php echo $i ?>" <?php if (isset($birthDate['year']) && $birthDate['year'] == $i) echo 'selected'?>>
            <?php echo $i ?>
          </option>
        <?php endfor ?>
      </select>
    </div>
  </div>
</li>