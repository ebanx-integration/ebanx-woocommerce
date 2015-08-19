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

  $('#ebanx_method_boleto, #ebanx_method_creditcard, #ebanx_method_tef').on('click', function() {
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
});