$(document).ready(function(){

   // COLOR PICKER
  $('body').on('change', '.mb-color-box input[type="text"]', function() {
    $(this).closest('.mb-color-box').find('input[type="color"]').val($(this).val());
  });

  $('body').on('change', '.mb-color-box input[type="color"]', function() {
    $(this).closest('.mb-color-box').find('input[type="text"]').val($(this).val());
  });


  // CATEGORY MULTI SELECT
  $('body').on('change', '.mb-row-select-multiple select', function(e){
    $(this).closest('.mb-row-select-multiple').find('input[type="hidden"]').val($(this).val());
  });


  // ON SEARCH SELECT/INPUT CHANGE, SUBMIT
  $('body').on('change', '#mb-search-table select, #mb-search-table input', function(e){
    $(this).closest('form').find('button[type="submit"], .mb-button').addClass('mb-loading');
    $(this).closest('form').find('button[type="submit"] i, .mb-button i').removeClass('fa-search').addClass('fa-refresh').addClass('fa-spin');
    $(this).closest('form').submit();
  });
  

  // ON LOCALE CHANGE RELOAD PAGE
  $('body').on('change', 'select.mb-select-locale', function(e){
    window.location.replace($(this).attr('rel') + "&faqLocale=" + $(this).val());
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
  Tipped.create('.mb-has-tooltip', { maxWidth: 200, radius: false, behavior: 'hide' });
  Tipped.create('.mb-has-tooltip-user', { maxWidth: 350, radius: false, size: 'medium', behavior: 'hide' });
  Tipped.create('.mb-has-tooltip-light', { maxWidth: 200, radius: false, size: 'medium', behavior: 'hide' });


  // CHECKBOX & RADIO SWITCH
  $.fn.bootstrapSwitch.defaults.size = 'small';
  $.fn.bootstrapSwitch.defaults.labelWidth = '0px';
  $.fn.bootstrapSwitch.defaults.handleWidth = '50px';

  $(".element-slide").bootstrapSwitch();


  // CHANGE FILE INPUT NAME
  $('input[name="image"]').change(function() {
    if( $(this)[0].files[0]['name'] != '' ) {
      $('.mb-file .wrap > span').text( $(this)[0].files[0]['name'] );
    }
  });

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

function faq_message($html, $type = '') {
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



