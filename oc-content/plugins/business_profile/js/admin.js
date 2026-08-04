$(document).ready(function(){


  // USER LOOKUP WALLET
  var name = $('input[name="name"].mb-user-lookup');

  if(name.length) {
    name.prop('autocomplete', 'off');
    
    name.autocomplete({
      source: user_lookup_base,
      minLength: 0,
      select: function (event, ui) {
        if (ui.item.id == '') {
          return false;
        } else {
          //console.log(ui);
        }

        $('input[name="fk_i_user_id"]').val(ui.item.id);
      },
      search: function () {
        $('input[name="fk_i_user_id"]').val('');
      }
    });

    $('.ui-autocomplete').css('zIndex', 10000);
  }


  // COLOR PICKER
  $('body').on('change', '.mb-color-box input[type="text"]', function() {
    $(this).closest('.mb-color-box').find('input[type="color"]').val($(this).val());
  });

  $('body').on('change', '.mb-color-box input[type="color"]', function() {
    $(this).closest('.mb-color-box').find('input[type="text"]').val($(this).val());
  });
  

  // REMOVE FEATURE / PAYMENT
  $('body').on('click', '.mb-remove-line', function(e){
    e.preventDefault();
    $(this).parent().remove();
  });



  // ADD FEATURE / PAYMENT
  $('body').on('click', 'a.mb-add-new', function(e){
    e.preventDefault();
    var cont = $(this).siblings('.mb-row-placeholder').html();

    $(this).parent().find('.mb-fp-list').append(cont);
    ($(this).parent().find('.mb-fp-list .mb-row').last()).find('input').attr('name', 'bpr-fp_-' + $(this).attr('data-id')).attr('required', true);

    $(this).attr('data-id', parseInt($(this).attr('data-id')) + 1);

    $(this).siblings('.mb-fp-list').animate({ scrollTop: $(this).siblings('.mb-fp-list').prop('scrollHeight')}, 150);

  });

 
  // CATEGORY MULTI SELECT
  $('body').on('change', '.mb-row-select-multiple select', function(e){
    $(this).closest('.mb-row-select-multiple').find('input[type="hidden"]').val($(this).val());
  });



  // ON LOCALE CHANGE RELOAD PAGE
  $('body').on('change', 'select.mb-select-locale', function(e){
    window.location.replace($(this).attr('rel') + "&bprLocale=" + $(this).val());
  });


  // HELP TOPICS
  $('#mb-help > .mb-inside > .mb-row.mb-help > div').each(function(){
    var cl = $(this).attr('class');
    $('label.' + cl + ' span').addClass('mb-has-tooltip').prop('title', $(this).text());
  });

  $('.mb-row label').click(function() {
    var cl = $(this).attr('class');
    var pos = $('#mb-help > .mb-inside > .mb-row.mb-help > div.' + cl).offset().top - $('.navbar').outerHeight() - 12;;
    $('html, body').animate({
      scrollTop: pos
    }, 1400, function(){
      $('#mb-help > .mb-inside > .mb-row.mb-help > div.' + cl).addClass('mb-help-highlight');
    });

    return false;
  });


  // ON-CLICK ANY ELEMENT REMOVE HIGHLIGHT
  $('body, body *').click(function(){
    $('.mb-help-highlight').removeClass('mb-help-highlight');
  });


  // GENERATE TOOLTIPS
  Tipped.create('.mb-has-tooltip', { maxWidth: 200, radius: false });
  Tipped.create('.mb-has-tooltip-user', { maxWidth: 350, radius: false, size: 'medium' });
  Tipped.create('.mb-has-tooltip-light', { maxWidth: 200, radius: false, size: 'medium' });


  // CHECKBOX & RADIO SWITCH
  $.fn.bootstrapSwitch.defaults.size = 'small';
  $.fn.bootstrapSwitch.defaults.labelWidth = '0px';
  $.fn.bootstrapSwitch.defaults.handleWidth = '50px';

  $(".element-slide").bootstrapSwitch();



  // MARK ALL
  $('input.mb_mark_all').click(function(){
    if ($(this).is(':checked')) {
      $('input[name^="' + $(this).val() + '"]').prop( "checked", true );
    } else {
      $('input[name^="' + $(this).val() + '"]').prop( "checked", false );
    }
  });


});


var timeoutHandle;

function atr_message($html, $type = '') {
  window.clearTimeout(timeoutHandle);

  $('.mb-message-js').fadeOut(0);
  $('.mb-message-js').attr('class', '').addClass('mb-message-js').addClass($type);
  $('.mb-message-js').fadeIn(200).html('<div>' + $html + '</div>');

  var timeoutHandle = setTimeout(function(){
    $('.mb-message-js > div').fadeOut(300, function() {
      $('.mb-message-js > div').remove();
    });
  }, 10000);
}
