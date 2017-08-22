/* Our Rules for this type of form */
var wpi_echeck_rules = {
  "cc_data[first_name]": {
    required: true
  },
  "cc_data[last_name]": {
    required: true
  },
  "cc_data[user_email]": {
    required: true,
    email: true
  },
  "cc_data[phonenumber]": {
    required: true
  },
  "cc_data[address]": {
    required: true
  },
  "cc_data[city]": {
    required: true
  },
  "cc_data[zip]": {
    required: true,
    digits: true
  },
  "cc_data[bank_aba_code]": {
    required: true,
    digits: true,
    maxlength: 9
  },
  "cc_data[bank_acct_num]": {
    required: true,
    digits: true,
    maxlength: 17
  },
  "cc_data[bank_acct_name]": {
    required: true
  }
};

/* Our messages for this type of form */
var wpi_echeck_messages = {
  "cc_data[first_name]": {
    required: "First name is required."
  },
  "cc_data[last_name]": {
    required: "Last name is required."
  },
  "cc_data[user_email]": {
    required: "An e-mail address is required.",
    email: "E-mail address is not valid."
  },
  "cc_data[phonenumber]": {
    required: "Phone Number is required."
  },
  "cc_data[address]": {
    required: "Address line is required."
  },
  "cc_data[city]": {
    required: "City is required."
  },
  "cc_data[zip]": {
    required: "Zip code is required.",
    digits: "Zip code should contain only digits."
  },
  "cc_data[bank_aba_code]": {
    required: "Routing Number is required",
    digits: "Routing Number should contain only digits",
    maxlength: "Routing Number maximum length is 9 digits."
  },
  "cc_data[bank_acct_num]": {
    required: "Account Number is required.",
    digits: "Account Number should contain only digits.",
    maxlength: "Account Number maximum length is 17 digits."
  },
  "cc_data[bank_acct_name]": {
    required: "Bank Account Name is required."
  }
};

/* This function happens when the form is initialized */
var wpi_echeck_init_form = function() {
  jQuery("#online_payment_form_wrapper").trigger('formLoaded');
};

/* This function adds to form validation, and returns true or false */
var wpi_echeck_validate_form = function(){
  return true;
};

/* This function handles the submit event */
var wpi_echeck_submit = function(){
  jQuery( "#cc_pay_button" ).attr("disabled", "disabled");
  jQuery( ".loader-img" ).show();
  var url = wpi_ajax.url+"?action="+jQuery("#wpi_action").val();
  var message = '';
  jQuery.post(url, jQuery("#online_payment_form-wpi_echeck").serialize(), function(d){
    if ( d.success ) {
      jQuery('#trans-results').css({background:"#EDFFDF"});
    } else if ( d.error ) {
      jQuery('#trans-results').css({background:"#FFDFDF"});
    }
    jQuery.each( d.data.messages, function(k, v){
      message += v +'\n\n';
    });
    alert( message );
    location.reload(true);
  }, 'json');
  return false;
};