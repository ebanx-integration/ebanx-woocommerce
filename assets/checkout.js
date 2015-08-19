jQuery(document).ready(function() {
  var ebanxRadio      = document.getElementById('payment_method_ebanx')
    , ebanxDirectForm = document.getElementById('payment_ebanx_direct')
    , fieldIds = [
        'ebanx_cpf'
      , 'ebanx_cc_name'
      , 'ebanx_cc_type'
      , 'ebanx_cc_number'
      , 'ebanx_cc_cvv'
      , 'ebanx_cc_expiration_month'
      , 'ebanx_cc_expiration_year'
      , 'ebanx_birth_day'
      , 'ebanx_birth_month'
      , 'ebanx_birth_year'
      , 'ebanx_method_boleto'
      , 'ebanx_method_creditcard'
      , 'ebanx_method_tef'
      , 'ebanx_cc_installments'
      , 'ebanx_tef_bank'
    ];

  var $ = jQuery;

  /**
   * Toggle credit card fields when changing the EBANX payment method
   * @return void
   */
  function toggleCCFields() {
    var radio  = document.getElementById('ebanx_method_creditcard')
      , fields = document.getElementsByClassName('ebanx-cc-field');

    if (radio) {
      for (i = 0; i < fields.length; i++) {
        if (radio.checked == true) {
          fields[i].style.display = 'block';
        } else {
          fields[i].style.display = 'none';
        }
      }
    }
  }

  /**
   * Toggle TEF fields when changing the EBANX payment method
   * @return void
   */
  function toggleTEFFields() {
    var radio  = document.getElementById('ebanx_method_tef')
      , fields = document.getElementsByClassName('ebanx-tef-field');

    if (radio) {
      for (i = 0; i < fields.length; i++) {
        if (radio.checked == true) {
          fields[i].style.display = 'block';
        } else {
          fields[i].style.display = 'none';
        }
      }
    }
  }

  $('#ebanx_method_boleto, #ebanx_method_creditcard, #ebanx_method_tef, #ebanx_method_pagoefectivo').on('click', function() {
    toggleCCFields();
    toggleTEFFields();
  });
  /**
   * Toggle the active payment method class
   */
  var setActivePaymentMethod = function() {
    var methods = document.getElementsByClassName('payment-method-toggle');
    var clickEvent = function() {
      for (var i = 0; i < methods.length; i++) {
        methods[i].className = methods[i].className.replace(/active/g, '');
      }

      this.className += ' active';
    };

    for (var i = 0; i < methods.length; i++) {
      methods[i].onclick = clickEvent;
    }
  }();

 /**
   * Validates the CPF number
   *
   */
  function validateCpf(cpf) {
    var digits = cpf.replace(/[\D]/g, '')
      , dv1, dv2, sum, mod;

    if (digits.length == 11) {
      d = digits.split('');

      sum = d[0] * 10 + d[1] * 9 + d[2] * 8 + d[3] * 7 + d[4] * 6 + d[5] * 5 + d[6] * 4 + d[7] * 3 + d[8] * 2;
      mod = sum % 11;
      dv1 = (11 - mod < 10 ? 11 - mod : 0);

      sum = d[0] * 11 + d[1] * 10 + d[2] * 9 + d[3] * 8 + d[4] * 7 + d[5] * 6 + d[6] * 5 + d[7] * 4 + d[8] * 3 + dv1 * 2;
      mod = sum % 11;
      dv2 = (11 - mod < 10 ? 11 - mod : 0);

      return dv1 == d[9] && dv2 == d[10];
    }

    return false;
  }

  /**
   * Updates the credit card issuer depending on its number
   */
  function updateCCIssuer() {
    var ccNumber = this.value
      , ccType   = document.getElementById('ebanx_cc_type');

    function toggleType(type) {
      ccType.value = type;
    }

    if (ccNumber.match(/^4[0-9]{12}(?:[0-9]{3})?$/)) {
      toggleType('visa');
    } else if (ccNumber.match(/^5[1-5][0-9]{14}$/)) {
      toggleType('mastercard');
    } else if (ccNumber.match(/^3[47][0-9]{13}$/)) {
      toggleType('amex');
    } else if (ccNumber.match(/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/)) {
      toggleType('diners');
    } else if (ccNumber.match(/^6(?:011|5[0-9]{2})[0-9]{12}$/)) {
      toggleType('discover');
    } else if (ccNumber.match(/^(636368|438935|504175|451416|636297|5067|4576|4011)/)) {
      toggleType('elo');
    } else if (ccNumber.match(/^50[0-9]{14,17}$/)) {
      toggleType('aura');
    } else if (ccNumber.match(/^(38|60)[0-9]{11,17}$/)) {
      toggleType('hipercard');
    } else {
      toggleType('');
    }
  }

  var ccNumber = document.getElementById('ebanx_cc_number');
  if (ccNumber) {
    ccNumber.onkeydown = updateCCIssuer;
    ccNumber.onchange  = updateCCIssuer;
    ccNumber.oninput   = updateCCIssuer;
  }

  /**
   * Validates a credit card number
   * https://gist.github.com/ShirtlessKirk/2134376
   * @param  string cardNumber
   * @return boolean
   */
  function luhnCheck(cardNumber) {
    var cardNumber = cardNumber.replace(/\D/g, '')
      , len = cardNumber.length
      , mul = 0
      , prodArr = [[0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [0, 2, 4, 6, 8, 1, 3, 5, 7, 9]]
      , sum = 0;

    while (len--) {
        sum += prodArr[mul][parseInt(cardNumber.charAt(len), 10)];
        mul ^= 1;
    }

    return sum % 10 === 0 && sum > 0;
  };

  /**
   * Validates the EBANX checkout form data
   * @return boolean
   */
  $('#ebanx-checkout-form').on('submit', function() {
    var cpf    = $('#ebanx_cpf').val()
      , bDay   = $('#ebanx_birth_day').val()
      , bMonth = $('#ebanx_birth_month').val()
      , bYear  = $('#ebanx_birth_year').val()
      , paymentMethod = $('input[name="ebanx[method]"]:checked');

      if (!cpf || !validateCpf(cpf)) {
        alert('O CPF digitado não é válido.');
        return false;
      }

      if (!paymentMethod) {
        alert('É necessário escolher o método de pagamento.');
        return false;
      }

      if (!bDay || !bMonth || !bYear) {
        alert('É necessário informar a data de nascimento.');
        return false;
      }

      // Validate TEF payments
      if (paymentMethod.val() == 'tef') {
        var bank = $('#ebanx_tef_bank').val();

        if (!bank) {
          alert('É necessário escolher o banco para fazer a transferência.');
          return false;
        }
      }

      // Validate credit card
      if (paymentMethod.val() == 'creditcard') {
        var ccName = $('#ebanx_cc_name').val()
          , ccNumber = $('#ebanx_cc_number').val()
          , ccCVV = $('#ebanx_cc_cvv').val()
          , ccScheme = $('#ebanx_cc_type').val()
          , ccExpMonth = $('#ebanx_cc_expiration_month').val()
          , ccExpYear = $('#ebanx_cc_expiration_year').val();

        if (ccName.length == 0) {
          alert('É necessário informar o nome do titular do cartão.');
          return false;
        }

        if (ccNumber.length == 0) {
          alert('É necessário informar o número do cartão.');
          return false;
        }

        if (!luhnCheck(ccNumber)) {
          alert('O número do cartão é inválido.');
          return false;
        }

        if (ccCVV.length < 3 || ccCVV.length > 4) {
          alert('O código de segurança deve conter 3 ou 4 dígitos.');
          return false;
        }

        if (!ccScheme) {
          alert('É necessário selecionar a bandeira do cartão.');
          return false;
        }

        if (ccExpMonth.length == 0 || ccExpYear.length == 0) {
          alert('É necessário informar a data de validade do cartão.');
          return false;
        }
      }
  });
});