$(document).ready(function(){

  // CREATE VALUES FROM LIST
  $('body').on('click', '.mb-val-footer .submit-list', function(e){
    e.preventDefault();
    var elem = $('.mb-val-footer .add');
    var attributeId = elem.attr('data-attribute-id');
    var locale = elem.attr('data-locale');

    if($('.mb-val-footer #add-list').val() != '') {
      var vals = $('.mb-val-footer #add-list').val();
      vals = vals.split(';');

      elem.find('i').removeClass('fa-plus-circle').addClass('fa-cog fa-spin');

      $.each(vals, function(i, val) {
        window.setTimeout(function(){
          $.ajax({
            url: atr_add_value_url + attributeId + '&name=' + encodeURIComponent(val) + '&locale=' + locale,
            type: "GET",
            success: function(response){
              //console.log(response);
              elem.closest('.mb-field').find('ol.sortable').append(response);
              elem.find('i').removeClass('fa-cog fa-spin').addClass('fa-plus-circle');

              //elem.closest('.mb-field').find('ol.sortable').animate({ scrollTop: elem.closest('.mb-field').find('ol.sortable li').last().offset().top }, 0);
              $('.mb-field ol.sortable').scrollTop($('.mb-field ol.sortable')[0].scrollHeight);

              elem.closest('.mb-field').find('.mb-values .mb-val-empty').hide(0);

              atr_message(atr_message_ok, 'ok');
            },
            error: function(response) {
              atr_message(atr_message_error, 'error');
              console.log(response);
            }
          });
        }, 250 + (i * 250));

      });
    }

    $('.mb-val-footer #add-list').val('');
  });

 
  // SHOW-HIDE TREE
  $('body').on('click', '.mb-values .show-hide', function(e){
    e.preventDefault();
    $(this).find('i').toggleClass('fa-angle-up fa-angle-down');
    $(this).parent('div').siblings('ol').slideToggle(200);
  });


  // HIDE SHOW-HIDE ON LOAD, IF NOT REQUIRED
  $('.mb-field').each(function(){
    var fType = $(this).find('select[name="s_type"]').val();

    if(fType != 'SELECT') {
      $(this).find('.mb-val .show-hide').hide(0);
    }
  });



  // TYPE CHANGE
  $('body').on('change', '.mb-field select[name="s_type"]', function(e){
    var tVal = $(this).val();
    var tField = $(this).closest('.mb-field');
    
    
    // Linked-to-attribute setup
    if(tVal == 'SELECT' || tVal == 'TEXT' || tVal == 'NUMBER' || tVal == 'DATE' || tVal == 'URL' || tVal == 'PHONE' || tVal == 'EMAIL' || tVal == 'DIVIDER') {
      tField.find('select[name="fk_i_linked_to_attr_id"]').closest('.mb-line').show(0);
      tField.find('input[name="b_values_all"]').closest('.mb-line').hide(0);
      tField.find('input[name="b_search_range"]').closest('.mb-line').hide(0);
      tField.find('select[name="s_search_type"]').closest('.mb-line').hide(0);
      tField.find('select[name="s_search_engine"]').closest('.mb-line').hide(0);
      
    } else {
      tField.find('select[name="fk_i_linked_to_attr_id"]').closest('.mb-line').hide(0);
      tField.find('input[name="b_values_all"]').closest('.mb-line').show(0);
      tField.find('input[name="b_search_range"]').closest('.mb-line').show(0);
      tField.find('select[name="s_search_type"]').closest('.mb-line').show(0);
      tField.find('select[name="s_search_engine"]').closest('.mb-line').show(0);
    }

    if(tVal == 'SELECT' || tVal == 'RADIO' || tVal == 'CHECKBOX') {
      tField.find('select[name="s_search_type"]').closest('.mb-line').show(0);
      tField.find('select[name="s_search_engine"]').closest('.mb-line').show(0);
      tField.find('select[name="s_search_values_all"]').closest('.mb-line').show(0);

      tField.find('ol.sortable, a.add').show(0);
      tField.find('.mb-val-notallowed').hide(0);
      tField.find('.atr-show-all').show(0);
      tField.find('.mb-val-footer').show(0);

      if(tField.find('ol.sortable li').length > 0) {
        tField.find('.mb-val-empty').hide(0);
      } else {
        tField.find('.mb-val-empty').show(0);
      }

      if(tVal == 'SELECT') {
        tField.find('ol.sortable').addClass('is-tree');
        tField.find('.mb-val .show-hide').show(0);

      } else {
        tField.find('ol.sortable').removeClass('is-tree');
        tField.find('.mb-val .show-hide').hide(0);

      }
      
    } else {
      tField.find('select[name="s_search_type"]').val('');
      tField.find('select[name="s_search_type"]').closest('.mb-line').hide(0);
      tField.find('select[name="s_search_engine"]').closest('.mb-line').hide(0);
      tField.find('select[name="s_search_values_all"]').closest('.mb-line').hide(0);

      tField.find('ol.sortable, a.add').hide(0);
      tField.find('.mb-val-notallowed').show(0);
      tField.find('.atr-show-all').hide(0);
      tField.find('.mb-val-empty').hide(0);
      tField.find('ol.sortable').removeClass('is-tree');
      tField.find('.mb-val .show-hide').hide(0);
      tField.find('.mb-val-footer').hide(0);
    }


    if(tVal == 'DATE' || tVal == 'DATERANGE' || tVal == 'URL' || tVal == 'PHONE' || tVal == 'EMAIL' || tVal == 'DIVIDER') {
      tField.find('input[name="b_search_range"]').closest('.mb-line').hide(0);
      tField.find('input[name="b_search_range"]').prop('checked', false);
      tField.find('input[name="b_search_range"]').bootstrapSwitch("state", false);
    } else {
      tField.find('input[name="b_search_range"]').closest('.mb-line').show(0);
    }

    if(tVal == 'RADIO' || tVal == 'CHECKBOX' || tVal == 'DIVIDER') {
      tField.find('select[name="s_search_type"] option[value="BOXED"]').prop('disabled', false);
    } else {
      tField.find('select[name="s_search_type"] option[value="BOXED"]').prop('disabled', true);
    }

    if(tVal == 'CHECKBOX') {
      tField.find('input[name="b_check_single"]').closest('.mb-line').show(0);
    } else {
      tField.find('input[name="b_check_single"]').closest('.mb-line').hide(0);
      tField.find('input[name="b_check_single"]').prop('checked', false);
      tField.find('input[name="b_check_single"]').bootstrapSwitch("state", false);
    }

    if(tVal == 'DIVIDER') {
      tField.find('select[name="fk_i_linked_to_attr_id"]').closest('.mb-line').hide(0);
      tField.find('input[name="s_icon"]').closest('.mb-line').hide(0);
      tField.find('input[name="b_required"]').closest('.mb-line').hide(0);
    } else {
      tField.find('select[name="fk_i_linked_to_attr_id"]').closest('.mb-line').show(0);
      tField.find('input[name="s_icon"]').closest('.mb-line').show(0);
      tField.find('input[name="b_required"]').closest('.mb-line').show(0);
    }
  });



  if($('.mb-field select[name="fk_i_linked_to_attr_id"]').val() > 0) {
    $('.mb-field').find('input[name="b_values_all"]').closest('.mb-line').hide(0);
    $('.mb-field').find('input[name="b_search_range"]').closest('.mb-line').hide(0);
    $('.mb-field').find('select[name="s_search_type"]').closest('.mb-line').hide(0);
    $('.mb-field').find('select[name="s_search_engine"]').closest('.mb-line').hide(0);
  } else {
    $('.mb-field').find('input[name="b_values_all"]').closest('.mb-line').show(0);
    $('.mb-field').find('input[name="b_search_range"]').closest('.mb-line').show(0);
    $('.mb-field').find('select[name="s_search_type"]').closest('.mb-line').show(0);
    $('.mb-field').find('select[name="s_search_engine"]').closest('.mb-line').show(0);
  }

  // LINKED-TO CHANGE
  $('body').on('change', '.mb-field select[name="fk_i_linked_to_attr_id"]', function(e){
    var isLinked = ($(this).val() > 0 ? 1 : 0);
    var tVal = $('.mb-field select[name="s_type"]').val();
    var tField = $(this).closest('.mb-field');
    
    
    // Linked-to-attribute setup
    if(tVal == 'SELECT' || tVal == 'TEXT' || tVal == 'NUMBER' || tVal == 'DATE' || tVal == 'URL' || tVal == 'PHONE' || tVal == 'EMAIL') {
      tField.find('select[name="fk_i_linked_to_attr_id"]').closest('.mb-line').show(0);
    } else {
      tField.find('select[name="fk_i_linked_to_attr_id"]').closest('.mb-line').hide(0).val('');
    }
    
    if(isLinked == 1) {
      tField.find('input[name="b_values_all"]').closest('.mb-line').hide(0);
      tField.find('input[name="b_search_range"]').closest('.mb-line').hide(0);
      tField.find('select[name="s_search_type"]').closest('.mb-line').hide(0);
      tField.find('select[name="s_search_engine"]').closest('.mb-line').hide(0);
      
    } else {
      tField.find('input[name="b_values_all"]').closest('.mb-line').show(0);
      tField.find('input[name="b_search_range"]').closest('.mb-line').show(0);
      tField.find('select[name="s_search_type"]').closest('.mb-line').show(0);
      tField.find('select[name="s_search_engine"]').closest('.mb-line').show(0);
    }
  });
  

  // ADD VALUE
  $('body').on('click', '.mb-val-footer a.add', function(e){
    e.preventDefault();
    var elem = $(this);
    var attributeId = $(this).attr('data-attribute-id');
     
    if(attributeId > 0) {
      $(this).find('i').removeClass('fa-plus-circle').addClass('fa-cog fa-spin');

      $.ajax({
        url: atr_add_value_url + attributeId,
        type: "GET",
        success: function(response){
          //console.log(response);
          elem.closest('.mb-field').find('ol.sortable').append(response);
          elem.find('i').removeClass('fa-cog fa-spin').addClass('fa-plus-circle');

          //elem.closest('.mb-field').find('ol.sortable').animate({ scrollTop: elem.closest('.mb-field').find('ol.sortable li').last().offset().top }, 0);
         $('.mb-field ol.sortable').scrollTop($('.mb-field ol.sortable')[0].scrollHeight);

          elem.closest('.mb-field').find('.mb-values .mb-val-empty').hide(0);

          atr_message(atr_message_ok, 'ok');
        },
        error: function(response) {
          atr_message(atr_message_error, 'error');
          console.log(response);
        }
      });
    }
  });



  // REMOVE VALUE
  $('body').on('click', '.mb-val .remove', function(e){
    e.preventDefault();
    var elem = $(this);
    var removeId = $(this).attr('data-id');
     
    if(removeId > 0) {
      $(this).find('i').removeClass('fa-times').addClass('fa-cog fa-spin');

      $.ajax({
        url: atr_remove_value_url + removeId,
        type: "GET",
        success: function(response){
          elem.closest('.mb-val').fadeOut(200, function() {
            if(elem.closest('.mb-field').find('.mb-values ol li').length <= 1) {
              elem.closest('.mb-field').find('.mb-values .mb-val-empty').show(0);
            }

            elem.closest('.mb-val').remove();
          });


          atr_message(atr_message_ok, 'ok');
        },
        error: function(response) {
          atr_message(atr_message_error, 'error');
          console.log(response);
        }
      });
    }
  });


  // REMOVE NEW VALUE CLASS
  $('body').on('click', '.mb-val-new', function(){
    $(this).removeClass('mb-val-new');
  });


  // HIDE MESSAGE
   $('body').on('click', '.mb-message-js', function(e){
     e.preventDefault();
     $('.mb-message-js > div').fadeOut(300, function() {
       $('.mb-message-js > div').remove();
     });
  });
   
  

  // SHOW-HIDE FIELDS
//   $('body').on('click', '.mb-field .mb-top-line', function(e){
//     e.preventDefault();
//     $(this).closest('.mb-field').toggleClass('opened');
//     $(this).closest('.mb-field').find('.mb-details, .mb-foot').fadeToggle(0);
//     $(this).closest('.mb-field').find('.mb-top-line .show i').toggleClass('fa-angle-down fa-angle-up');
//   });



  // CATEGORY MULTI SELECT
  $('body').on('change', '.mb-row-select-multiple select', function(e){
    $(this).closest('.mb-row-select-multiple').find('input[type="hidden"]').val($(this).val());
  });



  // ON LOCALE CHANGE RELOAD PAGE
  $('body').on('change', 'select.mb-select-locale', function(e){
    window.location.replace($(this).attr('rel') + "&atrLocale=" + $(this).val());
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
