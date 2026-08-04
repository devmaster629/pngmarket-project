$(document).ready(function() {

  // SELECT - DESELECT ALL ON SEARCH PAGE
  $('body').on('click', '#atr-search .atr-select-deselect', function(e){
    // ajax search of theme is ON
    if ( !(typeof(ajaxSearch) !== 'undefined' && ajaxSearch == 1) ) {
      e.preventDefault();
    }

    if($(this).hasClass('atr-select-all')) {
      if($(this).closest('.atr-subtype-boxed').length) {
        $(this).nextAll('li').find('input[type="checkbox"]:not(:checked) + label').change(); 
      }

      $(this).nextAll('li').find('input[type="checkbox"]').prop('checked', true); 

    } else {
      if($(this).closest('.atr-subtype-boxed').length) {
        $(this).closest('.controls').find('.atr-holder').html($(this).closest('.atr-subtype-boxed').find('.atr-box').attr('data-empty'));
      }

      $(this).nextAll('li').find('input[type="checkbox"]').prop('checked', false); 
    }

    $(this).hide(0);
    $(this).siblings('.atr-select-deselect').show(0);
  });


  // SELECT - DESELECT ALL ON PUBLISH PAGE
  $('body').on('click', '#atr-form .atr-select-deselect', function(e){
    e.preventDefault();

    if($(this).hasClass('atr-select-all')) {
      if($(this).closest('.atr-subtype-boxed').length) {
        $(this).prevAll('li').find('input[type="checkbox"]:not(:checked) + label').click(); 
      }

      $(this).prevAll('li').find('input[type="checkbox"]').prop('checked', true); 

    } else {
      if($(this).closest('.atr-subtype-boxed').length) {
        $(this).prevAll('li').find('input[type="checkbox"]:checked + label').click(); 
      }

      $(this).prevAll('li').find('input[type="checkbox"]').prop('checked', false); 
    }

    $(this).hide(0);
    $(this).siblings('.atr-select-deselect').show(0);
  });


  // ENSURE CHEBOX SINGLE WORKS AS RADIO BUTTONS ON PUBLISH PAGE - LABEL CLICK
  $('body').on('click', '.atr-check-options-single ul.atr-ul-checkbox label', function(e){
    var elem = $(this).siblings('input[type="checkbox"]');
    $(this).closest('ul.atr-ul-checkbox').find('input[type="checkbox"]:checked').not(elem).prop('checked', false);
  });

  // ENSURE CHEBOX SINGLE WORKS AS RADIO BUTTONS ON PUBLISH PAGE - INPUT CLICK
  $('body').on('click', '.atr-check-options-single ul.atr-ul-checkbox input[type="checkbox"]', function(e){
    var elem = $(this);
    $(this).closest('ul.atr-ul-checkbox').find('input[type="checkbox"]:checked').not(elem).prop('checked', false);
  });

  // BOXED SELECT FUNCTIONALITY
  if (($(window).width() + atrScrollCompensate()) > 767) {
    $('body').on('mouseenter', '.atr-subtype-boxed .controls', function() {
      $(this).find('.atr-ul').fadeIn(200); 
    }).on('mouseleave', '.atr-subtype-boxed .controls', function() {
      $(this).find('.atr-ul').fadeOut(200); 
    });
 
  } else {
    $('body').on('click', '#atr-search .atr-subtype-boxed .atr-box', function(e) {
      e.preventDefault();
      
      if($(this).hasClass('opened')) {
        $(this).removeClass('opened');
        $(this).siblings('.atr-ul').fadeOut(200); 

      } else {
        $(this).addClass('opened');
        $(this).siblings('.atr-ul').fadeIn(200); 

      }
    });

    // WHEN CLICK OUTSIDE ITEM PICKER, HIDE SELECTION
    $(document).mouseup(function (e){
      var container = $(".atr-subtype-boxed .controls");

      if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.find('.atr-box').removeClass('opened');
        container.closest('#atr-search').removeClass('has-opened');
        container.find('.atr-ul').fadeOut(200); 
      }
    });
  }


  // BOXED CHECKBOX/RADIO LAYOUT - UPDATE STRING
  $('body').on('click change', '.atr-subtype-boxed label:not(.control-label)', function(){

    var inputElem = $(this).parent().find('input');
    var inputLabel = $(this).parent().find('label').text();
    var boxText = $(this).closest('.atr-subtype-boxed').find('.atr-holder').text();

    if(!inputElem.is(":checked")) {
      if(!inputElem.closest('.atr-subtype-boxed').find('.atr-holder > span').length || inputElem.attr('type') == 'radio') {
        inputElem.closest('.atr-subtype-boxed').find('.atr-holder').text('');
      }

      inputElem.closest('.atr-subtype-boxed').find('.atr-holder').append('<span>' + inputLabel + '</span>');

    } else {
      inputElem.closest('.atr-subtype-boxed').find('.atr-holder span').each(function() {
        if($(this).text() == inputLabel) {
          $(this).remove();
        }
      });
    }


    if(!inputElem.closest('.atr-subtype-boxed').find('.atr-holder > span').length) {
      inputElem.closest('.atr-subtype-boxed').find('.atr-holder').append(inputElem.closest('.atr-subtype-boxed').find('.atr-box').attr('data-empty'));
    }
  });



  // ENSURE RADIO BUTTONS BEHAVES LIKE RADIO
  $('body').on('change', 'ul.atr-ul-radio input[type=radio]', function(){
    $(this).closest('ul.atr-ul-radio').find('input[type=radio]:checked').not(this).prop('checked', false);
  });


  // SELECT CASCADE
  $('body').on('change', '.atr-form select, .atr-search select', function(){
    if(!$(this).hasClass('no-refresh') && !$(this).hasClass('no-children')) {
      var valId = $(this).val();
      var atrId = $(this).attr('data-atr-id');
      var level = parseInt($(this).attr('data-level'));
      var parentId = $(this).attr('data-parent-id');
      var elem = $(this);
      var block = $(this).closest('.controls');

      block.find('input[type="hidden"][name="atr_' + atrId + '"]').val(valId);
      elem.nextAll('select').remove(); 
      elem.parent('.select-box').nextAll('.select-box').remove();    // bender compatibility

      var url = atr_select_url + '&atrId=' + atrId + '&atrValId=' + valId + '&atrLevel=' + (level + 1);

      if($(this).closest('.atr-search').length) {
        url += '&isSearch=1';
      } if($(this).closest('.atr-form').length) {
        url += '&isPublish=1';
      }

      if(valId != '' && valId > 0 && !$(this).parent().hasClass('atr-linked')) {
        block.addClass('atr-loading');

        $.ajax({
          url: url,
          type: "GET",
          success: function(response){
            //console.log(response);

            block.removeClass('atr-loading');

            if(response !== false) {
              elem.after(response);
            }
          },
          error: function(response) {
            block.removeClass('atr-loading');

            console.log(response);
          }
        });
      }
    }
  });


  // MULTI-LEVEL CHECKBOXES, ON PARENT SELECT ALL CHILDREN - SHOULD BE LAST
  $('body').on('click', '#atr-search .atr-type-checkbox label', function(e){
    var inptLevel = $(this).closest('li').attr('data-level');

    if(!$(this).siblings('input[type="checkbox"]').is(':checked')) {
      var inptChecked = true;
    } else {
      var inptChecked = false;
    }

    $(this).closest('li').nextAll('li').each(function() {
      var thisLi = $(this);

      if(thisLi.attr('data-level') > inptLevel) {
        thisLi.find('input[type="checkbox"]').prop('checked', inptChecked);
      } else {
        return false; 
      }
    });
  });

});


// CALCULATE SCROLL WIDTH
function atrScrollCompensate() {
  var inner = document.createElement('p');
  inner.style.width = "100%";
  inner.style.height = "200px";

  var outer = document.createElement('div');
  outer.style.position = "absolute";
  outer.style.top = "0px";
  outer.style.left = "0px";
  outer.style.visibility = "hidden";
  outer.style.width = "200px";
  outer.style.height = "150px";
  outer.style.overflow = "hidden";
  outer.appendChild(inner);

  document.body.appendChild(outer);
  var w1 = inner.offsetWidth;
  outer.style.overflow = 'scroll';
  var w2 = inner.offsetWidth;
  if (w1 == w2) w2 = outer.clientWidth;

  document.body.removeChild(outer);

  return (w1 - w2);
}