$(document).ready(function(){

  // SHOW-HIDE LEGAL NOTICE ON ITEM PAGE
  $('body').on('click', '#bpr-notice .bpr-notice-head a', function(e){
    e.preventDefault();
    $(this).parent().toggleClass('opened');
    $(this).closest('#bpr-notice').find('.bpr-notice-text').slideToggle(200);
  });
  
  // SHOW-HIDE LEGAL NOTICE ON SELLER PAGE
  $('body').on('click', '.bpr-legal-notice-sidebar .bpr-legal-notice-head', function(e){
    e.preventDefault();
    $(this).toggleClass('opened');
    $(this).closest('.bpr-legal-notice-sidebar').find('.bpr-legal-notice-text').slideToggle(200);
  });
  
  // LIGHTBOX GALLERY
  if(typeof $.fn.lightGallery !== 'undefined') {
    $('#bpr-gallery, .bpr-preview.bpr-gal').lightGallery({
      mode: 'lg-slide',
      thumbnail: true,
      cssEasing : 'cubic-bezier(0.25, 0, 0.25, 1)',
      selector: 'a.limg',
      getCaptionFromTitleOrAlt: false,
      download: false,
      thumbWidth: 90,
      thumbContHeight: 80,
      share: false
    }); 
  }
  
  // SHOW PHONE NUMBER
  $('body').on('click', '.bpr-phone-mask', function(e) {
    if($(this).attr('href') == '#' && !$(this).hasClass('not-logged')) {
      e.preventDefault()

      var phoneNumber = $(this).attr('data-part1') + $(this).attr('data-part2');
      $(this).text(phoneNumber).addClass('bpr-bold');
      $(this).attr('href', 'tel:' + phoneNumber);
      $(this).attr('title', $(this).attr('data-tip'));
      
      Tipped.remove('.bpr-has-tooltip-phone');
      Tipped.create('.bpr-has-tooltip-phone', { maxWidth: 200, radius: false, behavior: 'hide'});
    }
  });

  // CATEGORY MULTI SELECT - PROFILE
  $('body').on('change', '.bpr-select-cat input[type="checkbox"]', function(e){
    var ids = [];

    $('.bpr-select-cat input[type="checkbox"]').each(function() {
      if($(this).is(":checked")) {
        ids.push($(this).val());
      }
    });

    var idsList = ids.join(',');

    $(this).closest('.bpr-row-select-multiple').find('input[type="hidden"]').val(idsList);
  });


  // SEARCH - CATEGORY/CITY AUTOSUBMIT
  $('body').on('change', '.bpr-search-right select', function(e){
    $(this).closest('form').submit();
  });


  // PROPERLY SUBMIT CONTACT SELLER
  $('body').on('click', 'button.bpr-btn', function(e){
    e.preventDefault();
    $(this).closest('form').submit();
  });
 

  // CATEGORY MULTI SELECT
  $('body').on('change', '.bpr-row-select-multiple select', function(e){
    $(this).closest('.bpr-row-select-multiple').find('input[type="hidden"]').val($(this).val());
  });


  // ICON UPLOAD - FILE NAME
  $('body').on('change', 'input[name="bpr-file-icon"]', function() {
    if( $(this)[0].files[0]['name'] != '' ) {
      $('.bpr-row-icon .bpr-attachment .bpr-att-box .bpr-status .bpr-wrap span').text( $(this)[0].files[0]['name'] );
    }
  });


  // COVER UPLOAD - FILE NAME
  $('body').on('change', 'input[name="bpr-file-cover"]', function() {
    if( $(this)[0].files[0]['name'] != '' ) {
      $('.bpr-row-cover .bpr-attachment .bpr-att-box .bpr-status .bpr-wrap span').text( $(this)[0].files[0]['name'] );
    }
  });


  // GALLERY UPLOAD - FILE NAME
  $('body').on('change', 'input[name="bpr-files-gallery[]"]', function() {
    if( $(this)[0].files[0]['name'] != '' ) {
      var name = [];
      for (let i = 0; i < $(this)[0].files.length; i++) {
        name.push($(this)[0].files[i]['name']); 
      }

      $('.bpr-row-gallery .bpr-attachment .bpr-att-box .bpr-status .bpr-wrap span').text(name.join(', '));
    }
  });
  
  // PAGINATION CLICK - GO TO ITEMS
  if((typeof bprPage !== 'undefined' && bprPage >= 1) || (typeof bprCategory !== 'undefined' && bprCategory > 0) || (typeof bprCity !== 'undefined' && bprCity > 0)) {
    $('html, body').animate({
      scrollTop: $('.bpr-filters').offset().top
    }, 0);
  }


  // FILTER ITEMS BASED ON CITY
  $('body').on('change', 'select#bpr-city', function(e){
    var url = bprSearch;
    var id = $(this).val();

    if(id != bprCity) {
      url = url.replace('{city}', id);
      url = url.replace('{category}', bprCategory);
      url = url.replace('{page}', '');

      window.location.href = url;
    }
  });

  // FILTER ITEMS BASED ON CATEGORY
  $('body').on('change', 'select#bpr-category', function(e){
    var url = bprSearch;
    var id = $(this).val();

    if(id != bprCategory) {
      url = url.replace('{category}', id);
      url = url.replace('{city}', bprCity);
      url = url.replace('{page}', '');

      window.location.href = url;
    }
  });

  // CONTACT DISABLED
  $('body').on('click', 'a.bpr-btn.bpr-disabled', function(e){
    e.preventDefault();
  });


  // CONTACT ALLOWED
  $('body').on('click', 'a.bpr-btn.bpr-go, a.bpr-close', function(e){
    e.preventDefault();
    $('a.bpr-btn.bpr-go').fadeToggle(200);
    $('#sellerContact').fadeToggle(200);
  });



  // CREATE TOOLTIPS
  Tipped.create('.bpr-has-tooltip', { maxWidth: 200, radius: false, behavior: 'hide'});
  Tipped.create('.bpr-has-tooltip-phone', { maxWidth: 200, radius: false, behavior: 'hide'});

});  