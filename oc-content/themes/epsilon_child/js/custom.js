/**
 * PNG Market - child theme scripts.
 *
 * Homepage category subcategories:
 *   - pointer devices on desktop get the CSS hover flyout, untouched here;
 *   - everything else opens a bottom sheet on tap, per HOME-04.
 */
(function () {
  'use strict';

  var DESKTOP_FLYOUT = '(hover: hover) and (min-width: 1081px)';

  function usesHoverFlyout() {
    return window.matchMedia && window.matchMedia(DESKTOP_FLYOUT).matches;
  }

  var sheet = null;
  var backdrop = null;
  var lastTrigger = null;

  function buildSheet() {
    if (sheet) {
      return;
    }

    backdrop = document.createElement('div');
    backdrop.className = 'pngm-sheet-backdrop';

    sheet = document.createElement('div');
    sheet.className = 'pngm-sheet';
    sheet.setAttribute('role', 'dialog');
    sheet.setAttribute('aria-modal', 'true');
    sheet.innerHTML = '<div class="pngm-sheet-grip"></div><strong class="pngm-sheet-title"></strong><div class="pngm-sheet-body"></div>';

    document.body.appendChild(backdrop);
    document.body.appendChild(sheet);

    backdrop.addEventListener('click', closeSheet);
  }

  function openSheet(item) {
    var panel = item.querySelector('.pngm-subcat');
    var link = item.querySelector('a');

    if (!panel || !link) {
      return;
    }

    buildSheet();

    var heading = link.querySelector('h3');

    sheet.querySelector('.pngm-sheet-title').textContent = heading ? heading.textContent.trim() : '';
    sheet.querySelector('.pngm-sheet-body').innerHTML = panel.innerHTML;
    sheet.setAttribute('aria-label', sheet.querySelector('.pngm-sheet-title').textContent);

    // Force a reflow so the transition runs from the off-screen state.
    void sheet.offsetHeight;

    backdrop.classList.add('is-open');
    sheet.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    var toggle = item.querySelector('.pngm-sub-toggle');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'true');
    }

    lastTrigger = item;
  }

  function closeSheet() {
    if (!sheet || !sheet.classList.contains('is-open')) {
      return;
    }

    backdrop.classList.remove('is-open');
    sheet.classList.remove('is-open');
    document.body.style.overflow = '';

    if (lastTrigger) {
      var toggle = lastTrigger.querySelector('.pngm-sub-toggle');
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
      }
      lastTrigger = null;
    }
  }

  function onCategoryClick(event) {
    var item = event.target.closest ? event.target.closest('.pngm-cat-item.has-sub') : null;

    if (!item) {
      return;
    }

    // Never swallow a tap on a subcategory link inside the in-page panel.
    if (event.target.closest('.pngm-subcat')) {
      return;
    }

    if (usesHoverFlyout()) {
      return;
    }

    event.preventDefault();
    openSheet(item);
  }

  function initCategories() {
    var grid = document.getElementById('home-cat');

    if (!grid) {
      return;
    }

    grid.addEventListener('click', onCategoryClick);

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' || event.keyCode === 27) {
        closeSheet();
      }
    });

    // A resize into desktop territory should not leave the sheet stranded.
    window.addEventListener('resize', function () {
      if (usesHoverFlyout()) {
        closeSheet();
      }
    });
  }

  /**
   * SEARCH-03 — On the homepage the hero already has a large search field.
   * Reveal the compact sticky header search once the user scrolls past it.
   */
  function initStickyHomeSearch() {
    var body = document.body;

    if (!body || body.id !== 'home') {
      return;
    }

    var hero = document.querySelector('section.home-search');
    var threshold = hero ? Math.max(120, hero.offsetTop + Math.min(hero.offsetHeight * 0.55, 220)) : 160;

    function update() {
      if (window.pageYOffset > threshold || window.scrollY > threshold) {
        body.classList.add('pngm-sticky-search');
      } else {
        body.classList.remove('pngm-sticky-search');
      }
    }

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', function () {
      threshold = hero ? Math.max(120, hero.offsetTop + Math.min(hero.offsetHeight * 0.55, 220)) : 160;
      update();
    });
  }

  /**
   * Keyword search is always nationwide (no default-location filter).
   * Strip any leftover loc-inp / “Searching only in…” chip from pattern UI.
   */
  function initPatternSearchLocation() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;

    // Remove location inputs from keyword search forms (header + home hero).
    $('form').has('input.pattern, input[name="sPattern"]').each(function () {
      var form = $(this);

      // Keep location filters only on the dedicated search-results sidebar form.
      if (form.closest('#search-items, .filter-menu, #sidebar').length) {
        return;
      }

      form.find('input.loc-inp').remove();
    });

    function hideEmptyResults(box) {
      if (!box || !box.length) {
        return;
      }

      var hasVisible = box.find('a.option:visible, .pngmarket-no-exact-msg:visible').length > 0;

      if (!hasVisible) {
        box.hide(0);
      }
    }

    if (typeof window.epsLoadPatternSimple === 'function') {
      window.epsLoadPatternSimple = function (elem) {
        var min = 1;
        var form = elem.closest('form');
        var box = form.find('.results');
        var boxLoaded = form.find('.results .loaded');
        var boxDefault = form.find('.results .default');
        var rawTerm = $.trim($(elem).val() || '');
        var term = encodeURIComponent(rawTerm);

        if (window.epsLoadPatternSimpleValue == term) {
          box.show(0);
          boxLoaded.show(0);
          boxDefault.hide(0);
          boxLoaded.find('.row.defloc').remove();
          hideEmptyResults(box);
          return false;
        }

        window.epsLoadPatternSimpleValue = term;

        if (typeof window.epsLoadPatternSimpleTimeout !== 'undefined') {
          clearTimeout(window.epsLoadPatternSimpleTimeout);
        }

        if (term.length > 0) {
          elem.siblings('.clean').show(0);
        } else {
          elem.siblings('.clean').hide(0);
        }

        elem.closest('.picker').addClass('loading');

        window.epsLoadPatternSimpleTimeout = setTimeout(function () {
          if (term.length === 0 || term.length >= min) {
            $.ajax({
              type: 'GET',
              // Always global — do not append sCity / sRegion from default location.
              url: window.baseAjaxUrl + '&ajaxPatternSearch=1&term=' + term,
              success: function (data) {
                elem.closest('.picker').removeClass('loading');
                box.show(0);
                boxLoaded.html(data).show(0);
                boxLoaded.find('fieldset').remove();
                boxLoaded.find('.row.defloc').remove();
                boxDefault.hide(0);

                hideEmptyResults(box);

                if (boxLoaded.find('a, div').length <= 0) {
                  box.hide(0);
                  boxLoaded.html('').hide(0);
                }
              },
              error: function () {
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
      };
    }
  }

  /**
   * Replace “Popular cities” in the default-location modal with main cities first.
   */
  function initPopularCitiesOrder() {
    if (typeof window.jQuery === 'undefined' || !window.baseAjaxUrl) {
      return;
    }

    var $ = window.jQuery;
    var loaded = false;

    function refreshPopular() {
      var row = $('#def-location .row.popular');

      if (!row.length) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: window.baseAjaxUrl + '&ajaxPngmPopularCities=1',
        success: function (html) {
          if (html && $.trim(html) !== '') {
            row.html(html);
            loaded = true;
          }
        }
      });
    }

    // Initial + whenever the location modal is opened.
    refreshPopular();
    $(document).on('click', 'a.location, #def-location', function () {
      if (!loaded) {
        refreshPopular();
      }
    });
  }

  /**
   * VEHICLE-01/02/03 — Make/Brand cascade helpers on publish form.
   * - Show free-text "Specify make / model" only when Other is selected.
   * - Never force incomplete 3rd-level selects as required.
   */
  function initVehicleMakeOther() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;

    function otherSelected() {
      var found = false;

      $('#atr-make select, #atr-form #atr-make select, .atr-form #atr-make select').each(function () {
        var text = $.trim($(this).find('option:selected').text());

        if (text === 'Other') {
          found = true;
          return false;
        }
      });

      return found;
    }

    function syncOtherField() {
      var box = $('#atr-make_other');

      if (!box.length) {
        return;
      }

      if (otherSelected()) {
        box.addClass('pngm-other-visible').show();
      } else {
        box.removeClass('pngm-other-visible').hide();
      }
    }

    function softenThirdLevelRequired() {
      if (!$('form[name="item"]').length || !$.fn || !$.fn.rules) {
        return;
      }

      $('#atr-make select[data-level], .atr-form #atr-make select[data-level]').each(function () {
        var level = parseInt($(this).attr('data-level'), 10) || 0;

        // VEHICLE-03 — levels 3+ must not be mandatory.
        if (level >= 3) {
          try {
            $(this).rules('remove', 'required');
          } catch (e) {
            // Validator may not be ready yet.
          }

          $(this).removeAttr('required').removeClass('error');
        }
      });
    }

    $(document).on('change', '#atr-make select, .atr-form #atr-make select', function () {
      syncOtherField();
      setTimeout(softenThirdLevelRequired, 50);
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
      if (settings && settings.url && String(settings.url).indexOf('atr_select_url') !== -1) {
        syncOtherField();
        setTimeout(softenThirdLevelRequired, 100);
      }
    });

    syncOtherField();
    setTimeout(softenThirdLevelRequired, 1800);
    setTimeout(softenThirdLevelRequired, 3200);
  }

  function init() {
    initCategories();
    initStickyHomeSearch();
    initPatternSearchLocation();
    initPopularCitiesOrder();
    initVehicleMakeOther();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
