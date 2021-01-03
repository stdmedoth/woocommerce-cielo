function checkDigit(input, event, value) {

    var code = (event.which) ? event.which : event.keyCode;

    if ((code < 48 || code > 57) && (code > 31)) {
      return false;
    }

    console.log(input)

    cc_class_name = 'input-text wc-credit-card-form-card-number validate-required';

    if(value.startsWith('38')){
      input.className = cc_class_name + ' dinersclub identified'
    }else
    if(value.startsWith('35')){
      input.className = cc_class_name + ' jcb identified'
    }else
    if(value.startsWith('4')){
      input.className = cc_class_name + ' visa identified'
    }else
    if(value.startsWith('5')){
      input.className = cc_class_name + ' mastercard identified'
    }else
    if(value.startsWith('6')){
      input.className = cc_class_name + ' discover identified'
    }else{
      input.className = cc_class_name
    }

    return true;
}
