$(document).ready(function() {
  // INITIAL SETUP
  epsLazyLoadImages();
  epsManageScroll();
  epsShowUsefulScrollButtons();

  $(window).on('resize', function(){
    epsShowUsefulScrollButtons();
  });

  // SHOW SHOWCASE BOX ON MOBILE
  $('body').on('click', '#showcase-button', function(e) {
    e.preventDefault();
    $(this).toggleClass('active');
    $('#showcase-box').slideToggle(200);
  });


  // CLEAN ALL RECENTLY VIEWED
  $('body').on('click', '.clear-recently-viewed', function(e) {
    e.preventDefault();
    $(this).closest('.recent-ads').slideUp(200, function() {
      $(this).remove();
    });
    
    
    $.ajax({
      type: 'GET',
      url: baseAjaxUrl + '&ajaxCleanRecentlyViewedAll=1',
      success: function(data) {
        //console.log(data);
      }
    });
  });
  
  
  // CLEAN LOCATION IF CHANGED IN LOCATION BOX
  $('body').on('keyup change', 'form[name="item"] .picker.location input[name="sLocation"], form.profile .picker.location input[name="sLocation"]', function() {
    var form = $(this).closest('form');
    form.find('input[name="countryId"], input[name="regionId"], input[name="cityId"]').val('');
  });
  
  
  // CHECK IF HAS SHOWCASE
  if($('#showcase-box').length) {
    $('body').addClass('demo');
  }
  

  // SEARCH FREE PRICE CHECKBOX
  $('body').on('change', '.filter-menu input[name="bPriceCheckWithSeller"], .filter-menu input[name="bPriceFree"]', function(e) {
    e.preventDefault();
    var form = $(this).closest('form');
    
    if($(this).is(':checked')) {
      //form.find('input[name="sPriceMin"], input[name="sPriceMax"]').val('').attr('disabled', true);
      form.find('input[name="sPriceMin"], input[name="sPriceMax"]').val('');
      form.find('input[name="bPriceFree"], input[name="bPriceCheckWithSeller"]').not(this).prop('checked', false);
    } else {
      //form.find('input[name="sPriceMin"], input[name="sPriceMax"]').val('').attr('disabled', false);
      form.find('input[name="sPriceMin"], input[name="sPriceMax"]').val('');
    }
    
    $(form).find('input.ajaxRun').change();
  });

  
  $('body').on('click keyup change', '.filter-menu input[name="sPriceMin"], .filter-menu input[name="sPriceMax"]', function(e) {
    var form = $(this).closest('form');
    
    if($(this).val() != '') {
      form.find('input[name="bPriceFree"], input[name="bPriceCheckWithSeller"]').prop('checked', false);
    }
  });


  // SHOW-HIDE SCROLL TO TOP
  $(window).on('scroll', function(){
    if($(document).scrollTop() > 720) {
      $('#scroll-to-top').fadeIn(200);
    } else {
      $('#scroll-to-top').fadeOut(200);
    }  
  });

  // ITEM RATING
  $('body').on('click', '.is-rating-item', function(e) {
    e.preventDefault();
    $('input[name="rating"]').val($(this).attr('data-value'));
    $('.comment-rating-selected').text('(' + $(this).attr('data-value') + ' of 5)');
    $(this).parent().find('i.is-rating-item').addClass('fill');
    $(this).nextAll('i.is-rating-item').removeClass('fill');
  })
  
  // DISABLE DARK MODE
  $('body').on('click', 'a.disable-dark-mode', function(e) {
    e.preventDefault();
    $(this).hide();
    $('a.enable-dark-mode').show(0);
    $('link[rel="stylesheet"][href*="dark.css"]').attr('disabled', true);
    
    $.ajax({
      type: 'GET',
      url: baseAjaxUrl + '&ajaxDarkMode=disable',
      success: function(data) {
        //console.log(data);
      }
    });
  });
  
  
  // ENABLE DARK MODE
  $('body').on('click', 'a.enable-dark-mode', function(e) {
    e.preventDefault();
    $(this).hide();
    $('a.disable-dark-mode').show(0);
    $('link[rel="stylesheet"][href*="dark.css"]').attr('disabled', false);
    
    $.ajax({
      type: 'GET',
      url: baseAjaxUrl + '&ajaxDarkMode=enable',
      success: function(data) {
        //console.log(data);
        
        if(!$('link[rel="stylesheet"][href*="dark.css"]').length) {
          $('head link[rel="stylesheet"]').last().after('<link rel="stylesheet" href="' + baseDir + 'oc-content/themes/epsilon/css/dark.css" type="text/css" media="screen">');

          //window.location.reload();
          //return false;
        }
      }
    });
  });
  
  
  // RECHECK DARK MODE: Wait for DOM to be ready - in case theme loads late
  window.setTimeout(() => {
    if(darkMode == '1') {
      $('link[rel="stylesheet"][href*="dark.css"]').removeAttr('disabled');
      $('a.disable-dark-mode').hide();
      $('a.enable-dark-mode').show();
    } else {
      $('link[rel="stylesheet"][href*="dark.css"]').attr('disabled', 'disabled');
      $('a.disable-dark-mode').show();
      $('a.enable-dark-mode').hide();
    }
  }, 100);
  

  // PRINT ITEM
  $('body').on('click', 'a.print', function(e){
    e.preventDefault();
    $('body').addClass('print');
    $('.phone, #item-main .description .read-more-desc').click();
    $(window).scrollTop(0);
    window.print();
    $('body').removeClass('print');
  });
  

  // SCROLL TO TOP
  $('body').on('click', '#scroll-to-top', function(e) {
    e.preventDefault();
    $('html, body').animate({scrollTop: 0}, 500);
  });
  
  
  // FANCYBOX - OPEN ITEM FORM (COMMENT / SEND FRIEND / PUBLIC CONTACT / SELLER CONTACT)
  $('body').on('click', '.open-form', function(e) {
    e.preventDefault();
    var height = 600;
    var url = $(this).attr('href');
    var formType = $(this).attr('data-type');

    if(url.indexOf('loginRequired') !== -1) {
      window.location.href = url;
      return;
    }
    
    if(formType == 'comment') {
      height = (userLogged == 1 ? 490 : 640);
      height += ($(this).hasClass('has-rating') ? 55 : 0);
    } else if(formType == 'contact') {
      height = (userLogged == 1 ? 490 : 640);
    } else if(formType == 'friend') {
      height = (userLogged == 1 ? 540 : 705);
    } else if(formType == 'contact_public') {
      height = (userLogged == 1 ? 490 : 640);
    }
    
    epsModal({
      width: 380,
      height: height,
      content: url, 
      wrapClass: 'item-extra-form',
      closeBtn: true, 
      iframe: true, 
      fullscreen: 'mobile',
      transition: 200,
      delay: 0,
      lockScroll: true
    });
  });
  
  // OPEN REPORT BOX
  $('body').on('click', '.report-button', function(e) {
    e.preventDefault();

    epsModal({
      width: 420,
      height: 490,
      content: $('.report-wrap').html(), 
      wrapClass: 'report-box',
      closeBtn: true, 
      iframe: false, 
      fullscreen: 'mobile',
      transition: 200,
      delay: 0,
      lockScroll: true
    });
  });
  
  // SHOW FULL ITEM DESCRIPTION
  $('body').on('click', 'a.read-more-desc', function(e) { 
    e.preventDefault();
    var box = $(this).closest('.description');
    
    $(this).hide(0);
    box.find('.text.visible').hide(0);
    box.find('.text.hidden').show(0);
  });
  
  
  // LIGHTBOX GALLERY
  if(typeof $.fn.lightGallery !== 'undefined') {
    $('#item-image .swiper-container').lightGallery({
      mode: 'lg-slide',
      thumbnail: true,
      cssEasing : 'cubic-bezier(0.25, 0, 0.25, 1)',
      selector: 'li > a',
      getCaptionFromTitleOrAlt: true,
      download: false,
      thumbWidth: 90,
      thumbContHeight: 80,
      share: false
    }); 
  }
  
  
  // WHEN LIGHTBOX IS LOADED, MAKE SURE LAZYLOAD DOES NOT BLOCK THUMBNAILS
  var urlHash = window.location.hash;
  
  if(urlHash !== '' && urlHash.startsWith("#lg")) {
    setTimeout(function() {
      epsFixImgSources();
    }, 600);
  }
  
  // OPEN CONTACT SELLER BOX BASED ON HASH
  if(urlHash !== '' && urlHash.startsWith("#contact") && $('#item-side .master-button').length) {
    $('#item-side .master-button').click();
  }
  
  $('body').on('click', '#item-image li > a', function(){
    epsFixImgSources();
  });
  
  
  // SWIPER INITIATE
  if(typeof(Swiper) !== 'undefined') { 
    var swiper = new Swiper(".swiper-container", {
      slideClass: "swiper-slide",
      navigation: {
        nextEl: ".swiper-next",
        prevEl: ".swiper-prev",
      },
      pagination: {
        el: ".swiper-pg",
        type: "fraction",
      },
      on: {
        afterInit: function () {
          //epsLazyLoadGalleryImages();
        },
        activeIndexChange: function (swp) {
          //moveItemThumb(swp);
          epsLazyLoadImages('item-gallery');
          
          //(swp.activeIndex > 0 ? $('.swiper-button.swiper-prev').fadeIn(200) : $('.swiper-button.swiper-prev').fadeOut(200));
          //(swp.activeIndex < swp.slides.length - 1 ? $('.swiper-button.swiper-next').fadeIn(200) : $('.swiper-button.swiper-next').fadeOut(200));

          //(swp.activeIndex > 0 ? $('.swiper-button.swiper-prev').removeClass('disabled').fadeIn(100) : $('.swiper-button.swiper-prev').addClass('disabled'));
          //(swp.activeIndex < swp.slides.length - 1 ? $('.swiper-button.swiper-next').removeClass('disabled').fadeIn(100) : $('.swiper-button.swiper-next').addClass('disabled'));

          $('.swiper-thumbs li').removeClass('active');
          $('.swiper-thumbs li[data-id="' + swp.activeIndex + '"]').addClass('active');
        }
      }
    });
  }
  

  // SWIPER THUMBS
  $('body').on('click', '.swiper-thumbs li', function(e) {
    e.preventDefault();
    
    $('.swiper-thumbs li').removeClass('active');
    $(this).addClass('active');
    var elemId = $(this).attr('data-id');

    if(typeof(swiper) !== 'undefined') {
      epsfixImgSourcesThumb();
      swiper.slideToLoop(elemId);
    }
  });
  
  // NICE SCROLL - ACTION BUTTONS
  $('body').on('click', '.nice-scroll-next, .nice-scroll-prev', function(e) {
    e.preventDefault();
    
    if($(this).hasClass('scrolling')) {
      return false;
    }
    
    var scrollCards = 4;
    var btn = $(this);
    var elem = $(this).siblings('.nice-scroll');
    var pos = elem.scrollLeft();
    var len = elem.find(' > *').width() + parseFloat((elem.find(' > *').css('margin-left')).replace('px', '')) +  + parseFloat((elem.find(' > *').css('margin-right')).replace('px', ''));

    $(this).addClass('scrolling');
    
    ($(window).width() - 10 < scrollCards*len ? scrollCards = 3 : '');
    ($(window).width() - 10 < scrollCards*len ? scrollCards = 2 : '');
    ($(window).width() - 10 < scrollCards*len ? scrollCards = 1 : '');
  
    len = len*scrollCards;
    
    if($(this).hasClass('nice-scroll-prev')) {
      len = -len;
    }
    
    elem.stop(false,false).animate({scrollLeft: pos + len}, 100 + scrollCards*80, function() {
      btn.removeClass('scrolling');
    });
  });
  
  
  if(ajaxSearch == '1') {
    // AJAX SEARCH - initialize change events
    $('body#search').on('change', '.sort-type select, form.search-side-form input, form.search-side-form select', function(event) {
      epsAjaxSearch($(this), event);
    });
    
    // AJAX SEARCH - initialize keyp events
    $('body#search').on('keyup', 'form.search-side-form input[name="sPattern"]', function(event) {
      epsAjaxSearch($(this), event);
    });

    // AJAX SEARCH - initialize click events
    $('body#search').on('click', '.breadcrumb a, #search-category-box a, #search-filters a, #filter-user-type a, #latest-search a, .paginate a', function(event) {
      epsAjaxSearch($(this), event);
      return false;
    });
  }
  
  // MASKED ELEMENT UNHIDE
  $('body').on('click', '.masked', function(e) {
    if(!$(this).hasClass('revealed')) {
      e.preventDefault();
      var text = String($(this).attr('data-part1')) + String($(this).attr('data-part2'));

      $(this).attr('href', $(this).attr('data-prefix')+ ':' + text).attr('title', '').addClass('revealed');
      $(this).find('span').text(text);
    }
  });

  
  // SHOW HEADER QUICK INFOBAR ON MOBILE
  if($('body#search').length || $('body#item').length) {
    $(window).on('scroll', function(){
      if(($(window).width() + scrollCompensate()) < 768) {
        var elem = ($('body#search').length ? $('header .container.alt.cresults') : $('header .container.alt.citem'));
        var limit = ($('body#search').length ? $('#search-quick-bar') : $('.description'));
        
        if($(this).scrollTop() > limit.offset().top) {
          elem.hasClass('hidden') ? elem.removeClass('hidden').stop(true,true).slideDown(200) : '';
        } else {
          !elem.hasClass('hidden') ? elem.addClass('hidden').stop(true,true).slideUp(200) : '';
        }
      }
    });
  }
  
  
  // DYNAMICALLY GENERATED LABELS ON MOBILE DOES NOT WORK
  $('body').on('click', '#side-menu .filter-menu .input-box-check label', function(e) {
    if(($(window).width() + scrollCompensate()) < 768) {
      e.preventDefault();
      var checkbox = $(this).siblings('input[type="checkbox"]');
      checkbox.length ? checkbox.prop('checked', !checkbox.prop('checked')).change() : '';
    }
  });
  
  
  // SEARCH ORDER UPDATE ON SELECT CHANGE
  // $('body').on('change', '#search-quick-bar .sort-type select', function(e) {
    // var form = $('form.search-side-form');
    
    // form.find('input[name="sOrder"]').val($(this).find(':selected').attr('data-type'));
    // form.find('input[name="iOrderType"]').val($(this).find(':selected').attr('data-order'));


    // if(ajaxSearch == 1) {
      // form.find('input[name="iOrderType"]').change();
    // } else {
      // form.submit();
    // }
  // });

  $('body').on('change', '#search-quick-bar .sort-type select', function(e) {
    if(ajaxSearch != 1) {
      e.preventDefault();
      window.location.href = $(this).find(':selected').attr('data-link');
      return false;
    }
  });
  

  // OPEN SEARCH FILTERS ON MOBILE
  $('body').on('click', '#open-search-filters, .action.open-filters', function(e) {
    e.preventDefault();
    var menu = $('#side-menu');
    $('#menu-cover').fadeIn(200);
    $('#side-menu .box.filter').show(0);
    $('#side-menu .box.filter .section').html($('.filter-menu').html());
    
    if(!isRtl) {
      menu.css({'margin-left': '-50px', 'opacity': 0}).show(0).animate({'margin-left': 0, 'opacity': 1}, 300);
    } else {
      menu.css({'margin-right': '-50px', 'opacity': 0}).show(0).animate({'margin-right': 0, 'opacity': 1}, 300);
    }
    
    $('#side-menu').addClass('box-open');
  });
  
  // OPEN ALERT BOX
  $('body').on('click', '.open-alert-box', function(e) {
    e.preventDefault();

    epsModal({
      width: 490,
      height: 440,
      content: $('.alert-box').html(), 
      wrapClass: 'alert-box-search',
      closeBtn: true, 
      iframe: false, 
      fullscreen: 'mobile',
      transition: 200,
      delay: 0,
      lockScroll: true
    });
  });
  
  
  // LIST, GRID, DETAIL VIEW TYPE SWITCH
  $('body').on('click', '#search-quick-bar .view-type a', function(e) { 
    e.preventDefault();
    var viewType = $(this).attr('data-view');

    if(!$(this).hasClass('active')) {

      $(this).closest('.view-type').find('a').removeClass('active');
      $(this).addClass('active');
      $('#search-items > .products').removeClass('list grid detail');
      $('#search-items > .products').addClass(viewType);
      
      $('input[name="sShowAs"]').val(viewType);

      // UPDATE CURRENT LINK
      var href = $(this).attr('href');

      if(href != '') {
        var newNaviUrl = href;
      } else {
        //var newUrl = baseDir + 'index.php?' + $('form.search-side-form :input[value!=""], form.search-side-form select, form.search-side-form textarea').serialize();
        var newNaviUrl = baseDir + "index.php?" + $('form.search-side-form').find(":input:not(:disabled)").filter(function () { return $.trim(this.value).length > 0}).serialize();
      }

      window.history.pushState(null, null, newNaviUrl);


      // UPDATE PAGINATION AND OTHER LINKS
      $('.paginate a, .user-type a, .sort-it a, #search-filters a, select.orderSelect option').each(function() {
        var type = (typeof $(this).attr('data-link') !== 'undefined' ? 'data-link' : 'href');
        var url = $(this).attr(type);

        if(!url.indexOf("index.php") >= 0 && url.match(/\/$/)) {
          url += (url.substr(-1) !== '/' ? '/' : '');
        }

        if(url.indexOf("sShowAs") >= 0) {
          url += (url.substr(-1) !== '/' ? '/' : '');
          var newUrl = url.replace(/(sShowAs,).*?(\/)/,'$1' + viewType + '$2').replace(/(sShowAs,).*?(\/)/,'$1' + viewType + '$2');

        } else {
          if(url.indexOf("index.php") >= 0) {
            var newUrl = url + '&sShowAs=' + viewType;
          } else {
            var newUrl = url.replace(/\/+$/, '') + '?sShowAs=' + viewType;
          }
        }

        newUrl = (newUrl.substr(-1) == '/' ? newUrl.slice(0, -1) : newUrl);
        $(this).attr(type, newUrl);
      });
    }
  });
  
  
  // USER MENU HIGHLIGHT ACTIVE
  var url = window.location.toString();

  $('#user-menu a').each(function(){
    if(!$('#user-menu a.active').length) {
      var myHref = $(this).attr('href');

      if(url == myHref) {
        if(myHref.indexOf(url) >= 0)
        $(this).addClass('active');
        return;
      }
    }
  });
  
  // MOVE TO CHANGE EMAIL
  // SHOW TECHNICAL DETAILS ON ALERTS PAGE
  $('body').on('click', '.profile-box label a.change-email', function(e) {
    e.preventDefault();
    $('html, body').animate({
      scrollTop: $('.profile-box.change-mail').offset().top - 72
    }, 1000);
  });
  

  // SHOW TECHNICAL DETAILS ON ALERTS PAGE
  $('body').on('click', '.show-technical-details', function(e) {
    e.preventDefault();
    $(this).closest('.alert').find('.details').slideToggle(300); 
  });

  
  // DOUBLE ARROW PAGINATION FIX
  $('.paginate').each(function() {
    $(this).find('.searchPaginationNext').html('<i class="fas fa-angle-right"></i>');
    $(this).find('.searchPaginationPrev').html('<i class="fas fa-angle-left"></i>');
    //$(this).find('.searchPaginationFirst').html('<i class="fas fa-angle-double-left"></i>');
    //$(this).find('.searchPaginationLast').html('<i class="fas fa-angle-double-right"></i>');
    $(this).find('.searchPaginationFirst').html('<i class="fas fa-step-backward"></i>');
    $(this).find('.searchPaginationLast').html('<i class="fas fa-step-forward"></i>');
  });


  // CLEAN INPUT WHEN CLEAN BUTTON CLICKED
  $('body').on('click', '.picker .clean', function(e) {
    e.preventDefault();
    $(this).closest('.input-box').find('input').val('').focus().change();
    console.log('aa');
    //$(this).closest('form').find('.results').hide(0);
    $(this).hide(0);
  });
  
  
  // CLEAN INPUT WHEN CLEAN BUTTON CLICKED
  $('body').on('click', '.input-box .clean', function(e) {
    e.preventDefault();
    $(this).siblings('input').val('').focus().change();
    $(this).hide(0);
  });
  
  
  // ADD CLEAN BUTTON
  $('body').on('click keyup focus', '.input-box input', function(e) {
    //if(ajaxSearch == 0 && $(this).closest('.input-box').find('.clean').length) {
    if($(this).closest('.input-box').find('.clean').length) {
      if($(this).val() != '') {
        $(this).closest('.input-box').find('.clean').fadeIn(100);
      } else {
        $(this).closest('.input-box').find('.clean').fadeOut(100);
      }
    }
  });


  // SHOW BANNERS
  $('body').on('click', 'a.show-banners', function(e) {
    e.preventDefault();
    $('.banner-theme#banner-theme.is-demo, .home-container.banner-box.is-demo').stop(true, true).slideToggle(300);
    
    var newText = $(this).attr('data-alt-text');
    var oldText = $(this).text();
    
    $(this).attr('data-alt-text', oldText).text(newText);    
  });
  
  
  // ON FOUCS OUT INPUT BOX, HIDE CLEAN BUTTON WITH DELAY
  $('body').on('focusout', '.input-box input', function(e) {
    if($(this).closest('.input-box').find('.clean').length) {
      $(this).closest('.input-box').find('.clean').delay(100).fadeOut(100);
    }
  });
  
  
  // LATEST SEARCH TO INPUT BOX
  $('.latest-search a').click(function(e) {
    e.preventDefault();
    $('body#home .home-search input[name="sPattern"]').val($(this).attr('data-text'));    
  });
  
  
  // ON HOME PAGE LOAD, FOCUS SEARCH INPUT
  // if($('body#home .home-search input[name="sPattern"]').length) {
    // $('body#home .home-search input[name="sPattern"]').focus();
  // }
  

  // OPEN LOCATION SELETOR FROM HEADER MENU
  $('header .links .btn.location').click(function(e) {
    e.preventDefault();

    epsModal({
      width: 420,
      height: 640,
      content: '<div id="def-location" class="def-loc-box">' + $('#side-menu .box.location > .section').html() + '</div>', 
      wrapClass: 'location-select',
      closeBtn: true, 
      iframe: false, 
      fullscreen: 'mobile',
      transition: 200,
      delay: 0,
      lockScroll: true
    });
  });
  
  
  // ITEM PUBLISH UPDATE LOCATION
  $('body').on('click', '.location-link .link-update.location, .change-location, .change-search-location', function(e) {
    if(!$(e.target).hasClass('input-clean')) {
      e.preventDefault();

      if (($(window).width() + scrollCompensate()) >= 768) {
        $('header .links .btn.location').click();
      } else {
        $('#navi-bar a.location').click();
      }
    }
  });
  

  // OPEN LOCATION BOX FROM BOTTOM NAV BAR
  $('#navi-bar a.location').on('click', function(e) {
    e.preventDefault();
    var menu = $('#side-menu');
    $('#menu-cover').fadeIn(200);
    $('#side-menu .box').hide(0);
    $('#side-menu .box.location').show(0);
    
    if(!isRtl) {
      menu.css({'margin-left': '-50px', 'opacity': 0}).show(0).animate({'margin-left': 0, 'opacity': 1}, 300);
    } else {
      menu.css({'margin-right': '-50px', 'opacity': 0}).show(0).animate({'margin-right': 0, 'opacity': 1}, 300);
    }
    
    $('#side-menu').addClass('box-open');
  });


  // CLEAN LOCATION SEARCH FORM
  $('body').on('click', '.picker.pattern .input-clean', function(e) {
    e.preventDefault();
    $(this).closest('.row').slideUp(200);
    $(this).closest('form').find('input.loc-inp').remove();
  });
  
  
  // PATTERN PICKER LOADER KEYUP
  $('body').on('keyup click change', '.picker.pattern input', function(event) {
    var timeout = '';
    epsLoadPatternSimple($(this), event);
  });


  // HIDE PATTERN RESULTS ON OUTSIDE CLICK
  $(document).mouseup(function (e){
    var container = $('.picker.pattern');

    if(!container.is(e.target) && container.has(e.target).length === 0) {
      container.closest('form').find('.results').hide(0);
      //container.find('.results .loaded').html('').hide(0);
    }
  });

  // LOCATION LOADER KEYUP
  $('body').on('keyup click change', '.picker.location input', function(event) {
    var timeout = '';

    if($(this).closest('.picker').hasClass('only-search')) {
      epsLoadLocationsSimple($(this), event);
    } else {
      epsLoadLocationsSimple($(this), event, 'COOKIE');
    }
  });
  

  // LOCATION LOADER KEYUP
  $('body').on('keyup click change', '.picker.category input', function(event) {
    var timeout = '';

    if(!$(this).closest('.picker').hasClass('create-link')) {
      epsLoadCategoriesSimple($(this), event);
    } else {
      epsLoadCategoriesSimple($(this), event, 'LINK');
    }
  });


  // SELECT OPTION FROM LOCATION BOX
  $('body').on('click', '.picker.location div.option', function(event) {
    var form = $(this).closest('form');
    
    if($(this).closest('.picker').hasClass('is-publish') || !$('body#search').length) {
      form.find('input[name="sCountry"], input[name="countryId"]').val($(this).attr('data-country'));
      form.find('input[name="sRegion"], input[name="regionId"]').val($(this).attr('data-region'));
      form.find('input[name="sCity"], input[name="cityId"]').val($(this).attr('data-city'));
    } else {
      if($(this).attr('data-city') != '') {
        form.find('input[name="sCity"], input[name="cityId"]').val($(this).attr('data-city'));
        form.find('input[name="sRegion"], input[name="sCountry"]').val('');
      } else if ($(this).attr('data-region') != '') {
        form.find('input[name="sRegion"], input[name="regionId"]').val($(this).attr('data-region'));
        form.find('input[name="sCity"], input[name="sCountry"]').val('');
      } else if ($(this).attr('data-country') != '') {
        form.find('input[name="sCountry"], input[name="countryId"]').val($(this).attr('data-country'));
        form.find('input[name="sRegion"], input[name="sCity"]').val('');
      }
    }
    
    // Trigger change on publish page
    if($(this).closest('.picker').hasClass('is-publish')) {
      form.find('input[name="countryId"]').change();
    }

    $(this).closest('.picker').find('input[type="text"]').val($(this).find('span').text());
    $(this).closest('.results').hide(0);

    if($(this).closest('.search-side-form').length) {
      form.find('input[name="sCity"]').change();
    }
  });
  
  
  // SELECT OPTION FROM CATEGORY BOX
  $('body').on('click', '.picker.category div.option', function(event) {
    var form = $(this).closest('form');

    if($(this).attr('data-category') != '') {
      form.find('input[name="catId"], input[name="sCategory"]').val($(this).attr('data-category'));
    }

    $(this).closest('.picker').find('input[type="text"]').val($(this).find('span').text());
    $(this).closest('.results').hide(0);

    if($(this).closest('.search-side-form').length) {
      form.find('input[name="sCategory"]').change();
    }
    
    if($(this).closest('form[name="item"]').length) {
      form.find('input[name="catId"]').change();
    }
  });
  
  
  // HIDE LOCATION RESULTS ON OUTSIDE CLICK
  $(document).mouseup(function (e){
    var container = $('.picker.location');

    if(!container.is(e.target) && container.has(e.target).length === 0) {
      container.closest('form').find('.results').hide(0);
      //container.find('.results').html('').hide(0);
    }
  });
  
  
  // LOCATE USING GEOLOCATION
  $('body').on('click', '.navigator a.locate-me', function(e) {
    if(!$(this).hasClass('completed')) {
      e.preventDefault();
      epsGeoLocate($(this).find('span.status'));
    }
  });
  
  // OPEN SIDE MENU
  $('header .menu.btn').click(function(e) {
    e.preventDefault();
    var menu = $('#side-menu');
    $('#menu-cover').fadeIn(200);
    $('#side-menu').removeClass('box-open');
    $('#side-menu .box').hide(0);

    if(!isRtl) {
      menu.css({'margin-left': '-50px', 'opacity': 0}).show(0).animate({'margin-left': 0, 'opacity': 1}, 300);
    } else {
      menu.css({'margin-right': '-50px', 'opacity': 0}).show(0).animate({'margin-right': 0, 'opacity': 1}, 300);
    }
  });
  
  // CLOSE SIDE MENU
  $('#menu-cover').click(function(e) {
    e.preventDefault();
    var menu = $('#side-menu');
    $('#menu-cover').fadeOut(200);
    
    if(!isRtl) {
      menu.stop(true, true).animate({'margin-left': '-50px', 'opacity': 0}, 300, function() {
        menu.css({'margin-left': 0, 'opacity': 1}).hide(0);
      });
    } else {
      menu.stop(true, true).animate({'margin-right': '-50px', 'opacity': 0}, 300, function() {
        menu.css({'margin-right': 0, 'opacity': 1}).hide(0);
      }); 
    }
  });
  
  // SIDE MENU OPEN SUBSECTION
  $('#side-menu .open-box').click(function(e) {
    e.preventDefault();
    var boxId = $(this).attr('data-box');
    var box = $('#side-menu .box[data-box="' + boxId + '"]');
    
    if(!isRtl) {
      box.show(0).css({'margin-left': '-50px', 'opacity': 0}).animate({'margin-left': 0, 'opacity': 1}, 300);
    } else {
      box.show(0).css({'margin-right': '-50px', 'opacity': 0}).animate({'margin-right': 0, 'opacity': 1}, 300);
    }
    
    $('#side-menu').addClass('box-open');
  });
  
  // SIDE MENU SUBSECTION BACK BUTTON
  $('#side-menu .back').click(function(e) {
    e.preventDefault();
    if($(this).hasClass('close')) {
      $('#menu-cover').click();
      $(this).closest('.box').delay(300).hide(0);
    } else {
      var box = $(this).closest('.box');
      
      if(!isRtl) {
        box.animate({'margin-left': '-50px', 'opacity': 0}, 300, function() {
          box.css({'margin-left': 0, 'opacity': 1}).hide(0);
        });
      } else {
        box.animate({'margin-right': '-50px', 'opacity': 0}, 300, function() {
          box.css({'margin-right': 0, 'opacity': 1}).hide(0);
        });
      }
      
      $('#side-menu').removeClass('box-open');
    }
  });
  
  
  // REMOVE EMPTY SECTIONS OF MOBILE SIDE MENU
  $('#side-menu > .section').each(function() {
    if(!$(this).find('a').length) {
      $(this).hide(0); 
    }
  });
  
  // SUBMIT PUBLISH FORM VIA MIDDLE BUTTON
  $('#navi-bar a.active.post').click(function(e) {
    if($('form[name="item"]').length) {
      e.preventDefault();
      $('form[name="item"]').submit();
    }
  });
  
  
  // MOBILE BACK BUTTON
  $('header .csearch a.back').click(function(e) {
    e.preventDefault();
    $(this).closest('.container').slideUp(150);
  });
  
  // SHOW SEARCH BAR ON MOBILE
  $('header .links .search').click(function(e) {
    e.preventDefault();
    var box = $('header .csearch');
    var input = box.find('input[type="text"]');
    box.slideDown(150);
    
    var oldVal = input.val();
    input.val('').val(oldVal).focus().click();
  });


  // PUBLISH PAGE - SWITCH PRICE
  $('.item-publish .selection a').click(function(e) {
    e.preventDefault();
    var price = $(this).attr('data-price');

    $('.item-publish .selection a').removeClass('active');
    $(this).addClass('active');
    $('.item-publish .enter').addClass('disable');
    $('.item-publish .enter #price').val(price).attr('placeholder', '').attr('readonly', true);
  });

  $('.item-publish .enter .input-box').click(function(e) {
    if($(this).closest('.enter').hasClass('disable')) {
      $('.item-publish .selection a').removeClass('active');
      $(this).parent().removeClass('disable');
      $('.item-publish .enter #price').val('').attr('placeholder', '').attr('readonly', false);
    }
  });
  
  
  // SHOW HIDE PASSWORD
  $('.toggle-pass').click(function(e) {
    e.preventDefault();

    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    var input = $(this).siblings('input');
    
    if (input.attr('type') == 'password') {
      input.prop('type', 'text');
    } else {
      input.prop('type', 'password');
    }
  });
  
  
  // SHOW HIDE TIP ON PUBLISH PAGE - DESKTOP
  $('.item-publish .box section').click(function(e) {
    if (($(window).width() + scrollCompensate()) >= 768) {
      var box = $(this);
      var tip = $(this).find('.tip');

      if(tip.length && !tip.hasClass('shown') && !$(e.target).hasClass('close-tip') && !$(e.target.parentElement).hasClass('tabberactive')) {
        $('.item-publish .box section .tip').removeClass('shown').hide(0);

        if(!isRtl) {
          tip.addClass('shown').show(0).css({'margin-right': '-16px', 'opacity': 0}).animate({'margin-right': '16px', 'opacity': 1}, 300);
        } else {
          tip.addClass('shown').show(0).css({'margin-left': '-16px', 'opacity': 0}).animate({'margin-left': '16px', 'opacity': 1}, 300);
        }
      }
    }
  });
  
  $('.item-publish .box section .show-tip').click(function(e) {
    var box = $(this).closest('section');
    var tip = $(this).closest('section').find('.tip');
    
    if(tip.length && !tip.hasClass('shown')) {
      $('.item-publish .box section .tip').removeClass('shown').hide(0);
      
      if(($(window).width() + scrollCompensate()) >= 768) {
        if(!isRtl) {
          tip.addClass('shown').show(0).css({'margin-left': '-16px', 'opacity': 0}).animate({'margin-left': '16px', 'opacity': 1}, 300);
        } else {
          tip.addClass('shown').show(0).css({'margin-right': '-16px', 'opacity': 0}).animate({'margin-right': '16px', 'opacity': 1}, 300);
        }
      } else {
        tip.addClass('shown').show(0).css({'margin-top': '-12px', 'opacity': 0}).animate({'margin-top': '6px', 'opacity': 1}, 300);
      }
    }
  });
  
  $('.item-publish .box .close-tip').click(function(e) {
    e.preventDefault();
    var tip = $(this).closest('.tip');
    tip.removeClass('shown').stop(true, true).fadeOut(200);
  });
  
  $(document).mouseup(function (e){
    if (($(window).width() + scrollCompensate()) < 768) {
      var container = $('.item-publish .box .tip');

      if(!container.is(e.target) && container.has(e.target).length === 0 && container.hasClass('shown')) {
        container.removeClass('shown').stop(true, true).fadeOut(200);
      }
    }
  });

});


// CUSTOM MODAL BOX
function epsModal(opt) {
  width = (typeof opt.width !== 'undefined' ? opt.width : 480);
  height = (typeof opt.height !== 'undefined' ? opt.height : 480);
  content = (typeof opt.content !== 'undefined' ? opt.content : '');
  wrapClass = (typeof opt.wrapClass !== 'undefined' ? ' ' + opt.wrapClass : '');
  closeBtn = (typeof opt.closeBtn !== 'undefined' ? opt.closeBtn : true);
  iframe = (typeof opt.iframe !== 'undefined' ? opt.iframe : true); 
  fullscreen = (typeof opt.fullscreen !== 'undefined' ? opt.fullscreen : false); 
  transition = (typeof opt.transition !== 'undefined' ? opt.transition : 200); 
  delay = (typeof opt.delay !== 'undefined' ? opt.delay : 0);
  lockScroll = (typeof opt.lockScroll !== 'undefined' ? opt.lockScroll : true); 

  var id = Math.floor(Math.random() * 100) + 10;
  width = epsAdjustModalSize(width, 'width') + 'px';
  height = epsAdjustModalSize(height, 'height') + 'px';

  var fullscreenClass = '';
  if(fullscreen === 'mobile') {
    if (($(window).width() + scrollCompensate()) < 768) {
      width = 'auto'; height = 'auto'; fullscreenClass = ' modal-fullscreen';
    }
  } else if (fullscreen === true) {
    width = 'auto'; height = 'auto'; fullscreenClass = ' modal-fullscreen';
  }

  var html = '';
  html += '<div class="modal-cover" data-modal-id="' + id + '" onclick="epsModalClose(\'' + id + '\');"></div>';
  html += '<div id="epsModal" class="modal-box' + wrapClass + fullscreenClass + '" style="width:' + width + ';height:' + height + ';" data-modal-id="' + id + '">';

  if(closeBtn) {
    html += '<div class="modal-close-alt" onclick="epsModalClose(\'' + id + '\');"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="32px" height="32px"><path fill="currentColor" d="M193.94 256L296.5 153.44l21.15-21.15c3.12-3.12 3.12-8.19 0-11.31l-22.63-22.63c-3.12-3.12-8.19-3.12-11.31 0L160 222.06 36.29 98.34c-3.12-3.12-8.19-3.12-11.31 0L2.34 120.97c-3.12 3.12-3.12 8.19 0 11.31L126.06 256 2.34 379.71c-3.12 3.12-3.12 8.19 0 11.31l22.63 22.63c3.12 3.12 8.19 3.12 11.31 0L160 289.94 262.56 392.5l21.15 21.15c3.12 3.12 8.19 3.12 11.31 0l22.63-22.63c3.12-3.12 3.12-8.19 0-11.31L193.94 256z" class=""></path></svg></div>';
  }
  
  html += '<div class="modal-inside">';
  
  if(closeBtn) {
    html += '<div class="modal-close" onclick="epsModalClose(\'' + id + '\');"><i class="fas fa-times"></i></div>';
  }
    
  html += '<div class="modal-body ' + (iframe === true ? 'modal-is-iframe' : 'modal-is-inline') + '">';
  
  if(iframe === true) {
    html += '<div class="modal-content"><iframe class="modal-iframe" data-modal-id="' + id + '" src="' + content + '"/></div>';
  } else {
    html += '<div class="modal-content">' + content + '</div>';
  }
  
  html += '</div>';
  html += '</div>';
  html += '</div>';
  
  if(lockScroll) {
    $('body').css('overflow', 'hidden');
  }
  
  $('body').append(html);
  $('div[data-modal-id="' + id + '"].modal-cover').fadeIn(transition);
  $('div[data-modal-id="' + id + '"].modal-box').delay(delay).fadeIn(transition);
}


// Close modal by clicking on close button
function epsModalClose(id = '', elem = null) {
  if(id == '') {
    id = $(elem).closest('.modal-box').attr('data-modal-id');
  }
  
  $('body').css('overflow', 'initial');
  $('div[data-modal-id="' + id + '"]').fadeOut(200, function(e) {
    $(this).remove(); 
  });
  
  return false;
}


// Close modal by some action inside iframe
function epsModalCloseParent() {
  var boxId = $(window.frameElement, window.parent.document).attr('data-modal-id');
  window.parent.epsModalClose(boxId);
}


// Calculate maximum width/height of modal in case original width/height is larger than window width/height
function epsAdjustModalSize(size, type = 'width') {
  var size = parseInt(size);
  var windowSize = (type == 'width' ? $(window).width() : $(window).height());
  
  if(size <= 0) {
    size = (type == 'width' ? 640 : 480);  
  }
  
  if(size*0.9 > windowSize) {
    size = windowSize*0.9;
  }
  
  return Math.floor(size);
}


window.addEventListener('DOMContentLoaded', () => {
  var isPrinting = window.matchMedia('print');
  isPrinting.addListener((media) => {
    $('img.lazy').each(function() {
      $(this).attr('src', $(this).prop('data-src'));
    });
  })
});


// CALCULATE SCROLL WIDTH
function scrollCompensate() {
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


// HTML5 GEOLOCATION
function epsGeoLocate(elem) {
  function success(position) {
    const latitude  = position.coords.latitude;
    const longitude = position.coords.longitude;
    
    $.ajax({
      type: 'GET',
      dataType: 'json',
      url: baseAjaxUrl + "&ajaxFindCity=1&latitude=" + latitude + "&longitude=" + longitude,
      success: function(data) {
        //console.log(data);
        //console.log(baseAjaxUrl + "&ajaxFindCity=1&latitude=" + latitude + "&longitude=" + longitude);
        
        if(data['success'] !== undefined) {
          if(data['success'] == true) {
            data['url'] = baseAjaxUrl + "&ajaxFindCity=1&latitude=" + latitude + "&longitude=" + longitude;
            elem.find('span').hide(0);
            elem.find('span.success').text(data['s_location']).show(0);

            if(!elem.closest('.navigator-fill-selects').length) {
              elem.find('span.refresh').show(0);
              elem.closest('a').attr('href', (window.location.href).replace('#', '')).text(elem.closest('a').attr('data-alt-text')).addClass('completed');
              elem.closest('a').find('strong').text(elem.closest('a').find('strong').attr('data-alt-text'));
            } else {
              epsGeoToSelects(elem, data);
            }
            
            return;
          }
        }
        
        elem.find('span').hide(0);
        elem.find('span.failed-unfound').show(0);
        return;
      },
      error: function(data) {
        console.log(data);
        elem.find('span').hide(0);
        elem.find('span.failed-unfound').show(0);
        return;
      }
    });
  }

  function error() {
    elem.find('span').hide(0);
    elem.find('span.failed').show(0);
  }

  if(!navigator.geolocation) {
    elem.find('span').hide(0);
    elem.find('span.not-supported').show(0);
  } else {
    elem.find('span').hide(0);
    elem.find('span.loading').show(0);

    navigator.geolocation.getCurrentPosition(success, error);
  }

}


function epsGeoToSelects(elem, data) {
  var box = elem.closest('.navigator-fill-selects');
  var country = box.find('select[name="countryId"]');  
  var region = box.find('select[name="regionId"]');  
  var city = box.find('select[name="cityId"]');  
  
  if(country.length && data['fk_c_country_code'] !== undefined && data['fk_c_country_code'] != '') {
    country.val(data['fk_c_country_code']);
  }
  
  if(region.length && data['fk_i_region_id'] !== undefined && data['fk_i_region_id'] != '') {
    region.find('option').remove().end().append('<option value="' + data['fk_i_region_id'] + '">' + data['s_region'] + '</option>').val(data['fk_i_region_id']);
    region.attr('disabled', false);
  }

  if(city.length && data['fk_i_city_id'] !== undefined && data['fk_i_city_id'] != '') {
    city.find('option').remove().end().append('<option value="' + data['fk_i_city_id'] + '">' + data['s_city'] + '</option>').val(data['fk_i_city_id']);
    city.attr('disabled', false);
  }
}

var epsLoadLocationsSimpleTimeout = '';
var epsLoadLocationsSimpleValue = '';

// SIMPLE LOCATION LOADER
function epsLoadLocationsSimple(elem, event, type = '') {
  var min = 1;
  var box = elem.closest('.picker').find('.results');
  var term = $(elem).val().trim();

  term = (term.indexOf(',') > 1 ? term.substr(0, term.indexOf(',')) : term);
  term = (term.indexOf('-') > 1 ? term.substr(0, term.indexOf('-')) : term);
  term = encodeURIComponent(term).trim();


  if(epsLoadLocationsSimpleValue == term) {
    if(box.find('a, div').length) {
      box.show(0);
    }
    
    return false;
  } else {
    epsLoadLocationsSimpleValue = term; 
  }
  
  (typeof(epsLoadLocationsSimpleTimeout) !== undefined) ? clearTimeout(epsLoadLocationsSimpleTimeout) : ''; 
  (term.length > 0) ? elem.siblings('.clean').show(0) : elem.siblings('.clean').hide(0);
  (term != '' && term.length >= min) ? elem.closest('.picker').addClass('loading') : '';
  
  epsLoadLocationsSimpleTimeout = setTimeout(function() {
    if(term != '' && term.length >= min) {
      $.ajax({
        type: 'GET',
        url: baseAjaxUrl + '&ajaxLoc=1&dataType=' + type + '&term=' + term,
        success: function(data) {
          //console.log(data);
          
          elem.closest('.picker').removeClass('loading');
          box.html(data).show(0);
          box.find('fieldset').remove();   // DB debug
          
          if(box.find('a, div').length <= 0) {
            box.html('').hide(0);
          }
        },
        error: function(data) {
          elem.closest('.picker').removeClass('loading');
          box.html('').hide(0);
        }
      });
    } else {
      elem.closest('.picker').removeClass('loading');
      box.html('').hide(0); 
    }
  }, 300);
}


var epsLoadPatternSimpleTimeout = '';
var epsLoadPatternSimpleValue = '';

// SIMPLE LOCATION LOADER
function epsLoadPatternSimple(elem, event) {
  var min = 1;
  var form = elem.closest('form');
  var box = form.find('.results');
  var boxLoaded = form.find('.results .loaded');
  var boxDefault = form.find('.results .default');
  var term = encodeURIComponent($(elem).val().trim());

  if(epsLoadPatternSimpleValue == term) {
    box.show(0);
    boxLoaded.show(0);
    boxDefault.hide(0);

    if(boxLoaded.find('a, div').length <= 0) {
      box.hide(0);
    }

    return false;
  } else {
    epsLoadPatternSimpleValue = term;
  }

  (typeof(epsLoadPatternSimpleTimeout) !== undefined)
    ? clearTimeout(epsLoadPatternSimpleTimeout)
    : '';

  (term.length > 0)
    ? elem.siblings('.clean').show(0)
    : elem.siblings('.clean').hide(0);

  elem.closest('.picker').addClass('loading');

  epsLoadPatternSimpleTimeout = setTimeout(function() {
    // AJAX also runs when the field is empty.
    // ajax.php will return our fixed popular-city list.
    if(term.length === 0 || term.length >= min) {
      $.ajax({
        type: 'GET',
        url: baseAjaxUrl + '&ajaxPatternSearch=1&term=' + term,

        success: function(data) {
          elem.closest('.picker').removeClass('loading');

          box.show(0);
          boxLoaded.html(data).show(0);
          boxLoaded.find('fieldset').remove();
          boxDefault.hide(0);

          if(boxLoaded.find('a, div').length <= 0) {
            box.hide(0);
            boxLoaded.html('').hide(0);
          }
        },

        error: function(data) {
          elem.closest('.picker').removeClass('loading');
          box.hide(0);
          boxLoaded.html('').hide(0);
          boxDefault.hide(0);
        }
      });
    } else {
      elem.closest('.picker').removeClass('loading');
      box.hide(0);
      boxDefault.hide(0);
      boxLoaded.html('').hide(0);
    }
  }, 300);
}



var epsAjaxSearchTimeout = '';

// AJAX SEARCH - core function
function epsAjaxSearch(elem, event) {
  (typeof(epsAjaxSearchTimeout) !== undefined) ? clearTimeout(epsAjaxSearchTimeout) : '';  

  var delay = (event.type == 'keyup' ? 200 : 50);
  var scrollToTop = false;
  var ajaxStop = false;
  var ajaxSearchUrl = '';
  var sidebarReload = true;
  
  if(elem.closest('form.search-side-form').length) {
    var sidebar = elem.closest('form.search-side-form').last();
  } else {
    var sidebar = $('form.search-side-form').last();
  }


  // Breadcrumb home button
  if(elem.closest('li.first-child').length && elem.attr('href') == baseDir) {
    window.location.href = elem.attr('href');
    return false;
  }
  
  if($(event.target).attr('name') == 'sLocation') {
    ajaxStop = true;
    return false;
  }

  if(elem.attr('name') == 'sCity' || elem.attr('name') == 'sRegion' || elem.attr('name') == 'sCountry') {
    // sidebarReload = true;
    
  } else if ((elem.attr('name')?.startsWith('atr_') || elem.attr('data-attr-id') != '') && ($(window).width() + scrollCompensate()) < 768) {  // Attributes plugin
    // sidebarReload = true;
    
  } else if (elem.closest('.sidebar-hooks').length || elem.closest('.input-box-check').length || (elem.closest('.search-side-form').length && elem.attr('name') != 'sCategory') || event.type == 'keyup') {
    sidebarReload = false;
  }
  
  // Make sure no sidebar reload for Car Attributes PRO inputs
  if(elem.closest('.cap-input-box').length) {
    sidebarReload = false;
  }

  if(elem.closest('.paginate').length || elem.closest('#latest-search').length) {
    scrollToTop = true;
  }
  
  if (event.type == 'click' && !elem.is('input:radio')) {
    if(typeof elem.attr('href') !== typeof undefined && elem.attr('href') !== false && elem.attr('href') != '') {
      ajaxSearchUrl = elem.attr('href');
    }
  } else if (event.type == 'change' || event.type == 'keyup' || elem.is('input:radio')) {
    if (elem.hasClass('orderSelect')) {
      ajaxSearchUrl = elem.find(':selected').attr('data-link');
    } else {
      ajaxSearchUrl = baseDir + "index.php?" + sidebar.find(":input").filter(function () { return $.trim(this.value).length > 0}).serialize();
    }
  }

  epsAjaxSearchTimeout = setTimeout(function() {
    if(ajaxSearch == 1 && $('input.ajaxRun').val() != 1 && (ajaxSearchUrl != '#' && ajaxSearchUrl != '') && ajaxStop !== true) {
      if(ajaxSearchUrl == $(location).attr('href')) {
        return false;
      }

      sidebar.find('.init-search').addClass('loading').addClass('disabled').attr('disabled', true);
      sidebar.find('input.ajaxRun').val(1);
      $('#search-main').addClass('loading');
      $('#search-main .ajax-load-failed').hide(0);

      $.ajax({
        url: ajaxSearchUrl,
        type: "GET",
        timeout: 10000,
        success: function(response){
          var data = $(response).contents().find('#search-main').html();
          var bread = $(response).contents().find('ul.breadcrumb').html();
          //var filter = $(response).contents().find('.filter-menu').html();
          
          var sideForm = $(response).contents().find('.filter-menu > .wrap > form').html();
          var sideCat = $(response).contents().find('.filter-menu > #search-category-box').html();

          sidebar.find('.init-search').removeClass('loading').removeClass('disabled').attr('disabled', false);
          sidebar.find('input.ajaxRun').val('');

          $('#search-main').removeClass('loading').html(data);
          
          if(sidebarReload) {
            $('.filter-menu > .wrap > form').html(sideForm);
            $('.filter-menu > #search-category-box').html(sideCat);
          }
          
          // sidebarReload ? $('.filter-menu').html(filter) : '';
          // sidebarReload ? $('.search-side-form').last().remove() : '';

          $('ul.breadcrumb').html(bread);
          
          epsLazyLoadImages('search-items');
          epsLazyLoadImages('search-premium-items');
          epsManageScroll();
          epsShowUsefulScrollButtons();

          // Update URL
          var ajaxSearchUrlCleaned = baseDir + "index.php?" + $('.filter-menu form.search-side-form').find(":input").filter(function () { return $.trim(this.value).length > 0}).serialize();
          window.history.pushState(null, null, ajaxSearchUrl);
          
          //(scrollToTop ? $(window).scrollTop(0) : '');
          (scrollToTop ? $(window).scrollTop($('#search-quick-bar .view-type').offset().top - parseInt($('header').height()) - 12) : '');
        },

        error: function(response){
          // There was some problem
          console.log(response);
          
          sidebar.find('.init-search').removeClass('loading').removeClass('disabled').attr('disabled', false);
          sidebar.find('input.ajaxRun').val('');

          $('#search-main').removeClass('loading');
          $('#search-main .ajax-load-failed').show(0);
        }
      });

      if(!elem.is('input:radio')) {
        return false;
      }
    }
  }, delay);
}



// Lazyload images
function epsLazyLoadImages(type = '') {
  if(epsLazy != "1" ) {
    return false;
  }
  
  $('#item-image li a img').height(parseFloat($('#item-main').width())/parseFloat(imgPreviewRatio));

  
  // standard initialization
  if(type == '' || type == 'basic') {
    $('img.lazy').Lazy({
      appendScroll: window,
      scrollDirection: 'both',
      effect: 'fadeIn',
      effectTime: 100,
      afterLoad: function(element) {
        element.addClass('loaded');
        setTimeout(function() {
          element.css('transition', '0.2s');
        }, 100);
      }
    });
  }
  
  // search items
  if(type == 'search-items') {
    $('#search-items img.lazy').Lazy({
      appendScroll: window,
      scrollDirection: 'both',
      effect: 'fadeIn',
      effectTime: 100,
      afterLoad: function(element) {
        element.addClass('loaded');
        setTimeout(function() {
          element.css('transition', '0.2s');
        }, 100);
      }
    });
  }
  
  if(type == '' || type == 'search-premium-items') {
    $('#search-premium-items img.lazy').Lazy({
      appendScroll: window,
      scrollDirection: 'both',
      effect: 'fadeIn',
      effectTime: 100,
      afterLoad: function(element) {
        element.addClass('loaded');
        setTimeout(function() {
          element.css('transition', '0.2s');
        }, 100);
      }
    });
    
    $('#search-premium-items img.lazy').Lazy({
      appendScroll: '.nice-scroll, .mobile-scroll',
      scrollDirection: 'both',
      effect: 'fadeIn',
      effectTime: 100,
      afterLoad: function(element) {
        element.addClass('loaded');
        setTimeout(function() {
          element.css('transition', '0.2s');
        }, 100);
      }
    });
    
    if(type == 'search-premium-items') {
      $('#search-premium-items .nice-scroll').scrollLeft(1).delay(10).scrollLeft(0);
    }
  }
  
  
  // initialization in nice-scroll slider
  if(type == '') {
    $('.nice-scroll img.lazy, .mobile-scroll img.lazy').Lazy({
      appendScroll: '.nice-scroll, .mobile-scroll',
      scrollDirection: 'both',
      effect: 'fadeIn',
      effectTime: 100,
      afterLoad: function(element) {
        element.addClass('loaded');
        setTimeout(function() {
          element.css('transition', '0.2s');
        }, 100);
      }
    });
  }
  
  // item gallery swiper move
  if(type == 'item-gallery') {
    $('#item-image li a img.lazy').Lazy({
      appendScroll: '.swiper-wrapper, .swiper-container',
      scrollDirection: 'both',
      effect: 'fadeIn',
      effectTime: 100,
      afterLoad: function(element) {
        element.addClass('loaded');
        setTimeout(function() {
          element.css('transition', '0.2s');
        }, 100);
      }
    });
  }
  
}


// Fix lazyload large pictures when using thumbnails
function epsfixImgSourcesThumb() {
  $('#item-image li img').each(function() {
    if(typeof $(this).attr('data-src') !== 'undefined') {
      $(this).attr('src', $(this).attr('data-src'));
    }
  });
}


// Fix lazyload thumbnails for light gallery
function epsFixImgSources() {
  $('#item-image li img').each(function() {
    if(typeof $(this).attr('data-src') !== 'undefined') {
      var imgDataSrc = $(this).attr('data-src');
    } else {
      var imgDataSrc = $(this).attr('src');
    }
    
    if(typeof imgDataSrc !== 'undefined') {
      var index = $(this).closest('li').index();
      $('.lg-thumb .lg-thumb-item').eq(index).find('img').attr('src', imgDataSrc);
    }
  });
}


function epsManageScroll() {
  // NICE SCROLL - MANAGE FADERS
  $('.nice-scroll').on('scroll', function(e) {
    var box = $(this);
    var scrollLeft = (isRtl ? -1 : 1) * box.scrollLeft();
    var padding = parseFloat((box.css('padding-left')).replace('px', '')) + parseFloat((box.css('padding-right')).replace('px', ''));
    var maxScroll = box.prop('scrollWidth') - scrollLeft - box.width() - padding;
    var prev = box.siblings('.nice-scroll-prev');
    var next = box.siblings('.nice-scroll-next');

    if (scrollLeft < 20) {
      //(isRtl ? next.fadeOut(100) : prev.fadeOut(100));
      (isRtl ? next.addClass('disabled') : prev.addClass('disabled'));
    } else {
      //(isRtl ? next.fadeIn(100) : prev.fadeIn(100));
      (isRtl ? next.removeClass('disabled').show(100) : prev.removeClass('disabled').show(100));
    }

    if (maxScroll < 20) {
      //(isRtl ? prev.fadeOut(100) : next.fadeOut(100));
      (isRtl ? prev.addClass('disabled') : next.addClass('disabled'));
    } else {
      //(isRtl ? prev.fadeIn(100) : next.fadeIn(100));
      (isRtl ? prev.removeClass('disabled').show(100) : next.removeClass('disabled').show(100));
    }
  });
}


function epsShowUsefulScrollButtons() {
  $('.nice-scroll').each(function() {
    var box = $(this);
    var boxWidth = parseInt($(this).outerWidth());
    var boxInnerWidth = parseInt($(this)[0].scrollWidth);
    var prev = box.siblings('.nice-scroll-prev');
    var next = box.siblings('.nice-scroll-next');
    
    if(boxWidth < boxInnerWidth - 2) {   // 2 is there as buffer for rounding etc
      //(isRtl ? prev.fadeIn(200) : next.fadeIn(200));
      (isRtl ? prev.removeClass('disabled').fadeIn(100) : next.removeClass('disabled').fadeIn(100));
      $(this).parent().addClass('nice-scroll-have-overflow').removeClass('nice-scroll-nothave-overflow');
    } else {
      //(isRtl ? prev.hide(0) : next.hide(0));
      (isRtl ? prev.addClass('disabled') : next.addClass('disabled'));
      $(this).parent().removeClass('nice-scroll-have-overflow').addClass('nice-scroll-nothave-overflow');
    }
  });
}


function epsHideUselessScrollButtons() {
  // HIDE FADERS WHEN NOT NEEDED
  $('.nice-scroll').each(function() {
    var box = $(this);
 
    if(box.prop('scrollWidth') - box.width() <= 0) {
      box.siblings('.nice-scroll-prev, .nice-scroll-next').hide(0);
    }
  });
}
