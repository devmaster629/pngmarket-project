$(document).ready(function(){

  // AUTO-EXPAND TEXTAREA
  $('body').on('change keyup keydown paste cut', 'textarea#im-message', function () {
    $(this).height(0).height(this.scrollHeight - 30);
    
    if(this.scrollHeight > 455) {
      $(this).css('overflow-y', 'scroll');
      $('body #im-message-form button.im-button-alt').css('right', '17px');
      $('body #im-message-form .im-attachment').css('right', '68px');
    }
  });


  // SUBMIT MESSAGE WITH CTRL+ENTER
  $('body').on('keydown', 'textarea#im-message', function(e) {
    if((e.ctrlKey || e.metaKey) && (e.key === 'Enter' || e.keyCode === 13)) {
      e.preventDefault();
      $('body #im-message-form button.im-button-alt').trigger('click');
    }
  });


  // TOOLTIPS IN USER ACCOUNT
  Tipped.create('.im-has-tooltip, .im-tooltip', { maxWidth: 200, radius: false });
  Tipped.create('.im-has-tooltip-left', { maxWidth: 200, radius: false } );


  // ATTACHMENT NAME
  $('input[name="im-file"]').change(function() {
    if( $(this)[0].files[0]['name'] != '' ) {
      $('.im-attachment .im-att-box .im-status .im-wrap span').text( $(this)[0].files[0]['name'] );
      $('#im-message').removeAttr('required').removeClass('error');
    }
  });

  // Whole conversation row opens the thread
  $('body').on('click', '.im-threads .im-table-row', function(e) {
    if($(e.target).closest('a, button, input, label').length) {
      return;
    }

    var href = $(this).attr('data-href') || $(this).find('a.im-mes-title').attr('href');
    if(href) {
      window.location.href = href;
    }
  });


  // FORM VALIDATION
  if($('form.im-form-validate').length) {
    var imRqName2  = (typeof imRqName  !== 'undefined') ? imRqName  : 'Your Name: This field is required!';
    var imDsName2  = (typeof imDsName  !== 'undefined') ? imDsName  : 'Your Name: Name is too short!';
    var imRqEmail2 = (typeof imRqEmail !== 'undefined') ? imRqEmail : 'Your Email: This field is required!';
    var imDsEmail2 = (typeof imDsEmail !== 'undefined') ? imDsEmail : 'Your Email: Email your have entered is not in valid format!';
    var imRqMessage2 = (typeof imRqMessage !== 'undefined') ? imRqMessage : 'Message: This field is required!';
    var imDsMessage2 = (typeof imDsMessage !== 'undefined') ? imDsMessage : 'Message: Message is too short!';

    $('form.im-form-validate').validate({
      rules: {
        "im-from-user-name": {
          required: true,
          minlength: 3
        },
        
        "im-from-user-email": {
          required: true,
          email: true
        },
        
        /*
        "im-title": {
          required: true,
          minlength: 2
        },
        */
        
        "im-message": {
          required: {
            depends: function () {
              var fileInput = $('input[name="im-file"]');
              return !(fileInput.length && fileInput.val());
            }
          },
          minlength: {
            depends: function () {
              return $.trim($('#im-message').val() || '').length > 0;
            },
            param: 2
          }
        }
      },
      
      messages: {
        "im-from-user-name": {
          required: imRqName2,
          minlength: imDsName2
        },
        
        "im-from-user-email": {
          required: imRqEmail2,
          email: imDsEmail2
        },
        
        "im-message": {
          required: imRqMessage2,
          minlength: imDsMessage2
        },
      },
      
      wrapper: "li",
      errorLabelContainer: "#im-error-list",
      invalidHandler: function(form, validator) {
        $('html,body').animate({ scrollTop: $('#im-error-list').offset().top - 100 }, { duration: 250, easing: 'swing'});
      },
      submitHandler: function(form){
        $('button[type=submit], input[type=submit]').attr('disabled', 'disabled');
        form.submit();
      }
    });
  }

});


// Toggle send button icon (paper plane / spinner)
function imSubmitButtonLoading(button, loading) {
  var icon = $(button).find('i').first();
  if(!icon.length) {
    return;
  }

  if(loading) {
    icon.removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');
  } else {
    icon.removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
  }
}

