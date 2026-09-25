/**
 * PNG Market - child theme scripts.
 *
 * Homepage category subcategories:
 *   - pointer devices on desktop get the CSS hover flyout, untouched here;
 *   - everything else opens a bottom sheet on tap, per HOME-04.
 */
(function () {
  'use strict';

  var pngmItemValidation = (function () {
    var pluginPatched = false;

    function escapeHtml(text) {
      var node = document.createElement('div');
      node.textContent = text == null ? '' : String(text);
      return node.innerHTML;
    }

    function ensureToastHost() {
      var host = document.getElementById('pngm-post-toast-host');
      if (!host) {
        host = document.createElement('div');
        host.id = 'pngm-post-toast-host';
        host.className = 'pngm-post-toast-host';
        host.setAttribute('aria-live', 'polite');
        host.setAttribute('aria-atomic', 'true');
        document.body.appendChild(host);
      }
      return host;
    }

    function hideToast() {
      var host = document.getElementById('pngm-post-toast-host');
      if (!host) {
        return;
      }
      host.innerHTML = '';
      host.classList.remove('is-visible');
    }

    function showPostToast(message, count) {
      var host = ensureToastHost();
      var extra = '';

      if (count > 1) {
        extra = '<span class="pngm-post-toast-more">' + (count - 1) + ' more field(s) need attention</span>';
      }

      host.innerHTML =
        '<div class="pngm-post-toast" role="alert">' +
          '<button type="button" class="pngm-post-toast-close" aria-label="Dismiss">&times;</button>' +
          '<strong class="pngm-post-toast-title">Required field missing</strong>' +
          '<span class="pngm-post-toast-msg">' + escapeHtml(message) + '</span>' +
          extra +
        '</div>';

      host.classList.add('is-visible');

      host.querySelector('.pngm-post-toast-close').addEventListener('click', hideToast);

      window.clearTimeout(host._pngmToastTimer);
      host._pngmToastTimer = window.setTimeout(hideToast, 7000);
    }

    function scrollTargetNode(element) {
      if (!element) {
        return null;
      }

      var node = element.nodeType ? element : (element[0] || null);
      if (!node) {
        return null;
      }

      var selectors = ['[id^="atr-"]', 'fieldset', '.box', '.atr-row', '.control-group', '.input-box', '.row', 'section'];
      var i;

      for (i = 0; i < selectors.length; i += 1) {
        var match = node.closest ? node.closest(selectors[i]) : null;
        if (match) {
          return match;
        }
      }

      return node;
    }

    function scrollToField(element) {
      var node = scrollTargetNode(element);
      if (!node || typeof node.scrollIntoView !== 'function') {
        return;
      }

      node.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

      window.setTimeout(function () {
        var focusNode = element && element.nodeType ? element : (element && element[0] ? element[0] : null);
        if (focusNode && typeof focusNode.focus === 'function') {
          try {
            focusNode.focus({ preventScroll: true });
          } catch (e) {
            focusNode.focus();
          }
        }
      }, 320);
    }

    function highlightField(element) {
      var $ = window.jQuery;
      if (!$) {
        return;
      }
      var $el = $(element);
      $el.addClass('error pngm-field-error');
      $el.closest('[id^="atr-"], fieldset, .box, .atr-row, .row, .control-group, .input-box, .controls, li, section').addClass('pngm-has-error');
    }

    function unhighlightField(element) {
      var $ = window.jQuery;
      if (!$) {
        return;
      }
      var $el = $(element);
      $el.removeClass('error pngm-field-error');
      $el.closest('[id^="atr-"], fieldset, .box, .atr-row, .row, .control-group, .input-box, .controls, li, section').removeClass('pngm-has-error');
    }

    function ensureHiddenErrorList($form) {
      var $ = window.jQuery;
      if (!$('#pngm-error-hidden').length) {
        $('<ul id="pngm-error-hidden" class="pngm-error-hidden" aria-hidden="true"></ul>').appendTo($form);
      }
      $('#error_list').addClass('pngm-error-hidden').attr('aria-hidden', 'true');
    }

    function handleInvalid(validator) {
      var now = Date.now();
      if (handleInvalid._lastRun && (now - handleInvalid._lastRun) < 300) {
        return;
      }
      handleInvalid._lastRun = now;

      var list = (validator && validator.errorList) ? validator.errorList : [];
      var message = 'Please complete all required fields.';
      var count = list.length;

      if (count > 0 && list[0].message) {
        message = list[0].message;
      }

      showPostToast(message, count);

      if (count > 0 && list[0].element) {
        window.setTimeout(function () {
          scrollToField(list[0].element);
        }, 50);
      }
    }

    function wrapValidateOptions(options) {
      options = options || {};
      options.errorLabelContainer = '#pngm-error-hidden';
      options.wrapper = options.wrapper || 'li';

      options.invalidHandler = function (event, validator) {
        handleInvalid(validator);
      };

      options.highlight = function (element) {
        highlightField(element);
      };

      options.unhighlight = function (element) {
        unhighlightField(element);
      };

      options.errorPlacement = function () {
        return false;
      };

      options.showErrors = function (errorMap, errorList) {
        this.defaultShowErrors();
        $('#error_list').empty().hide();
        $('#pngm-error-hidden').empty().hide();
      };

      return options;
    }

    function applyValidatorSettings(validator) {
      var $ = window.jQuery;
      if (!$ || !validator) {
        return false;
      }

      var form = $(validator.currentForm || 'form[name="item"]');
      var wrapped = wrapValidateOptions($.extend(true, {}, validator.settings));

      validator.settings.invalidHandler = wrapped.invalidHandler;
      validator.settings.highlight = wrapped.highlight;
      validator.settings.unhighlight = wrapped.unhighlight;
      validator.settings.errorPlacement = wrapped.errorPlacement;
      validator.settings.showErrors = wrapped.showErrors;
      validator.settings.errorLabelContainer = '#pngm-error-hidden';
      validator.settings.wrapper = wrapped.wrapper;

      // Parent theme binds invalidHandler to this event at init; settings alone are not enough.
      form.off('invalid-form.validate');
      form.on('invalid-form.validate', function (event, validatorInstance) {
        wrapped.invalidHandler.call(validatorInstance || validator, event, validatorInstance || validator);
      });

      return true;
    }

    function bindValidatorEvents(form, validator) {
      var $ = window.jQuery;
      if (!$ || !form.length || !validator || validator._pngmBound) {
        return;
      }

      validator._pngmBound = true;

      form.on('click.pngmValidate', 'button[type=submit], input[type=submit]', function () {
        window.setTimeout(function () {
          var activeValidator = form.data('validator');
          if (!activeValidator || !activeValidator.errorList || !activeValidator.errorList.length) {
            return;
          }

          $('html, body').stop(true);
          handleInvalid(activeValidator);
        }, 50);
      });

      form.on('input.pngmValidate change.pngmValidate', 'input, select, textarea', function () {
        if ($(this).valid()) {
          unhighlightField(this);
        }
      });
    }

    function forceEnhanceValidator() {
      var $ = window.jQuery;
      if (!$) {
        return false;
      }

      var form = $('form[name="item"]');
      if (!form.length) {
        return false;
      }

      ensureHiddenErrorList(form);

      var validator = form.data('validator');
      if (!validator) {
        return false;
      }

      applyValidatorSettings(validator);
      bindValidatorEvents(form, validator);
      validator._pngmEnhanced = true;

      return true;
    }

    function patchExistingForm() {
      return forceEnhanceValidator();
    }

    function patchValidatePlugin() {
      var $ = window.jQuery;
      if (!$ || !$.fn.validate || $.fn.validate._pngmPatched) {
        return !!($ && $.fn.validate && $.fn.validate._pngmPatched);
      }

      var originalValidate = $.fn.validate;
      $.fn.validate = function (options) {
        if (this.filter('form[name="item"]').length && options && typeof options === 'object') {
          ensureHiddenErrorList(this.filter('form[name="item"]'));
          options = wrapValidateOptions($.extend(true, {}, options));
        }
        var result = originalValidate.apply(this, arguments);
        patchExistingForm();
        return result;
      };
      $.fn.validate._pngmPatched = true;
      pluginPatched = true;
      return true;
    }

    function repatchValidatePlugin() {
      var $ = window.jQuery;
      if (!$ || !$.fn.validate) {
        return false;
      }

      if ($.fn.validate._pngmPatched) {
        return true;
      }

      pluginPatched = false;
      return patchValidatePlugin();
    }

    function init() {
      repatchValidatePlugin();
      forceEnhanceValidator();
    }

    return {
      init: init,
      handleInvalid: handleInvalid,
      patchExistingForm: patchExistingForm,
      patchValidatePlugin: patchValidatePlugin,
      repatchValidatePlugin: repatchValidatePlugin,
      forceEnhanceValidator: forceEnhanceValidator
    };
  })();

  window.pngmItemValidation = pngmItemValidation;
  pngmItemValidation.patchValidatePlugin();

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
    if (event.target.closest && event.target.closest('.pngm-subcat')) {
      return;
    }

    if (usesHoverFlyout()) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    openSheet(item);
  }

  function initCategories() {
    var grid = document.getElementById('home-cat');

    if (!grid) {
      return;
    }

    grid.addEventListener('click', onCategoryClick, true);

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
        var picker = elem.closest('.picker');

        // Search results page: keyword goes straight to the board — no suggestion dropdown.
        if (
          $('body#search').length &&
          (picker.hasClass('pngm-no-suggest') ||
            elem.closest('.search-side-form, .pngm-global-search').length)
        ) {
          picker.find('.results').hide(0);
          return false;
        }

        var min = 1;
        var box = picker.find('.results');
        var boxLoaded = picker.find('.results .loaded');
        var boxDefault = picker.find('.results .default');
        var rawTerm = $.trim($(elem).val() || '');
        var term = encodeURIComponent(rawTerm);

        if (window.epsLoadPatternSimpleValue == term) {
          box.show(0);
          if (rawTerm.length === 0) {
            boxDefault.show(0);
            boxLoaded.hide(0);
          } else {
            boxLoaded.show(0);
            boxDefault.hide(0);
            hideEmptyResults(box);
          }
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

        // Empty focus: show default suggestions (recent / popular), not a blank hide.
        if (rawTerm.length === 0) {
          elem.closest('.picker').removeClass('loading');
          boxLoaded.html('').hide(0);
          if (boxDefault.length && boxDefault.children().length) {
            box.show(0);
            boxDefault.show(0);
          } else {
            box.hide(0);
          }
          return false;
        }

        elem.closest('.picker').addClass('loading');

        window.epsLoadPatternSimpleTimeout = setTimeout(function () {
          if (term.length >= min) {
            $.ajax({
              type: 'GET',
              // Always global — do not append sCity / sRegion from default location.
              url: window.baseAjaxUrl + '&ajaxPatternSearch=1&term=' + term,
              success: function (data) {
                elem.closest('.picker').removeClass('loading');
                var html = $.trim(data || '');
                boxDefault.hide(0);
                if (!html) {
                  box.hide(0);
                  boxLoaded.html('').hide(0);
                  return;
                }
                box.show(0);
                boxLoaded.html(html).show(0);
                boxLoaded.find('fieldset').remove();
                boxLoaded.find('.row.defloc').remove();
                hideEmptyResults(box);
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

    // Also restore parent location loader so empty focus can request main cities.
    if (typeof window.epsLoadLocationsSimple === 'function') {
      window.epsLoadLocationsSimple = function (elem, event, type) {
        type = typeof type === 'undefined' ? '' : type;
        var min = 0;
        var box = elem.closest('.picker').find('.results');
        var raw = $.trim($(elem).val() || '');
        var term = raw;
        term = term.indexOf(',') > 1 ? term.substr(0, term.indexOf(',')) : term;
        term = term.indexOf('-') > 1 ? term.substr(0, term.indexOf('-')) : term;
        term = encodeURIComponent(term).trim();

        if (window.epsLoadLocationsSimpleValue == term && box.find('a, div, .option').length) {
          box.show(0);
          return false;
        }
        window.epsLoadLocationsSimpleValue = term;

        if (typeof window.epsLoadLocationsSimpleTimeout !== 'undefined') {
          clearTimeout(window.epsLoadLocationsSimpleTimeout);
        }
        if (raw.length > 0) {
          elem.siblings('.clean').show(0);
        } else {
          elem.siblings('.clean').hide(0);
        }

        elem.closest('.picker').addClass('loading');
        window.epsLoadLocationsSimpleTimeout = setTimeout(function () {
          $.ajax({
            type: 'GET',
            url: window.baseAjaxUrl + '&ajaxLoc=1&dataType=' + type + '&term=' + term,
            success: function (data) {
              elem.closest('.picker').removeClass('loading');
              var html = $.trim(data || '');
              if (!html) {
                box.html('').hide(0);
                return;
              }
              box.html(html).show(0);
              box.find('fieldset').remove();
            },
            error: function () {
              elem.closest('.picker').removeClass('loading');
              box.html('').hide(0);
            }
          });
        }, 220);
      };
    }
  }

  /**
   * LOCATION-01 — Main cities list in side-menu + location modal.
   * Mobile opens #side-menu (not #def-location), so both must be updated.
   */
  function initPopularCitiesOrder() {
    if (typeof window.jQuery === 'undefined' || !window.baseAjaxUrl) {
      return;
    }

    var $ = window.jQuery;

    function refreshPopular(root) {
      var $root = root ? $(root) : $(document);
      var rows = $root.find('.box.location .row.popular, #def-location .row.popular, #side-menu .row.popular');

      if (!rows.length) {
        rows = $('.box.location .row.popular, #def-location .row.popular, #side-menu .row.popular');
      }

      if (!rows.length) {
        return;
      }

      $.ajax({
        type: 'GET',
        url: window.baseAjaxUrl + '&ajaxPngmPopularCities=1',
        cache: false,
        success: function (html) {
          if (!html || $.trim(html) === '') {
            return;
          }

          rows.each(function () {
            $(this).html(html);
          });
        }
      });
    }

    refreshPopular();

    // Bottom nav / header location open — refresh every time.
    $(document).on('click', '#navi-bar a.location, header .links .btn.location, .change-location, .change-search-location, a.location', function () {
      setTimeout(function () {
        refreshPopular();
      }, 50);
      setTimeout(function () {
        refreshPopular('#def-location');
        refreshPopular('#side-menu');
      }, 280);
    });
  }

  /**
   * LOCATION-02 — City select: Main towns first, villages under Other locations.
   * Works for ajax-loaded lists and the initial publish/edit form select.
   */
  function initPostingCityGrouping() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;
    var cfg = window.pngmLocationConfig || {};
    var labels = cfg.labels || {};
    var labelMain = labels.main || 'Main towns';
    var labelOther = labels.other || 'Other locations';
    var labelSelect = labels.select || 'Select a city...';

    function locKey(name) {
      return String(name || '')
        .replace(/^\s+|\s+$/g, '')
        .toLowerCase()
        .replace(/mt\.?\s+/g, 'mount ')
        .replace(/\s+/g, ' ');
    }

    function regionKeys(name) {
      var key = locKey(name);
      var keys = [];
      var stripped;

      if (!key) {
        return keys;
      }

      keys.push(key);
      stripped = key.replace(/\s+(province|district)$/g, '');
      if (stripped && stripped !== key) {
        keys.push(stripped);
      }
      if (stripped && !/province$/.test(key) && !/district$/.test(key)) {
        keys.push(stripped + ' province');
      }

      return keys;
    }

    function capitalForRegion(regionName) {
      var capitals = cfg.capitals || {};
      var keys = regionKeys(regionName);
      var i;

      for (i = 0; i < keys.length; i++) {
        if (capitals[keys[i]]) {
          return capitals[keys[i]];
        }
      }

      return '';
    }

    function cityIsCapital(cityName, capitalName) {
      var city = locKey(cityName);
      var cap = locKey(capitalName);

      if (!city || !cap) {
        return false;
      }

      if (city === cap) {
        return true;
      }

      return new RegExp('(^|[\\s,\\-/])' + cap.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '($|[\\s,\\-/])').test(city);
    }

    function mainKeysForRegion(regionName) {
      var keys = {};
      var rks = regionKeys(regionName);
      var capitals = cfg.capitals || {};
      var towns = cfg.mainTowns || {};
      var i;
      var j;
      var rk;

      for (i = 0; i < rks.length; i++) {
        rk = rks[i];
        if (capitals[rk]) {
          keys[locKey(capitals[rk])] = true;
        }
        if (towns[rk] && towns[rk].length) {
          for (j = 0; j < towns[rk].length; j++) {
            keys[locKey(towns[rk][j])] = true;
          }
        }
      }

      return keys;
    }

    function isMainCity(name, regionName, tier) {
      if (cityIsCapital(name, capitalForRegion(regionName))) {
        return true;
      }

      if (tier === 'main') {
        return true;
      }

      if (tier === 'other') {
        return false;
      }

      var keys = mainKeysForRegion(regionName);
      return !!keys[locKey(name)];
    }

    function suburbRank(name) {
      var list = cfg.ncdSuburbs || [];
      var key = locKey(name);
      var i;

      for (i = 0; i < list.length; i++) {
        if (locKey(list[i]) === key) {
          return i;
        }
      }

      return -1;
    }

    function isNcdRegion(regionName) {
      var keys = regionKeys(regionName);
      var i;

      for (i = 0; i < keys.length; i++) {
        if (keys[i] === 'national capital district' || keys[i] === 'ncd') {
          return true;
        }
      }

      return false;
    }

    function optionHtml(id, name, selectedId) {
      var sel = String(id) === String(selectedId) ? ' selected' : '';
      return '<option value="' + id + '"' + sel + '>' + name + '</option>';
    }

    function appendGroup(html, label, rows, selectedId) {
      var i;

      if (!rows.length) {
        return html;
      }

      html += '<optgroup label="' + label + '">';
      for (i = 0; i < rows.length; i++) {
        html += optionHtml(rows[i].id, rows[i].name, selectedId);
      }
      html += '</optgroup>';

      return html;
    }

    function mainPriority(name) {
      var list = cfg.mainCities || [];
      var key = locKey(name);
      var i;

      for (i = 0; i < list.length; i++) {
        if (locKey(list[i]) === key) {
          return i;
        }
      }

      return 1000;
    }

    function buildGroupedHtml(items, selectedId, regionName) {
      var main = [];
      var suburbs = [];
      var other = [];
      var i;
      var html = '<option value="">' + labelSelect + '</option>';
      var cap = capitalForRegion(regionName);
      var ncd = isNcdRegion(regionName);
      var labelSuburbs = (labels.ncdSuburbs || 'Port Moresby suburbs');

      for (i = 0; i < items.length; i++) {
        if (ncd && (items[i].suburb || suburbRank(items[i].name) >= 0) && !cityIsCapital(items[i].name, 'Port Moresby')) {
          suburbs.push(items[i]);
        } else if (items[i].main) {
          main.push(items[i]);
        } else {
          other.push(items[i]);
        }
      }

      main.sort(function (a, b) {
        var ac = cityIsCapital(a.name, cap);
        var bc = cityIsCapital(b.name, cap);

        if (ac && !bc) {
          return -1;
        }
        if (bc && !ac) {
          return 1;
        }

        var pa = mainPriority(a.name);
        var pb = mainPriority(b.name);

        if (pa !== pb) {
          return pa - pb;
        }

        return String(a.name).localeCompare(String(b.name));
      });

      suburbs.sort(function (a, b) {
        var ra = suburbRank(a.name);
        var rb = suburbRank(b.name);

        if (ra !== rb) {
          return ra - rb;
        }

        return String(a.name).localeCompare(String(b.name));
      });

      other.sort(function (a, b) {
        return String(a.name).localeCompare(String(b.name));
      });

      html = appendGroup(html, ncd ? 'Port Moresby' : labelMain, main, selectedId);
      if (ncd) {
        html = appendGroup(html, labelSuburbs, suburbs, selectedId);
      }
      html = appendGroup(html, labelOther, other, selectedId);

      return html;
    }

    function regroupCitySelect($city, regionName, data) {
      if (!$city || !$city.length) {
        return;
      }

      var selectedId = $city.val();
      var items = [];
      var i;
      var name;
      var id;
      var tier;
      var currentRegionId = String($('#regionId').val() || '');

      if ($.isArray(data) && data.length) {
        for (i = 0; i < data.length; i++) {
          if (!data[i] || data[i].pk_i_id === undefined) {
            continue;
          }

          if (currentRegionId && data[i].fk_i_region_id !== undefined
              && String(data[i].fk_i_region_id) !== currentRegionId) {
            continue;
          }

          name = data[i].s_name_native && data[i].s_name_native !== 'null'
            ? data[i].s_name_native
            : data[i].s_name;
          id = data[i].pk_i_id;
          tier = data[i].pngm_tier || '';
          items.push({
            id: id,
            name: name,
            main: isMainCity(name, regionName, tier),
            suburb: tier === 'suburb' || suburbRank(name) >= 0
          });
        }
      } else {
        $city.find('option').each(function () {
          var $opt = $(this);
          id = $opt.attr('value');
          name = $.trim($opt.text());

          if (!id) {
            return;
          }

          items.push({
            id: id,
            name: name,
            main: isMainCity(name, regionName, ''),
            suburb: suburbRank(name) >= 0
          });
        });
      }

      if (!items.length) {
        return;
      }

      // Keep relative order (already main-first from server when data provided).
      $city.html(buildGroupedHtml(items, selectedId, regionName));

      if (selectedId) {
        $city.val(selectedId);
      }

      if (currentRegionId) {
        $city.attr('data-pngm-region-id', currentRegionId);
        $city.removeAttr('data-pngm-loading');
      }

      if (typeof window.pngmRefreshSearchSelect === 'function') {
        window.pngmRefreshSearchSelect($city);
      }
    }

    function currentRegionName() {
      var $region = $('#regionId');

      if (!$region.length) {
        return '';
      }

      return $.trim($region.find('option:selected').text());
    }

    function requestedRegionId(url) {
      var match = String(url || '').match(/[?&]regionId=([^&]*)/);
      return match ? decodeURIComponent(match[1]) : '';
    }

    function setCityLoadState(regionId, state) {
      var $city = $('#cityId');
      var loadingText = labels.loadingCities || 'Loading cities...';

      if (!$city.length || !$city.is('select')) {
        return;
      }

      if (state === 'loading') {
        $city.attr('data-pngm-loading', '1');
        $city.removeAttr('data-pngm-region-id');
        $city.attr('data-pngm-pending-region', String(regionId || ''));
        $city.prop('disabled', false);
        $city.html('<option selected value="">' + $('<div>').text(loadingText).html() + '</option>');
        $city.val('');
      } else if (state === 'ready') {
        $city.removeAttr('data-pngm-loading');
        $city.removeAttr('data-pngm-pending-region');
        $city.attr('data-pngm-region-id', String(regionId || ''));
        $city.prop('disabled', !regionId);
      } else {
        $city.removeAttr('data-pngm-loading');
        $city.removeAttr('data-pngm-pending-region');
        $city.removeAttr('data-pngm-region-id');
        $city.html('<option selected value="">' + $('<div>').text(labelSelect).html() + '</option>');
        $city.val('');
        $city.prop('disabled', true);
      }

      if (typeof window.pngmRefreshSearchSelect === 'function') {
        window.pngmRefreshSearchSelect($city);
      }
    }

    function applyCitiesForRegion(regionId, data) {
      var current = String($('#regionId').val() || '');
      var requested = String(regionId || '');

      if (!requested || (current && requested !== current)) {
        return;
      }

      var $city = $('#cityId');
      var result = '';
      var i;
      var row;
      var vname;
      var locationsNative = '0';

      if (!$city.length || !$city.is('select')) {
        // Core may have swapped select for text input — recreate select.
        $('#city').before('<select name="cityId" id="cityId"></select>');
        $('#city').remove();
        $city = $('#cityId');
        if (typeof window.pngmRefreshSearchSelect === 'function') {
          window.pngmRefreshSearchSelect($city);
        }
      }

      if (!$.isArray(data) || !data.length) {
        $city.html('<option value="">' + $('<div>').text(labels.noResults || 'No results').html() + '</option>');
        setCityLoadState(requested, 'ready');
        return;
      }

      result += '<option selected value="">' + $('<div>').text(labelSelect).html() + '</option>';

      for (i = 0; i < data.length; i++) {
        row = data[i];
        if (!row || row.pk_i_id === undefined) {
          continue;
        }
        // Hard filter: never paint a city from another province.
        if (row.fk_i_region_id !== undefined && String(row.fk_i_region_id) !== requested) {
          continue;
        }
        vname = row.s_name;
        if (row.s_name_native && row.s_name_native !== '' && row.s_name_native !== 'null' && locationsNative === '1') {
          vname = row.s_name_native;
        }
        result += '<option value="' + row.pk_i_id + '">' + $('<div>').text(vname).html() + '</option>';
      }

      $city.html(result);
      setCityLoadState(requested, 'ready');
      regroupCitySelect($city, currentRegionName(), data);
      $city.trigger('change');
    }

    // Drop previous province cities immediately and show loading.
    $(document).on('change.pngmClearCities', '#regionId', function () {
      var regionId = String($(this).val() || '');
      window.pngmCitiesToken = (window.pngmCitiesToken || 0) + 1;
      window.pngmPendingCityRegionId = regionId;

      if (!regionId) {
        setCityLoadState('', 'idle');
        return;
      }

      setCityLoadState(regionId, 'loading');
    });

    // Take over cities AJAX success so core cannot write a stale province list.
    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
      var url = options && options.url ? String(options.url) : '';

      if (url.indexOf('action=cities') === -1) {
        return;
      }

      var requested = String(requestedRegionId(url) || '');

      // Replace core success entirely — we own writing #cityId.
      options.success = function (data) {
        var current = String($('#regionId').val() || '');
        var pending = String(window.pngmPendingCityRegionId || current || '');

        if (!requested || requested !== current || requested !== pending) {
          return;
        }

        applyCitiesForRegion(requested, data);
      };
    });

    $(document).ajaxSend(function (event, jqXHR, settings) {
      var url = settings && settings.url ? String(settings.url) : '';

      if (url.indexOf('action=cities') === -1) {
        return;
      }

      if (window.pngmCitiesXhr && window.pngmCitiesXhr !== jqXHR && window.pngmCitiesXhr.readyState !== 4) {
        try {
          window.pngmCitiesXhr.abort();
        } catch (e) {}
      }

      window.pngmCitiesXhr = jqXHR;
      window.pngmCitiesRequestedRegionId = requestedRegionId(url);
    });

    $(document).ajaxError(function (event, xhr, settings) {
      var url = settings && settings.url ? String(settings.url) : '';
      var requested;
      var current;

      if (url.indexOf('action=cities') === -1) {
        return;
      }

      // Aborted on purpose when province changes again.
      if (xhr && xhr.statusText === 'abort') {
        return;
      }

      requested = requestedRegionId(url);
      current = String($('#regionId').val() || '');

      if (requested && current && requested === current) {
        setCityLoadState(current, 'loading');
        $('#cityId').html('<option value="">' + $('<div>').text(labels.loadError || 'Could not load cities').html() + '</option>');
        if (typeof window.pngmRefreshSearchSelect === 'function') {
          window.pngmRefreshSearchSelect($('#cityId'));
        }
      }
    });

    // Initial publish/edit form: regroup the PHP-rendered city list.
    setTimeout(function () {
      var $city = $('#cityId');
      var regionId = String($('#regionId').val() || '');

      if ($city.length && $city.find('option[value!=""]').length > 0) {
        $city.attr('data-pngm-region-id', regionId);
        $city.removeAttr('data-pngm-loading');
        regroupCitySelect($city, currentRegionName(), null);
      }
    }, 100);
  }

  /**
   * Searchable Province / City selects on publish (and profile) forms.
   * Keeps the native <select> for validation + existing change handlers.
   */
  function initSearchableLocationSelects() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;
    var cfg = window.pngmLocationConfig || {};
    var labels = cfg.labels || {};
    var activeWidget = null;

    function escapeHtml(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function selectedLabel($select) {
      var $opt = $select.find('option:selected');
      var val = $select.val();

      if (!val || !$opt.length) {
        return '';
      }

      return $.trim($opt.text());
    }

    function placeholderFor($select) {
      if ($select.attr('id') === 'regionId') {
        return labels.searchRegion || 'Type to search province...';
      }

      return labels.searchCity || 'Type to search city...';
    }

    function closeWidget(widget) {
      if (!widget) {
        return;
      }

      widget.removeClass('is-open');
      widget.find('.pngm-search-select-list').hide();
      widget.find('.pngm-search-select-input').attr('aria-expanded', 'false');

      if (activeWidget && activeWidget[0] === widget[0]) {
        activeWidget = null;
      }
    }

    function closeAll(except) {
      $('.pngm-search-select.is-open').each(function () {
        if (except && this === except[0]) {
          return;
        }

        closeWidget($(this));
      });
    }

    function syncFromSelect(widget) {
      var $select = widget.data('pngmSelect');
      var $input = widget.find('.pngm-search-select-input');
      var disabled = !!$select.prop('disabled');
      var loading = $select.attr('data-pngm-loading') === '1';
      var label = selectedLabel($select);

      if (loading) {
        $input.val('');
        $input.attr('placeholder', labels.loadingCities || 'Loading cities...');
      } else {
        $input.val(label);
        $input.attr('placeholder', placeholderFor($select));
      }

      $input.prop('disabled', disabled && !loading);
      widget.toggleClass('is-disabled', disabled && !loading);
      widget.toggleClass('is-loading', loading);
      widget.find('.pngm-search-select-clear').toggle(!!label && !disabled && !loading);
    }

    function buildListHtml($select, query) {
      var q = String(query || '').toLowerCase().replace(/^\s+|\s+$/g, '');
      var html = '';
      var matches = 0;
      var $children = $select.children();

      function optionMatches($opt) {
        var val = $opt.attr('value');
        var text = $.trim($opt.text());

        if (!val) {
          return false;
        }

        if (!q) {
          return true;
        }

        return text.toLowerCase().indexOf(q) !== -1;
      }

      $children.each(function () {
        var $node = $(this);

        if ($node.is('optgroup')) {
          var groupItems = '';
          var groupCount = 0;

          $node.children('option').each(function () {
            var $opt = $(this);

            if (!optionMatches($opt)) {
              return;
            }

            groupCount += 1;
            matches += 1;
            groupItems += '<button type="button" class="pngm-search-select-option" data-value="'
              + escapeHtml($opt.attr('value')) + '" role="option">'
              + escapeHtml($.trim($opt.text())) + '</button>';
          });

          if (groupCount > 0) {
            html += '<div class="pngm-search-select-group">'
              + '<div class="pngm-search-select-group-label">' + escapeHtml($node.attr('label') || '') + '</div>'
              + groupItems
              + '</div>';
          }

          return;
        }

        if ($node.is('option')) {
          if (!optionMatches($node)) {
            return;
          }

          matches += 1;
          html += '<button type="button" class="pngm-search-select-option" data-value="'
            + escapeHtml($node.attr('value')) + '" role="option">'
            + escapeHtml($.trim($node.text())) + '</button>';
        }
      });

      if (!matches) {
        html = '<div class="pngm-search-select-empty">'
          + escapeHtml(labels.noMatch || 'No matching locations')
          + '</div>';
      }

      return html;
    }

    function showList(widget, filterText) {
      var $select = widget.data('pngmSelect');
      var $list = widget.find('.pngm-search-select-list');
      var $input = widget.find('.pngm-search-select-input');
      var loadedFor;
      var currentRegion;

      if ($select.prop('disabled') && $select.attr('data-pngm-loading') !== '1') {
        return;
      }

      closeAll(widget);

      // Never show a previous province's towns while cities are loading / mismatched.
      if ($select.attr('id') === 'cityId') {
        currentRegion = String($('#regionId').val() || '');
        loadedFor = String($select.attr('data-pngm-region-id') || '');

        if (!currentRegion) {
          $list.html('<div class="pngm-search-select-empty">'
            + escapeHtml(labels.selectRegionFirst || 'Select a province first')
            + '</div>');
          $list.show();
          widget.addClass('is-open');
          $input.attr('aria-expanded', 'true');
          activeWidget = widget;
          return;
        }

        // Only render options that were loaded for the currently selected province.
        if (loadedFor !== currentRegion) {
          $list.html('<div class="pngm-search-select-empty">'
            + escapeHtml(labels.loadingCities || 'Loading cities...')
            + '</div>');
          $list.show();
          widget.addClass('is-open is-loading');
          $input.attr('aria-expanded', 'true');
          activeWidget = widget;
          return;
        }
      }

      widget.removeClass('is-loading');
      $list.html(buildListHtml($select, filterText));
      $list.show();
      widget.addClass('is-open');
      $input.attr('aria-expanded', 'true');
      activeWidget = widget;

      var val = $select.val();

      if (val) {
        $list.find('.pngm-search-select-option').each(function () {
          if (String($(this).attr('data-value')) === String(val)) {
            $(this).addClass('is-active is-selected');
          }
        });
      }

      // Always start at MAIN TOWNS. Do not restore a previous scroll, and do
      // not jump down to a pre-selected city on first open.
      $list.scrollTop(0);
      if (window.requestAnimationFrame) {
        window.requestAnimationFrame(function () {
          $list.scrollTop(0);
        });
      }
    }

    function pickValue(widget, value, label) {
      var $select = widget.data('pngmSelect');
      var $input = widget.find('.pngm-search-select-input');

      $select.val(value).trigger('change');
      $input.val(label || selectedLabel($select));
      widget.find('.pngm-search-select-clear').toggle(!!$select.val());
      closeWidget(widget);
    }

    function sortProvinceSelect($select) {
      if (!$select || !$select.length || $select.attr('id') !== 'regionId') {
        return;
      }

      var popular = cfg.popularProvinces || [];
      var $empty = $select.find('option').filter(function () {
        return !$(this).attr('value');
      }).first().detach();
      var items = [];

      $select.find('option').each(function () {
        var $opt = $(this);
        var val = $opt.attr('value');
        var name = $.trim($opt.text());

        if (!val) {
          return;
        }

        items.push({
          val: val,
          name: name,
          html: $opt.prop('outerHTML'),
          selected: $opt.prop('selected')
        });
      });

      function provincePriority(name) {
        var key = String(name || '')
          .replace(/^\s+|\s+$/g, '')
          .toLowerCase()
          .replace(/\s+/g, ' ');
        var variants = [key];
        var stripped = key.replace(/\s+(province|district)$/g, '');
        var i;
        var k;

        if (stripped && stripped !== key) {
          variants.push(stripped);
        }
        if (stripped && !/province$/.test(key) && !/district$/.test(key)) {
          variants.push(stripped + ' province');
        }

        for (k = 0; k < variants.length; k++) {
          for (i = 0; i < popular.length; i++) {
            if (popular[i] === variants[k]) {
              return i;
            }
          }
        }

        return 1000;
      }

      items.sort(function (a, b) {
        var pa = provincePriority(a.name);
        var pb = provincePriority(b.name);

        if (pa !== pb) {
          return pa - pb;
        }

        return String(a.name).localeCompare(String(b.name));
      });

      $select.empty();
      if ($empty.length) {
        $select.append($empty);
      }

      $.each(items, function (i, item) {
        $select.append(item.html);
      });
    }

    function wrapSelect($select) {
      if (!$select.length || $select.data('pngmSearchWrapped')) {
        return $select.closest('.pngm-search-select');
      }

      if (!$select.is('select')) {
        return $();
      }

      if ($select.attr('id') === 'regionId') {
        sortProvinceSelect($select);
      }

      var $widget = $('<div class="pngm-search-select" role="combobox" aria-haspopup="listbox"></div>');
      var $input = $('<input type="text" class="pngm-search-select-input" autocomplete="off" aria-autocomplete="list" aria-expanded="false" />');
      var $clear = $('<button type="button" class="pngm-search-select-clear" aria-label="Clear" title="Clear">&times;</button>');
      var $list = $('<div class="pngm-search-select-list" role="listbox"></div>');

      $input.attr('placeholder', placeholderFor($select));
      $select.addClass('pngm-search-select-native').data('pngmSearchWrapped', true);
      $select.after($widget);
      $widget.append($select);
      $widget.append($input);
      $widget.append($clear);
      $widget.append($list);
      $widget.data('pngmSelect', $select);

      syncFromSelect($widget);

      $input.on('focus', function () {
        if ($select.prop('disabled')) {
          return;
        }

        // Show full list on focus; keep current label until user types.
        showList($widget, '');
        this.select();
      });

      $input.on('input', function () {
        showList($widget, $input.val());
        $widget.find('.pngm-search-select-clear').toggle($.trim($input.val()) !== '');
      });

      $input.on('blur', function () {
        setTimeout(function () {
          if (!$widget.hasClass('is-open')) {
            syncFromSelect($widget);
          }
        }, 120);
      });

      $input.on('keydown', function (e) {
        var $options;
        var $active;
        var idx;

        if (e.key === 'Escape') {
          syncFromSelect($widget);
          closeWidget($widget);
          return;
        }

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault();

          if (!$widget.hasClass('is-open')) {
            showList($widget, $input.val());
          }

          $options = $widget.find('.pngm-search-select-option');

          if (!$options.length) {
            return;
          }

          $active = $options.filter('.is-active');
          idx = $options.index($active);

          if (e.key === 'ArrowDown') {
            idx = idx < $options.length - 1 ? idx + 1 : 0;
          } else {
            idx = idx > 0 ? idx - 1 : $options.length - 1;
          }

          $options.removeClass('is-active');
          $active = $options.eq(idx).addClass('is-active');

          if ($active[0] && $active[0].scrollIntoView) {
            $active[0].scrollIntoView({ block: 'nearest' });
          }

          return;
        }

        if (e.key === 'Enter') {
          $active = $widget.find('.pngm-search-select-option.is-active').first();

          if (!$active.length) {
            $active = $widget.find('.pngm-search-select-option').first();
          }

          if ($active.length) {
            e.preventDefault();
            pickValue($widget, $active.attr('data-value'), $.trim($active.text()));
          }
        }
      });

      $clear.on('mousedown', function (e) {
        e.preventDefault();
        pickValue($widget, '', '');
        $input.focus();
      });

      $list.on('mousedown', '.pngm-search-select-option', function (e) {
        e.preventDefault();
        pickValue($widget, $(this).attr('data-value'), $.trim($(this).text()));
      });

      $select.on('change.pngmSearchSelect', function () {
        syncFromSelect($widget);
      });

      return $widget;
    }

    function refresh($select) {
      var $el = $($select);

      // Drop orphan wrappers left behind when core replaces #cityId.
      $('.row.city .pngm-search-select, .row.region .pngm-search-select').each(function () {
        var $w = $(this);

        if (!$w.find('select#regionId, select#cityId').length) {
          $w.remove();
        }
      });

      $el = $($select);

      if (!$el.length || !$el.is('select')) {
        return;
      }

      if (!$el.data('pngmSearchWrapped') || !$el.parent().hasClass('pngm-search-select')) {
        $el.removeData('pngmSearchWrapped');
        wrapSelect($el);
        return;
      }

      var $widget = $el.closest('.pngm-search-select');
      syncFromSelect($widget);

      // If the city dropdown is open while province options reload, rebuild it
      // so users never keep seeing the previous province's towns.
      if ($widget.hasClass('is-open') && $el.attr('id') === 'cityId') {
        showList($widget, $widget.find('.pngm-search-select-input').val());
      }
    }

    window.pngmRefreshSearchSelect = refresh;

    // Only enhance classic region/city selects (not autocomplete location mode).
    if ($('#regionId').is('select') || $('#cityId').is('select')) {
      wrapSelect($('#regionId'));
      wrapSelect($('#cityId'));

      // Mark PHP-rendered cities as belonging to the current province so the
      // dropdown is allowed to open immediately on first paint.
      if ($('#cityId').is('select') && $('#regionId').val()
          && $('#cityId').find('option[value!=""]').length > 0) {
        $('#cityId').attr('data-pngm-region-id', String($('#regionId').val()));
        $('#cityId').removeAttr('data-pngm-loading');
      }
    }

    $(document).on('mousedown.pngmSearchSelect', function (e) {
      if ($(e.target).closest('.pngm-search-select').length) {
        return;
      }

      closeAll();
      $('.pngm-search-select').each(function () {
        syncFromSelect($(this));
      });
    });

    // Keep city widget in sync when province changes / city list reloads.
    $(document).on('change', '#regionId', function () {
      setTimeout(function () {
        refresh($('#cityId'));
      }, 100);
    });

    $(document).ajaxSuccess(function (event, xhr, settings) {
      var url = settings && settings.url ? String(settings.url) : '';

      if (url.indexOf('action=regions') === -1) {
        return;
      }

      setTimeout(function () {
        var $region = $('#regionId');
        if ($region.length) {
          sortProvinceSelect($region);
          refresh($region);
        }
      }, 0);
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

      // Attributes plugin calls .rules() when jquery.validate is loaded; without a
      // form validator instance that throws: Cannot read properties of undefined (settings)
      if (!$('form[name="item"]').data('validator')) {
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

    // Wizard reloads these fields over AJAX when the category changes.
    $(document).on('pngm:attrs-loaded', function () {
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

  /**
   * P2-006 — Label Make cascade level-2 as "Model" on vehicle search filters.
   */
  function initVehicleSearchModelLabel() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }
    var $ = window.jQuery;

    function labelModelSelects(root) {
      var $root = root ? $(root) : $('#atr-search #atr-make, #side-menu #atr-make');
      if (!$root.length) {
        $root = $('#atr-search #atr-make');
      }
      $root.each(function () {
        var $block = $(this);
        $block.find('select[data-level="1"]').each(function () {
          var $opt = $(this).find('option[value=""]').first();
          if ($opt.length) {
            if (/select/i.test($opt.text()) && !/brand|make/i.test($opt.text())) {
              $opt.text('Select make / brand…');
            }
          }
        });
        $block.find('select[data-level="2"]').each(function () {
          var $sel = $(this);
          if (!$sel.attr('aria-label')) {
            $sel.attr('aria-label', 'Model');
          }
          var $opt = $sel.find('option[value=""]').first();
          if ($opt.length) {
            $opt.text('Select model…');
          }
          // Visible field label once (search sidebar).
          if (!$sel.prev('.pngm-atr-model-label').length && !$sel.closest('label').length) {
            $sel.before('<span class="pngm-atr-model-label">Model</span>');
          }
        });
      });
    }

    labelModelSelects();
    $(document).on('change', '#atr-search #atr-make select, #side-menu #atr-make select', function () {
      setTimeout(function () { labelModelSelects(); }, 30);
    });
    $(document).ajaxComplete(function (event, xhr, settings) {
      if (settings && settings.url && String(settings.url).indexOf('atr_select_url') !== -1) {
        setTimeout(function () { labelModelSelects(); }, 40);
      }
    });
  }

  /**
   * ITEM-01 / ITEM-02 — Listing photo gallery.
   * Native pinch/pan/swipe viewer lives in gallery.js.
   */
  function initItemGallery() {
    if (typeof window.pngmInitItemGallery === 'function') {
      window.pngmInitItemGallery(window.jQuery);
    }
  }

  /**
   * DASHBOARD-01 — Light UX helpers for account pages.
   */
  function initUserAccountUx() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;
    var body = $('body.body-ua');

    if (!body.length) {
      return;
    }

    body.addClass('pngm-ua');

    // Ensure side menu has class for CSS targeting when parent templates are used.
    $('#user-menu').addClass('pngm-user-menu');

    // Make plugin-injected menu links (messages/favourites) easier to spot.
    $('#user-menu a').each(function () {
      var text = $.trim($(this).text()).toLowerCase();
      var href = String($(this).attr('href') || '').toLowerCase();

      if (text.indexOf('message') !== -1 || href.indexOf('im-threads') !== -1) {
        $(this).addClass('pngm-nav-messages');
      }

      if (text.indexOf('favorite') !== -1 || text.indexOf('favourite') !== -1 || href.indexOf('favorite') !== -1) {
        $(this).addClass('pngm-nav-favorite');
      }
    });

    initUaMobileDrawer($);
  }

  /**
   * Mobile account nav: hide in-page sidebar; open designed drawer via hamburger.
   * Must beat theme #side-menu (z-index 10120) and menu-cover (10100).
   */
  function initUaMobileDrawer($) {
    var $sidebar = $('#user-menu.pngm-ua-sidebar');
    if (!$sidebar.length) {
      return;
    }

    function isMobileUa() {
      return window.matchMedia('(max-width: 980px)').matches;
    }

    function hideGlobalSideMenu() {
      var $menu = $('#side-menu');
      $menu.stop(true, true).hide(0).removeClass('box-open');
      $menu.find('.box').hide(0);
      $menu.css({
        'margin-left': '',
        'margin-right': '',
        opacity: ''
      });
    }

    function openUaNav(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
          e.stopImmediatePropagation();
        }
      }
      hideGlobalSideMenu();
      $('body').addClass('pngm-ua-nav-open');
      $('#menu-cover').stop(true, true).fadeIn(200);
    }

    function closeUaNav() {
      $('body').removeClass('pngm-ua-nav-open');
    }

    // Replace theme hamburger handler on account pages so #side-menu never opens.
    var $btn = $('header .menu.btn');

    function bindHamburger() {
      $btn.off('click.pngmUaNav');
      // Wipe theme global.js click binder on this control for UA pages only.
      $btn.off('click');
      $btn.on('click.pngmUaNav', function (e) {
        if (!isMobileUa()) {
          return;
        }
        openUaNav(e);
      });
    }

    bindHamburger();
    // Beat late global.js / other ready handlers that re-bind the hamburger.
    window.setTimeout(bindHamburger, 0);
    window.setTimeout(bindHamburger, 400);

    // Capture fallback if another script re-binds later
    var btnEl = $btn.get(0);
    if (btnEl && !btnEl.getAttribute('data-pngm-ua-nav')) {
      btnEl.setAttribute('data-pngm-ua-nav', '1');
      btnEl.addEventListener('click', function (e) {
        if (!isMobileUa()) {
          return;
        }
        openUaNav(e);
      }, true);
    }

    $(document)
      .off('click.pngmUaNavClose')
      .on('click.pngmUaNavClose', '#menu-cover, .pngm-ua-nav-close', function () {
        closeUaNav();
        hideGlobalSideMenu();
      });

    $(document)
      .off('click.pngmUaMenuBtn')
      .on('click.pngmUaMenuBtn', '[data-pngm-ua-menu="1"]', function (e) {
        if (!isMobileUa()) {
          return;
        }
        openUaNav(e);
      });

    $(document)
      .off('keydown.pngmUaNav')
      .on('keydown.pngmUaNav', function (e) {
        if (e.key === 'Escape') {
          closeUaNav();
          $('#menu-cover').stop(true, true).fadeOut(200);
          hideGlobalSideMenu();
        }
      });

    $(window).on('resize.pngmUaNav', function () {
      if (!isMobileUa()) {
        closeUaNav();
      }
    });
  }

  function initPostingPlaceholders() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;

    function apply() {
      $('input[placeholder="+"]').attr('placeholder', '');
      $('input[name="sPhone"], input[name="contactPhone"], input[id^="atr_"]').filter(function () {
        var val = String($(this).val() || '');
        return /^\+\d*$/.test(val.trim());
      }).val('');

      $('label').each(function () {
        var text = $.trim($(this).text()).toLowerCase();
        if (text.indexOf('seat') !== -1) {
          $(this).closest('.row, .atr-row, li, div').find('input[type="text"], input[type="number"]').attr('placeholder', '123');
        }
        if (text.indexOf('phone') !== -1) {
          $(this).closest('.row, .atr-row, li, div').find('input').attr('placeholder', '');
        }
      });
    }

    apply();
    $(document).ajaxComplete(function () {
      apply();
    });
  }

  function initGeoLocate() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;

    window.epsGeoLocate = function (elem) {
      if (!elem || !elem.length) {
        return;
      }

      function show(name) {
        elem.find('span').hide(0);
        elem.find('span.' + name).show(0);
      }

      function lookup(lat, lng) {
        if (!window.baseAjaxUrl) {
          show('failed-unfound');
          return;
        }

        $.ajax({
          type: 'GET',
          dataType: 'json',
          url: window.baseAjaxUrl + '&ajaxFindCity=1&latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lng),
          success: function (data) {
            if (data && (data.success === true || data.success === 'true' || data.success === 1)) {
              elem.find('span').hide(0);
              elem.find('span.success').text(data.s_location || '').show(0);

              if (!elem.closest('.navigator-fill-selects').length) {
                elem.find('span.refresh').show(0);
                var link = elem.closest('a');
                link.attr('href', '#').addClass('completed');
                var alt = link.find('strong').attr('data-alt-text');
                if (alt) {
                  link.find('strong').text(alt);
                }
                if (typeof window.pngmLocAfterGeoSuccess === 'function') {
                  window.pngmLocAfterGeoSuccess(data);
                }
              } else if (typeof window.epsGeoToSelects === 'function') {
                window.epsGeoToSelects(elem, data);
              }

              return;
            }

            show('failed-unfound');
          },
          error: function () {
            show('failed-unfound');
          }
        });
      }

      function onPos(pos) {
        lookup(pos.coords.latitude, pos.coords.longitude);
      }

      function fail() {
        show('failed');
      }

      function onErr(err) {
        var code = err && err.code;
        if (!elem.data('pngm-geo-retry') && code !== 1) {
          elem.data('pngm-geo-retry', 1);
          navigator.geolocation.getCurrentPosition(onPos, fail, {
            enableHighAccuracy: false,
            timeout: 20000,
            maximumAge: 600000
          });
          return;
        }

        fail();
      }

      if (!navigator.geolocation) {
        show('not-supported');
        return;
      }

      show('loading');
      navigator.geolocation.getCurrentPosition(onPos, onErr, {
        enableHighAccuracy: false,
        timeout: 15000,
        maximumAge: 120000
      });
    };
  }

  function initSearchSubcats() {
    function scrollActiveIntoView() {
      document.querySelectorAll('.pngm-search-subcats-list').forEach(function (list) {
        var active = list.querySelector('.is-active');
        if (active && typeof active.scrollIntoView === 'function') {
          active.scrollIntoView({ inline: 'center', block: 'nearest' });
        }
      });
    }

    function syncSubcatActiveFromResponse($doc) {
      var $remote = $doc.find('#pngm-search-cat-strips');
      var $local = $('#pngm-search-cat-strips');

      // Fallback for older markup without the wrapper.
      if (!$remote.length) {
        $remote = $doc.find('#pngm-search-subcats');
      }
      if (!$local.length) {
        $local = $('#pngm-search-subcats');
      }

      // Root ↔ subcategory / nested strips can change shape; replace whole block.
      if ($remote.length) {
        if ($local.length) {
          $local.replaceWith($remote.first().clone());
        } else {
          var $anchor = $('.pngm-mobile-search-bar').first();
          if ($anchor.length) {
            $anchor.after($remote.first().clone());
          } else {
            var $board = $('#pngm-search-board');
            if ($board.length) {
              $board.before($remote.first().clone());
            } else {
              $('#search-main').prepend($remote.first().clone());
            }
          }
        }
      } else if ($local.length) {
        $local.remove();
      }

      scrollActiveIntoView();
    }

    function markClickedSubcat($link) {
      var $strip = $link.closest('.pngm-search-subcats');
      if (!$strip.length) {
        return;
      }
      $strip.find('a.pngm-search-subcat').removeClass('is-active');
      $link.addClass('is-active');
    }

    scrollActiveIntoView();

    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;

    /**
     * Replace only #pngm-search-board (count / sort / listings).
     * Keep subcategory strip + recently viewed mounted.
     */
    function pngmAjaxSearchBoard(elem, event) {
      if (typeof window.epsAjaxSearchTimeout !== 'undefined') {
        clearTimeout(window.epsAjaxSearchTimeout);
      }

      var $elem = $(elem);
      var delay = event && event.type === 'keyup' ? 200 : 50;
      if ($elem.closest('.atr-search, #atr-search').length && event && event.type === 'change') {
        // Let Attributes plugin write atr_* hidden + spawn child selects first.
        delay = 120;
      }
      var scrollToTop = false;
      var ajaxStop = false;
      var ajaxSearchUrl = '';
      var sidebarReload = true;

      var sidebar;
      if ($elem.closest('form.search-side-form').length) {
        sidebar = $elem.closest('form.search-side-form').last();
      } else {
        sidebar = $('form.search-side-form').last();
      }

      if ($elem.closest('li.first-child').length && $elem.attr('href') === window.baseDir) {
        window.location.href = $elem.attr('href');
        return false;
      }

      if ($(event.target).attr('name') === 'sLocation') {
        return false;
      }

      if (
        $elem.closest('.sidebar-hooks').length ||
        $elem.closest('.input-box-check').length ||
        ($elem.closest('.search-side-form').length && $elem.attr('name') !== 'sCategory') ||
        (event && event.type === 'keyup')
      ) {
        sidebarReload = false;
      }

      if ($elem.closest('.cap-input-box').length) {
        sidebarReload = false;
      }

      if ($elem.closest('.paginate').length || $elem.closest('#latest-search').length) {
        scrollToTop = true;
      }

      var rebuildFromForm =
        (event && (event.type === 'change' || event.type === 'keyup')) || $elem.is('input:radio');

      if (event && event.type === 'click' && !$elem.is('input:radio')) {
        if (typeof $elem.attr('href') !== 'undefined' && $elem.attr('href') !== false && $elem.attr('href') !== '') {
          ajaxSearchUrl = $elem.attr('href');
        }
        rebuildFromForm = false;
      } else if ($elem.hasClass('orderSelect')) {
        ajaxSearchUrl = $elem.find(':selected').attr('data-link');
        rebuildFromForm = false;
      }

      window.epsAjaxSearchTimeout = setTimeout(function () {
        // Attributes cascade selects have no name=; they write into a hidden atr_* input.
        // Sync after the plugin's own change handler so Make / Brand actually filters.
        if (typeof window.pngmSyncAtrSearchValues === 'function') {
          window.pngmSyncAtrSearchValues(sidebar);
        } else {
          sidebar.find('.atr-search .controls, #atr-search .controls').each(function () {
            var $block = $(this);
            var $hidden = $block.find('input[type="hidden"][name^="atr_"]').first();
            if (!$hidden.length) {
              return;
            }
            var $filled = $block.find('select[data-atr-id]').filter(function () {
              return $.trim($(this).val() || '').length > 0;
            }).last();
            $hidden.val($filled.length ? $filled.val() : '');
          });
        }

        if (rebuildFromForm) {
          ajaxSearchUrl =
            window.baseDir +
            'index.php?' +
            sidebar
              .find(':input')
              .filter(function () {
                return $.trim(this.value).length > 0;
              })
              .serialize();
        }

        if (
          String(window.ajaxSearch) !== '1' ||
          $('input.ajaxRun').val() === '1' ||
          !ajaxSearchUrl ||
          ajaxSearchUrl === '#' ||
          ajaxStop === true
        ) {
          return false;
        }

        if (ajaxSearchUrl === $(location).attr('href')) {
          return false;
        }

        var $board = $('#pngm-search-board');
        if (!$board.length) {
          if (typeof window.pngmEpsAjaxSearchOriginal === 'function') {
            return window.pngmEpsAjaxSearchOriginal($elem, event);
          }
          return false;
        }

        markClickedSubcat($elem);

        sidebar.find('.init-search').addClass('loading').addClass('disabled').attr('disabled', true);
        sidebar.find('input.ajaxRun').val(1);
        $('#search-main').removeClass('loading');

        var $loader = $board.children('.pngm-board-loader');
        if (!$loader.length) {
          $loader = $(
            '<div class="pngm-board-loader" role="status" aria-live="polite" aria-busy="true">' +
              '<div class="pngm-board-loader-card">' +
                '<span class="pngm-board-loader-spinner" aria-hidden="true"></span>' +
                '<p class="pngm-board-loader-text">Loading listings…</p>' +
                '<p class="pngm-board-loader-hint">Updating results</p>' +
              '</div>' +
            '</div>'
          );
          $board.prepend($loader);
        }
        $board.addClass('loading');
        $board.find('.ajax-load-failed').hide(0);

        $.ajax({
          url: ajaxSearchUrl,
          type: 'GET',
          timeout: 10000,
          success: function (response) {
            var $doc = $('<div>').append($.parseHTML(response, document, true));
            var boardHtml = $doc.find('#pngm-search-board').html();
            var bread = $doc.find('ul.breadcrumb').html();
            var sideForm = $doc.find('.filter-menu > .wrap > form').html();
            var sideCat = $doc.find('.filter-menu > #search-category-box').html();

            sidebar.find('.init-search').removeClass('loading').removeClass('disabled').attr('disabled', false);
            sidebar.find('input.ajaxRun').val('');

            if (typeof boardHtml !== 'undefined' && boardHtml !== null) {
              $board.removeClass('loading').html(boardHtml);
            } else {
              // Fallback if older markup without board wrapper
              var mainHtml = $doc.find('#search-main').html();
              $('#search-main').html(mainHtml);
            }

            syncSubcatActiveFromResponse($doc);

            if (sidebarReload) {
              $('.filter-menu > .wrap > form').html(sideForm);
              $('.filter-menu > #search-category-box').html(sideCat);
            }

            if (bread) {
              $('ul.breadcrumb').html(bread);
            }

            if (typeof window.pngmSyncFilterDrawerFromPage === 'function') {
              window.pngmSyncFilterDrawerFromPage();
            }

            var mobilePh = $doc.find('#sPattern').attr('placeholder');
            if (mobilePh) {
              $('.pngm-mobile-pattern').attr('placeholder', mobilePh);
            }

            if (typeof window.epsLazyLoadImages === 'function') {
              window.epsLazyLoadImages('search-items');
              window.epsLazyLoadImages('search-premium-items');
            }
            if (typeof window.epsManageScroll === 'function') {
              window.epsManageScroll();
            }
            if (typeof window.epsShowUsefulScrollButtons === 'function') {
              window.epsShowUsefulScrollButtons();
            }

            window.history.pushState(null, null, ajaxSearchUrl);

            if (scrollToTop) {
              var $quick = $('#search-quick-bar');
              if ($quick.length) {
                $(window).scrollTop($quick.offset().top - parseInt($('header').height() || 0, 10) - 12);
              }
            }
          },
          error: function () {
            sidebar.find('.init-search').removeClass('loading').removeClass('disabled').attr('disabled', false);
            sidebar.find('input.ajaxRun').val('');
            $board.removeClass('loading');
            $board.find('.ajax-load-failed').show(0);
          }
        });

        if (!$elem.is('input:radio')) {
          return false;
        }
      }, delay);
    }

    // Prefer board-only AJAX whenever the board wrapper exists.
    if (typeof window.epsAjaxSearch === 'function' && !window.pngmEpsAjaxSearchOriginal) {
      window.pngmEpsAjaxSearchOriginal = window.epsAjaxSearch;
      window.epsAjaxSearch = function (elem, event) {
        if ($('#pngm-search-board').length) {
          return pngmAjaxSearchBoard(elem, event);
        }
        return window.pngmEpsAjaxSearchOriginal(elem, event);
      };
    }

    $('body#search').off('click.pngmSubcatAjax').on(
      'click.pngmSubcatAjax',
      '#pngm-search-cat-strips a.pngm-search-subcat, #pngm-search-subcats a.pngm-search-subcat, a[data-pngm-ajax-search="1"]',
      function (event) {
        var href = $(this).attr('href');
        if (!href || href === '#' || href.indexOf('javascript:') === 0) {
          return;
        }

        if (String(window.ajaxSearch) !== '1' || typeof window.epsAjaxSearch !== 'function') {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        window.epsAjaxSearch($(this), event);
        return false;
      }
    );

    $(document).ajaxComplete(function () {
      window.setTimeout(scrollActiveIntoView, 40);
    });
  }

  function initItemPostMinlength() {
    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.validate !== 'function') {
      return;
    }

    var $ = window.jQuery;
    var form = $('form[name="item"]');

    if (!form.length) {
      return;
    }

    function apply() {
      if (!form.data('validator')) {
        return false;
      }

      form.find('input[name^="title["]').each(function () {
        $(this).rules('add', {
          minlength: 3,
          messages: {
            minlength: 'Title: enter at least 3 characters.'
          }
        });
      });

      form.find('textarea[name^="description["]').each(function () {
        $(this).rules('remove', 'minlength');
      });

      return true;
    }

    if (!apply()) {
      setTimeout(apply, 50);
      setTimeout(apply, 300);
    }
  }

  function initItemPostValidation() {
    if (!window.pngmItemValidation) {
      return;
    }

    window.pngmItemValidation.init();

    var attempts = 0;
    var timer = window.setInterval(function () {
      attempts += 1;
      window.pngmItemValidation.forceEnhanceValidator();

      if (attempts >= 40) {
        window.clearInterval(timer);
      }
    }, 250);
  }

  function initPostPhotoPreview() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;

    if (!$('.upload-photos, #photos').length) {
      return;
    }

    var viewer = $('<div class="pngm-photo-viewer" hidden role="dialog" aria-modal="true" aria-label="Photo">' +
      '<div class="pngm-photo-viewer-bar"><button type="button" class="pngm-photo-viewer-close" aria-label="Close">&times;</button></div>' +
      '<div class="pngm-photo-viewer-stage"><img alt=""></div>' +
      '</div>');

    $('body').append(viewer);

    var stage = viewer.find('.pngm-photo-viewer-stage')[0];
    var img = viewer.find('img')[0];
    var scale = 1;
    var tx = 0;
    var ty = 0;
    var startScale = 1;
    var startDist = 0;
    var panX = 0;
    var panY = 0;
    var lastX = 0;
    var lastY = 0;
    var panning = false;
    var lastTap = 0;

    function applyTransform() {
      img.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(' + scale + ')';
    }

    function resetTransform() {
      scale = 1;
      tx = 0;
      ty = 0;
      applyTransform();
    }

    function distance(a, b) {
      var dx = a.clientX - b.clientX;
      var dy = a.clientY - b.clientY;
      return Math.sqrt(dx * dx + dy * dy);
    }

    function fullSrc(el) {
      if (!el) {
        return '';
      }
      var src = el.getAttribute('src') || el.getAttribute('data-src') || '';
      return src.replace('_thumbnail.', '.').replace('_preview.', '.');
    }

    function open(src) {
      if (!src) {
        return;
      }
      img.src = src;
      resetTransform();
      viewer.removeAttr('hidden');
      document.body.style.overflow = 'hidden';
      document.body.classList.add('pngm-photo-viewer-open');
    }

    window.pngmOpenPhotoViewer = open;

    function close() {
      viewer.attr('hidden', 'hidden');
      img.removeAttribute('src');
      resetTransform();
      document.body.style.overflow = '';
      document.body.classList.remove('pngm-photo-viewer-open');
    }

    function ensureViewButtons(scope) {
      var $scope = scope ? $(scope) : $(document);
      $scope.find('.upload-photos .qq-upload-list li, #photos .qq-upload-list li, #uppy-gallery li').each(function () {
        var $li = $(this);
        if (!$li.find('img').length) {
          return;
        }
        var $preview = $li.find('.ajax_preview_img').first();
        if (!$preview.length) {
          $preview = $li;
        }
        if ($li.find('.pngm-photo-view').length) {
          return;
        }
        $preview.append(
          '<button type="button" class="pngm-photo-view" title="View photo" aria-label="View photo">' +
            '<i class="far fa-eye" aria-hidden="true"></i>' +
          '</button>'
        );
      });
    }

    viewer.find('.pngm-photo-viewer-close').on('click', function (e) {
      e.preventDefault();
      close();
    });

    viewer.on('click', function (e) {
      if (e.target === viewer[0] || e.target === stage) {
        close();
      }
    });

    $(document).on('keydown.pngmPhoto', function (e) {
      if ((e.key === 'Escape' || e.keyCode === 27) && !viewer.is('[hidden]')) {
        close();
      }
    });

    stage.addEventListener('touchstart', function (e) {
      if (e.touches.length === 2) {
        startDist = distance(e.touches[0], e.touches[1]);
        startScale = scale;
        panning = false;
      } else if (e.touches.length === 1 && scale > 1.05) {
        panning = true;
        lastX = e.touches[0].clientX;
        lastY = e.touches[0].clientY;
        panX = tx;
        panY = ty;
      } else {
        panning = false;
      }
    }, { passive: true });

    stage.addEventListener('touchend', function (e) {
      if (e.touches.length === 0 && e.changedTouches && e.changedTouches.length === 1 && !panning) {
        var now = Date.now();
        if (now - lastTap < 320) {
          if (scale > 1.05) {
            resetTransform();
          } else {
            scale = 2.5;
            applyTransform();
          }
          lastTap = 0;
        } else {
          lastTap = now;
        }
      }
    }, { passive: true });

    stage.addEventListener('touchmove', function (e) {
      if (e.touches.length === 2 && startDist > 0) {
        e.preventDefault();
        scale = Math.min(5, Math.max(1, startScale * (distance(e.touches[0], e.touches[1]) / startDist)));
        if (scale <= 1.02) {
          scale = 1;
          tx = 0;
          ty = 0;
        }
        applyTransform();
      } else if (e.touches.length === 1 && panning) {
        e.preventDefault();
        tx = panX + (e.touches[0].clientX - lastX);
        ty = panY + (e.touches[0].clientY - lastY);
        applyTransform();
      }
    }, { passive: false });

    stage.addEventListener('wheel', function (e) {
      e.preventDefault();
      scale = Math.min(5, Math.max(1, scale + (e.deltaY < 0 ? 0.2 : -0.2)));
      if (scale === 1) {
        tx = 0;
        ty = 0;
      }
      applyTransform();
    }, { passive: false });

    $(document).on('click.pngmPhotoPreview', '.pngm-photo-view', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $host = $(this).closest('li, #pngm-review-cover, #pngm-public-cover, .pngm-post-review-thumb');
      var el = $host.find('img').get(0) || $(this).siblings('img').get(0);
      open(fullSrc(el));
    });

    $(document).on('click.pngmPhotoPreview', '.upload-photos .ajax_preview_img img, #photos .ajax_preview_img img', function (e) {
      if ($(e.target).closest('.qq-upload-delete, .qq-upload-rotate, .qq-upload-move, .qq-upload-rotate-img, .pngm-photo-view').length) {
        return;
      }

      e.preventDefault();
      e.stopPropagation();
      open(fullSrc(this));
    });

    $(document).on('click.pngmPhotoPreview', '#pngm-review-cover img, #pngm-public-cover img', function (e) {
      if ($(e.target).closest('.pngm-photo-view, .pngm-post-review-badge').length) {
        return;
      }
      e.preventDefault();
      e.stopPropagation();
      open(fullSrc(this));
    });

    ensureViewButtons();

    if (window.MutationObserver) {
      var listRoot = document.querySelector('#photos .qq-upload-list, #uppy-gallery, .upload-photos .qq-upload-list');
      if (listRoot) {
        var obs = new MutationObserver(function () {
          ensureViewButtons(listRoot);
        });
        obs.observe(listRoot, { childList: true, subtree: true });
      }
      var reviewRoot = document.getElementById('pngm-review-cover');
      if (reviewRoot) {
        var reviewObs = new MutationObserver(function () {
          ensureReviewViewButton();
        });
        reviewObs.observe(reviewRoot, { childList: true, subtree: true });
      }
    }

    function ensureReviewViewButton() {
      var $cover = $('#pngm-review-cover');
      if (!$cover.length || !$cover.find('img').length) {
        return;
      }
      if ($cover.find('.pngm-photo-view').length) {
        return;
      }
      $cover.append(
        '<button type="button" class="pngm-photo-view" title="View photo" aria-label="View photo">' +
          '<i class="far fa-eye" aria-hidden="true"></i>' +
        '</button>'
      );
    }

    ensureReviewViewButton();

    document.addEventListener('error', function (e) {
      var el = e.target;
      if (!el || el.tagName !== 'IMG' || !el.closest) {
        return;
      }
      if (!el.closest('.upload-photos .ajax_preview_img, #photos .ajax_preview_img')) {
        return;
      }
      if (el.getAttribute('data-pngm-src-tried')) {
        return;
      }
      var src = el.getAttribute('src') || '';
      if (src.indexOf('/uploads/temp/auto_') === -1) {
        return;
      }
      el.setAttribute('data-pngm-src-tried', '1');
      el.src = src.replace('/uploads/temp/auto_', '/uploads/temp/');
    }, true);
  }

  function initUppyOverNav() {
    function sync() {
      var modal = document.querySelector('.uppy-Dashboard--modal');
      var open = false;

      if (modal) {
        var hidden = modal.getAttribute('aria-hidden');
        open = hidden !== 'true';
      }

      document.body.classList.toggle('pngm-uppy-open', open);
    }

    document.addEventListener('click', function () {
      setTimeout(sync, 50);
    });

    if (window.MutationObserver) {
      var obs = new MutationObserver(sync);
      obs.observe(document.body, {
        subtree: true,
        attributes: true,
        attributeFilter: ['aria-hidden']
      });
    }

    sync();
  }

  /**
   * Live-refresh search board on keyword / price / filter changes (no Search button).
   * Syncs the header search bar with the sidebar form on the search page.
   */
  function initLiveSearchBoard() {
    if (typeof window.jQuery === 'undefined' || !document.body || document.body.id !== 'search') {
      return;
    }

    var $ = window.jQuery;

    function triggerAjax($elem, event) {
      if (typeof window.epsAjaxSearch !== 'function') {
        return;
      }
      if (String(window.ajaxSearch) !== '1') {
        return;
      }
      window.epsAjaxSearch($elem, event || $.Event('keyup'));
    }

    // Price range: refresh as the user types (parent only binds change).
    $('body#search').on(
      'keyup input',
      'form.search-side-form input[name="sPriceMin"], form.search-side-form input[name="sPriceMax"]',
      function (event) {
        if ($(this).closest('#side-menu .box.filter').length) {
          return;
        }
        triggerAjax($(this), event);
      }
    );

    // Header keyword → sidebar pattern → AJAX results (no suggestion dropdown).
    $('body#search').on('keyup input', '.pngm-global-search input.pattern', function (event) {
      var val = $(this).val();
      var $side = sidePatternInput();
      if ($side.length) {
        $side.val(val);
        triggerAjax($side, event);
      }
      $('.pngm-mobile-pattern').val(val);
      syncMobileClear(val);
    });

    function sidePatternInput() {
      var $side = $('form.search-side-form').not('#side-menu form').first().find('input[name="sPattern"]');
      if (!$side.length) {
        $side = $('.filter-menu form.search-side-form').first().find('input[name="sPattern"]');
      }
      if (!$side.length) {
        $side = $('#side-menu form.search-side-form input[name="sPattern"]').first();
      }
      return $side;
    }

    function syncMobileClear(val) {
      var $clear = $('.pngm-mobile-search-clear');
      if (!$clear.length) {
        return;
      }
      if ($.trim(val || '').length) {
        $clear.prop('hidden', false).removeAttr('hidden');
      } else {
        $clear.prop('hidden', true).attr('hidden', 'hidden');
      }
    }

    // Mobile keyword bar (filters drawer is separate on small screens).
    $('body#search').on('keyup input', '.pngm-mobile-pattern', function (event) {
      var val = $(this).val();
      var $side = sidePatternInput();
      syncMobileClear(val);
      if ($side.length) {
        $side.val(val);
        triggerAjax($side, event);
      }
      $('.pngm-global-search input.pattern').val(val);
    });

    $('body#search').on('keydown', '.pngm-mobile-pattern', function (event) {
      if (event.key === 'Enter' || event.keyCode === 13) {
        event.preventDefault();
        var val = $(this).val();
        var $side = sidePatternInput();
        if ($side.length) {
          $side.val(val);
          triggerAjax($side, $.Event('keyup'));
        }
      }
    });

    $('body#search').on('click', '.pngm-mobile-search-clear', function (event) {
      event.preventDefault();
      var $input = $('.pngm-mobile-pattern');
      $input.val('');
      syncMobileClear('');
      var $side = sidePatternInput();
      if ($side.length) {
        $side.val('');
        triggerAjax($side, $.Event('keyup'));
      }
      $('.pngm-global-search input.pattern').val('');
      $input.trigger('focus');
    });

    $('body#search').on('submit', '.global-search-form', function (e) {
      e.preventDefault();
      var $input = $(this).find('input.pattern');
      var $side = sidePatternInput();
      if ($side.length) {
        $side.val($input.val());
        triggerAjax($side, $.Event('keyup'));
      }
      $('.pngm-mobile-pattern').val($input.val());
      syncMobileClear($input.val());
    });

    // Keep header / mobile keyword in sync after AJAX board refresh.
    $('body#search').on('keyup input', 'form.search-side-form input[name="sPattern"]', function () {
      if ($(this).closest('#side-menu .box.filter').length) {
        return;
      }
      var val = $(this).val();
      $('.pngm-global-search input.pattern').val(val);
      $('.pngm-mobile-pattern').val(val);
      syncMobileClear(val);
    });

    syncMobileClear($('.pngm-mobile-pattern').val() || '');

    function clearSearchLocation($form) {
      if (!$form || !$form.length) {
        return;
      }
      $form.find('input[name="sCity"], input[name="sRegion"], input[name="sCountry"]').val('');
      $form.find('input[name="sLocation"]').val('');
      $form.find('.picker.location .results').hide(0);
      $form.find('.picker.location .clean').hide(0);

      var $city = $form.find('input[name="sCity"]').first();
      if ($city.length) {
        // Parent ajax ignores sLocation keyups; trigger via sCity change instead.
        triggerAjax($city, $.Event('change'));
      }
    }

    // X clear on location picker → drop city/region/country and refresh board.
    $('body#search').on('click', 'form.search-side-form .picker.location .clean', function (e) {
      e.preventDefault();
      e.stopImmediatePropagation();
      clearSearchLocation($(this).closest('form.search-side-form'));
    });

    // Deleting all location text → same refresh once hidden loc fields were set.
    $('body#search').on('keyup input', 'form.search-side-form input[name="sLocation"]', function () {
      if ($(this).closest('#side-menu .box.filter').length) {
        return;
      }
      if ($.trim($(this).val() || '') !== '') {
        return;
      }
      var $form = $(this).closest('form.search-side-form');
      var hadLoc =
        $.trim($form.find('input[name="sCity"]').val() || '') !== '' ||
        $.trim($form.find('input[name="sRegion"]').val() || '') !== '' ||
        $.trim($form.find('input[name="sCountry"]').val() || '') !== '';
      if (hadLoc) {
        clearSearchLocation($form);
      }
    });
  }

  /**
   * Mobile filter drawer: keep typing stable, pin the Filter button,
   * and apply filters on submit instead of live AJAX while the panel is open.
   */
  function initMobileSearchFilters() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;
    var originalAjax = window.epsAjaxSearch;

    if (typeof originalAjax === 'function' && !window.pngmAjaxSearchWrapped) {
      window.pngmAjaxSearchWrapped = true;
      window.epsAjaxSearch = function (elem, event) {
        var $elem = window.jQuery(elem);
        var inDrawer = $elem.closest('#side-menu .box.filter').length > 0;

        // While the mobile filter drawer is open, don't live-refresh on every
        // keystroke/change — Apply ("Show results") submits instead.
        // Exception: Category must refresh so Make / Brand (and other hooks) appear.
        if (inDrawer && event && event.type !== 'click') {
          var name = $elem.attr('name') || '';
          if (name !== 'sCategory') {
            return;
          }
        }

        return originalAjax.apply(this, arguments);
      };
    }

    function uniqueIds($root) {
      $root.find('[id]').each(function () {
        var id = this.id;
        if (!id || id.indexOf('pngm-f-') === 0) {
          return;
        }
        var next = 'pngm-f-' + id;
        this.id = next;
        $root.find('label[for="' + id + '"]').attr('for', next);
      });
    }

    function layoutFilterDrawer($panel) {
      var $section = $panel.find('.section.filter-menu, .section').first();
      var $form = $section.find('form.search-side-form');
      if (!$form.length || $form.hasClass('pngm-filter-laid-out')) {
        return;
      }

      var $btn = $form.find('.row.buttons.srch');
      var $cat = $section.find('#search-category-box, [id$="search-category-box"]');
      var $scroll = $('<div class="pngm-filter-scroll"></div>');

      $form.children().not($btn).appendTo($scroll);
      if ($cat.length) {
        $cat.remove();
      }
      $section.find('#search-category-box, [id$="search-category-box"]').remove();
      $form.prepend($scroll);
      if ($btn.length) {
        $form.append($btn);
      }
      $form.addClass('pngm-filter-laid-out');
      $form.find('input[name="sPattern"]').addClass('pattern');
      $form.find('.picker .results').each(function () {
        if (!$.trim($(this).html() || '')) {
          $(this).hide();
        }
      });
    }

    /** Re-clone #search-menu into the open drawer after category/AJAX refresh. */
    function syncFilterDrawerFromPage() {
      if (!$('body').hasClass('pngm-filter-open')) {
        return;
      }

      var $panel = $('#side-menu .box.filter');
      var $section = $panel.find('.section.filter-menu, .section').first();
      var $src = $('#search-menu.filter-menu');
      if (!$panel.length || !$section.length || !$src.length) {
        return;
      }

      $section.html($src.html());
      uniqueIds($section);
      $panel.find('input[name="sPattern"]').attr('autocomplete', 'off');
      layoutFilterDrawer($panel);

      var $scroll = $panel.find('.pngm-filter-scroll');
      if ($scroll.length) {
        $scroll.scrollTop($scroll[0].scrollHeight);
      }
    }

    // Expose so board AJAX can refresh Make / Brand hooks in the open drawer.
    window.pngmSyncFilterDrawerFromPage = syncFilterDrawerFromPage;

    $('body').on('click', '#open-search-filters, .action.open-filters', function () {
      $('body').addClass('pngm-filter-open');
      setTimeout(function () {
        var $panel = $('#side-menu .box.filter');
        uniqueIds($panel.find('.section'));
        $panel.find('input[name="sPattern"]').attr('autocomplete', 'off');
        layoutFilterDrawer($panel);
      }, 40);
    });

    $('body').on('click', '#side-menu .box.filter .back, #menu-cover', function () {
      $('body').removeClass('pngm-filter-open');
    });
  }

  function initChatLayout() {
    var form = document.getElementById('im-message-form');
    var board = document.querySelector('.im-table.im-messages');
    var pinTimers = [];

    if (!form && !document.querySelector('.im-file-messages')) {
      return;
    }

    document.body.classList.add('im-chat-page');

    function clearPinTimers() {
      var i;
      for (i = 0; i < pinTimers.length; i += 1) {
        window.clearTimeout(pinTimers[i]);
      }
      pinTimers = [];
    }

    function scrollBoardToBottom(el) {
      if (!el) {
        return;
      }
      // Force layout so scrollHeight is current after flex/height changes.
      void el.offsetHeight;
      el.scrollTop = el.scrollHeight;
      var last = el.querySelector('.im-table-row:not(.hidden):last-of-type, .im-table-row:last-child');
      if (last && typeof last.scrollIntoView === 'function') {
        try {
          last.scrollIntoView({ block: 'end', inline: 'nearest' });
        } catch (errView) {
          try { last.scrollIntoView(false); } catch (errView2) {}
        }
      }
      el.scrollTop = el.scrollHeight;
    }

    /**
     * Pin chat to latest message. Retries after layout settles (flex height,
     * images, AJAX board swap) — a single scrollTop often runs too early.
     */
    function pinChatBottom(opts) {
      var immediate = !(opts && opts.deferredOnly);
      var delays = (opts && opts.delays) ? opts.delays : [0, 50, 150, 350, 700];

      function run() {
        board = document.querySelector('.im-table.im-messages');
        if (!board) {
          return;
        }
        scrollBoardToBottom(board);
      }

      clearPinTimers();
      if (immediate) {
        run();
        if (typeof window.requestAnimationFrame === 'function') {
          window.requestAnimationFrame(function () {
            run();
            window.requestAnimationFrame(run);
          });
        }
      }
      var d;
      for (d = 0; d < delays.length; d += 1) {
        (function (ms) {
          pinTimers.push(window.setTimeout(run, ms));
        })(delays[d]);
      }

      // Re-pin once when bubbles' images finish loading (avatars / attachments).
      board = document.querySelector('.im-table.im-messages');
      if (board && !board.getAttribute('data-pngm-pin-imgs')) {
        board.setAttribute('data-pngm-pin-imgs', '1');
        board.addEventListener('load', function (e) {
          if (!(e.target && e.target.tagName === 'IMG')) {
            return;
          }
          var el = document.querySelector('.im-table.im-messages');
          if (!el) {
            return;
          }
          var near = (el.scrollHeight - el.scrollTop - el.clientHeight) < 120;
          // Only follow images if user is already near the latest messages.
          if (near) {
            scrollBoardToBottom(el);
          }
        }, true);
      }
    }

    window.pngmPinChatBottom = pinChatBottom;

    function layout(opts) {
      form = document.getElementById('im-message-form');
      board = document.querySelector('.im-table.im-messages');
      if (!board) {
        return;
      }

      var pinBottom = !!(opts && opts.pinBottom);
      var nearBottom = (board.scrollHeight - board.scrollTop - board.clientHeight) < 80;

      // Desktop: match list+board height to sidebar (no empty stretch below Logout).
      if (window.innerWidth > 767) {
        var menu = document.getElementById('user-menu');
        var main = document.getElementById('user-main');
        var shell = document.querySelector('.container.primary.pngm-ua-shell, .container.primary');
        if (menu && main) {
          // Temporarily clear so sidebar reports its natural content height.
          if (shell) {
            shell.style.removeProperty('--pngm-im-shell-h');
          }
          main.style.height = '';
          main.style.maxHeight = '';
          var sideH = Math.ceil(menu.getBoundingClientRect().height);
          if (sideH > 120) {
            var hPx = sideH + 'px';
            if (shell) {
              shell.style.setProperty('--pngm-im-shell-h', hPx);
            }
            main.style.height = hPx;
            main.style.maxHeight = hPx;
          }
        }
        board.style.maxHeight = '';
        board.style.height = '';
        board.style.overflowY = '';
        if (pinBottom || nearBottom) {
          pinChatBottom({ delays: pinBottom ? [0, 50, 150, 350, 700] : [0, 50] });
        }
        return;
      }

      // Mobile: give the message list an explicit height so it scrolls, and keep
      // the composer dock (tip + input) pinned below it above the bottom nav.
      if (form) {
        form.style.display = 'flex';
        form.style.visibility = 'visible';
        form.style.flex = '0 0 auto';
        form.style.flexShrink = '0';
        form.style.height = 'auto';
        form.style.maxHeight = 'none';
        form.style.overflow = 'visible';
      }

      var dock = document.querySelector('.pngm-composer-dock');
      var tip = document.getElementById('pngm-composer-tip');
      if (dock) {
        dock.style.display = 'flex';
        dock.style.flexDirection = 'column';
        dock.style.flex = '0 0 auto';
        dock.style.overflow = 'visible';
      }
      if (tip) {
        tip.style.setProperty('display', 'block', 'important');
        tip.style.setProperty('visibility', 'visible', 'important');
        tip.style.setProperty('opacity', '1', 'important');
        tip.style.setProperty('color', '#6b7785', 'important');
        tip.style.setProperty('-webkit-text-fill-color', '#6b7785', 'important');
        tip.style.setProperty('background', 'transparent', 'important');
        tip.style.setProperty('font-size', '12px', 'important');
        tip.style.setProperty('min-height', '0', 'important');
      }

      var navi = document.getElementById('navi-bar');
      var naviH = 0;
      if (navi && window.getComputedStyle(navi).display !== 'none') {
        naviH = navi.offsetHeight;
      }

      // Reserve the whole dock (tip above input + form), not just the form.
      var formH = 0;
      if (dock) {
        formH = dock.offsetHeight;
      } else if (form) {
        formH = form.offsetHeight;
        if (tip) {
          formH += tip.offsetHeight;
        }
      }
      if (formH < 110) {
        formH = 110;
      }

      var top = board.getBoundingClientRect().top;
      var available = Math.floor(window.innerHeight - top - formH - naviH - 12);
      if (available < 140) {
        available = 140;
      }

      board.style.flex = '1 1 auto';
      board.style.minHeight = '120px';
      board.style.height = available + 'px';
      board.style.maxHeight = available + 'px';
      board.style.overflowY = 'auto';

      if (pinBottom || nearBottom) {
        pinChatBottom({ delays: pinBottom ? [0, 50, 150, 350, 700] : [0, 50] });
      }
    }

    window.pngmLayoutChat = layout;
    layout({ pinBottom: true });
    window.addEventListener('resize', function () {
      layout();
    });
    window.addEventListener('orientationchange', function () {
      layout({ pinBottom: true });
    });
    pinChatBottom({ delays: [0, 50, 150, 350, 700] });

    // Plugin autosize uses a 50–85px floor, so one keystroke already grows the field
    // and can push the send-hint under the overflow clip on mobile.
    (function initComposerAutosize() {
      var BASE = 44;
      var MAX = 120;

      function fit() {
        var ta = document.getElementById('im-message');
        if (!ta) {
          return;
        }
        ta.style.height = BASE + 'px';
        ta.style.overflowY = 'hidden';
        var needed = Math.max(BASE, Math.min(MAX, ta.scrollHeight));
        ta.style.height = needed + 'px';
        ta.style.overflowY = needed >= MAX ? 'auto' : 'hidden';
        if (typeof window.pngmLayoutChat === 'function') {
          window.pngmLayoutChat();
        }
      }

      window.imResetComposerHeight = function () {
        var ta = document.getElementById('im-message');
        if (!ta) {
          return;
        }
        ta.style.height = BASE + 'px';
        ta.style.overflowY = 'hidden';
        if (typeof window.pngmLayoutChat === 'function') {
          window.pngmLayoutChat();
        }
      };

      // Beat the plugin's taller min-height handler.
      if (window.jQuery) {
        window.jQuery('body')
          .off('change keyup keydown paste cut input', 'textarea#im-message')
          .on('change.pngmImFit keyup.pngmImFit keydown.pngmImFit paste.pngmImFit cut.pngmImFit input.pngmImFit', 'textarea#im-message', fit);
      }
      fit();
    })();
  }

  /**
   * Listing description: clamp to 5 lines and only show "Show more" when overflowing.
   */
  function initItemDescriptionClamp() {
    var wraps = document.querySelectorAll('body#item .pngm-desc');
    if (!wraps.length) {
      return;
    }

    function measure(wrap) {
      var body = wrap.querySelector('.pngm-desc-body');
      var more = wrap.querySelector('.pngm-desc-more');
      if (!body || !more) {
        return;
      }

      if (wrap.classList.contains('is-expanded')) {
        more.hidden = true;
        wrap.classList.remove('is-collapsed');
        return;
      }

      var maxLines = parseInt(wrap.getAttribute('data-max-lines') || '5', 10) || 5;
      wrap.classList.remove('is-collapsed');
      more.hidden = true;

      // Force layout with full text, then compare against 5-line cap
      var styles = window.getComputedStyle(body);
      var lineHeight = parseFloat(styles.lineHeight);
      if (!lineHeight || isNaN(lineHeight)) {
        lineHeight = parseFloat(styles.fontSize) * 1.4;
      }
      var maxHeight = lineHeight * maxLines;
      var fullHeight = body.scrollHeight;

      if (fullHeight > maxHeight + 2) {
        wrap.classList.add('is-collapsed');
        more.hidden = false;
      } else {
        wrap.classList.remove('is-collapsed');
        more.hidden = true;
      }
    }

    function measureAll() {
      var i;
      for (i = 0; i < wraps.length; i += 1) {
        measure(wraps[i]);
      }
    }

    measureAll();
    window.setTimeout(measureAll, 50);
    window.setTimeout(measureAll, 250);
    window.addEventListener('resize', measureAll);

    document.body.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('a.pngm-show-more-desc') : null;
      if (!link) {
        return;
      }
      e.preventDefault();
      var wrap = link.closest('.pngm-desc');
      if (!wrap) {
        return;
      }
      wrap.classList.remove('is-collapsed');
      wrap.classList.add('is-expanded');
      var more = wrap.querySelector('.pngm-desc-more');
      if (more) {
        more.hidden = true;
      }
    });
  }

  function initLocationModalUx() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }
    var $ = window.jQuery;
    var locOpenSel = 'header .links .btn.location, #navi-bar a.location';

    function pngmLocToast(message, isError) {
      pngmShowToast(message, isError);
    }

    function pngmCloseLocationModals() {
      $('.modal-box.location-select').each(function () {
        var id = $(this).attr('data-modal-id');
        if (id && typeof epsModalClose === 'function') {
          epsModalClose(id);
        } else {
          $(this).remove();
          $('.modal-cover[data-modal-id="' + id + '"]').remove();
        }
      });
      $('body').removeClass('pngm-loc-modal-open').css('overflow', '');
    }

    function pngmHashFromHref(href) {
      if (!href) {
        return '';
      }
      try {
        var url = new URL(href, window.location.origin);
        return url.searchParams.get('hash') || '';
      } catch (err) {
        var m = String(href).match(/[?&]hash=([^&]+)/);
        return m ? decodeURIComponent(m[1]) : '';
      }
    }

    function pngmApplyLocationUi(data) {
      var cleared = !!(data && data.cleared);
      var label = (data && (data.label || data.s_name)) || 'Location';
      var full = (data && data.s_location) || label;

      var $btn = $('header .links .btn.location');
      $btn.toggleClass('active', !cleared);
      $btn.attr('title', cleared ? 'Location' : label);
      $btn.find('.pngm-loc-label').text(cleared ? 'Location' : label);

      $('.pngm-near-city').text(cleared ? '' : label);
      // Near You: only show "Set location" when empty — never a "Change" link beside the city.
      var $nearChange = $('.pngm-near-meta .change-location, .pngm-near-meta .pngm-change-link');
      if (cleared) {
        if ($nearChange.length) {
          $nearChange.text('Set location').show();
        }
      } else {
        $nearChange.remove();
      }

      var $cards = $('#def-location .pngm-loc-current, #side-menu .box.location .pngm-loc-current');
      $cards.toggleClass('is-empty', cleared);
      $cards.find('.pngm-loc-current-name').text(cleared ? 'No location selected' : full);

      // Keep the hidden side-menu template in sync for the next open.
      $('#side-menu .box.location .pngm-loc-current').toggleClass('is-empty', cleared);
      $('#side-menu .box.location .pngm-loc-current-name').text(cleared ? 'No location selected' : full);
    }

    function pngmTrimRecentLocations($root) {
      var $items = $root.find('.row.recent a.location-elem, .pngm-loc-recent a.location-elem');
      if ($items.length > 5) {
        $items.slice(5).remove();
      }
      $root.find('.pngm-loc-view-all, .row.recent .view-all, a.view-all').remove();
    }

    function pngmIsMobileLoc() {
      try {
        if (window.matchMedia && window.matchMedia('(max-width: 767px)').matches) {
          return true;
        }
      } catch (err) {}
      var w = $(window).width();
      if (typeof scrollCompensate === 'function') {
        w += scrollCompensate();
      }
      return w < 768;
    }

    function pngmForceModalVisible(isMobile) {
      var $modal = $('.modal-box.location-select').last();
      if (!$modal.length) {
        return;
      }
      var mid = $modal.attr('data-modal-id');
      var $cover = $('.modal-cover[data-modal-id="' + mid + '"]');

      // Kill the floating alt-close SVG that shows as a "stranger" outside the dialog.
      $modal.find('.modal-close-alt').remove();

      $cover.css({
        display: 'block',
        opacity: 1,
        zIndex: 10190
      });

      if (isMobile) {
        $modal.addClass('modal-fullscreen pngm-loc-fullscreen');
        // Clear desktop centering inline styles that beat the mobile CSS.
        $modal.attr('style', '');
        $modal.css({
          display: 'block',
          opacity: 1,
          visibility: 'visible',
          zIndex: 10200,
          position: 'fixed',
          width: '100%',
          height: '100%',
          maxWidth: '100%',
          maxHeight: '100%',
          top: '0',
          left: '0',
          right: '0',
          bottom: '0',
          transform: 'none',
          borderRadius: '0',
          margin: '0',
          boxShadow: 'none'
        });
      } else {
        $modal.removeClass('pngm-loc-fullscreen');
        $modal.css({
          display: 'block',
          opacity: 1,
          visibility: 'visible',
          zIndex: 10200,
          width: '520px',
          height: '720px',
          maxWidth: 'calc(100vw - 32px)',
          maxHeight: 'calc(100vh - 48px)',
          top: '50%',
          left: '50%',
          right: 'auto',
          bottom: 'auto',
          transform: 'translate(-50%, -50%)',
          borderRadius: '16px'
        });
      }

      pngmTrimRecentLocations($modal);
    }

    function pngmReloadAfterLocation(message) {
      try {
        window.sessionStorage.setItem('pngm_loc_toast', message || 'Location updated');
      } catch (err) {}
      // Full reload so Near You listings + recent locations match the new cookie.
      window.location.reload();
    }

    function pngmSetLocationAjax(hash, onDone) {
      if (!window.baseAjaxUrl || !hash) {
        if (onDone) {
          onDone(false);
        }
        return;
      }
      $.ajax({
        type: 'GET',
        dataType: 'json',
        url: window.baseAjaxUrl + '&ajaxPngmSetLocation=1&hash=' + encodeURIComponent(hash),
        success: function (data) {
          if (data && data.success) {
            pngmApplyLocationUi(data);
            pngmCloseLocationModals();
            pngmReloadAfterLocation(data.message || 'Location updated');
            if (onDone) {
              onDone(true, data);
            }
            return;
          }
          pngmLocToast((data && data.message) || 'Could not update location', true);
          if (onDone) {
            onDone(false);
          }
        },
        error: function () {
          pngmLocToast('Could not update location', true);
          if (onDone) {
            onDone(false);
          }
        }
      });
    }

    function pngmClearLocationAjax() {
      if (!window.baseAjaxUrl) {
        return;
      }
      $.ajax({
        type: 'GET',
        dataType: 'json',
        url: window.baseAjaxUrl + '&ajaxPngmClearLocation=1',
        success: function (data) {
          if (data && data.success) {
            pngmApplyLocationUi(data);
            pngmCloseLocationModals();
            pngmReloadAfterLocation(data.message || 'Location cleared');
            return;
          }
          pngmLocToast((data && data.message) || 'Could not clear location', true);
        },
        error: function () {
          pngmLocToast('Could not clear location', true);
        }
      });
    }

    function pngmOpenLocationModal(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
          e.stopImmediatePropagation();
        }
      }
      if (typeof epsModal !== 'function') {
        return false;
      }

      $('#side-menu').removeClass('box-open').hide();
      $('#menu-cover').hide();

      var sectionHtml = $('#side-menu .box.location > .section').html() || '';
      if (!sectionHtml) {
        return false;
      }
      var isMobile = pngmIsMobileLoc();

      pngmCloseLocationModals();

      epsModal({
        width: isMobile ? window.innerWidth : 520,
        height: isMobile ? window.innerHeight : 720,
        content: '<div id="def-location" class="def-loc-box pngm-loc">' + sectionHtml + '</div>',
        wrapClass: 'location-select' + (isMobile ? ' pngm-loc-fullscreen' : ''),
        closeBtn: true,
        iframe: false,
        fullscreen: isMobile ? true : false,
        transition: 200,
        delay: 0,
        lockScroll: true
      });

      $('body').addClass('pngm-loc-modal-open');

      window.setTimeout(function () {
        pngmForceModalVisible(pngmIsMobileLoc());
      }, 20);
      window.setTimeout(function () {
        pngmForceModalVisible(pngmIsMobileLoc());
      }, 220);

      return false;
    }

    function pngmBindLocationOpeners() {
      $(locOpenSel).off('click');
      $(document).off('click.pngmLocOpen', locOpenSel);
      $(locOpenSel).on('click.pngmLocOpen', pngmOpenLocationModal);
    }

    pngmBindLocationOpeners();
    window.setTimeout(pngmBindLocationOpeners, 0);
    window.setTimeout(pngmBindLocationOpeners, 150);

    $(document).on('click.pngmLoc', '.pngm-loc-change', function (e) {
      e.preventDefault();
      var $root = $(this).closest('#def-location, .box.location, .pngm-loc');
      var $input = $root.find('input.location-pick').first();
      if (!$input.length) {
        $input = $('#def-location input.location-pick, #side-menu .box.location input.location-pick').first();
      }
      if ($input.length) {
        $input.trigger('focus');
        try {
          $input[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (err) {}
      }
    });

    // Pick a city / recent / search result — AJAX, no full page reload.
    $(document).on(
      'click.pngmLocPick',
      '#def-location a.location-elem, #side-menu .box.location a.location-elem, #def-location .picker.location .results a.option.direct, #side-menu .box.location .picker.location .results a.option.direct',
      function (e) {
        var href = $(this).attr('href') || '';
        if (href.indexOf('manualCookieLocation') === -1) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        var hash = pngmHashFromHref(href);
        if (!hash) {
          return;
        }
        pngmSetLocationAjax(hash);
      }
    );

    $(document).on('click.pngmLoc', '.pngm-loc-clear', function (e) {
      e.preventDefault();
      pngmClearLocationAjax();
    });

    $(document).on('click.pngmLoc', '.pngm-loc-apply', function (e) {
      e.preventDefault();
      pngmCloseLocationModals();
    });

    // Keep body class in sync when user taps X / cover.
    $(document).on('click.pngmLocCloseSync', '.modal-box.location-select .modal-close, .modal-cover[data-modal-id]', function () {
      window.setTimeout(function () {
        if (!$('.modal-box.location-select:visible').length) {
          $('body').removeClass('pngm-loc-modal-open');
        }
      }, 250);
    });

    // GPS already sets the cookie via ajaxFindCity — reload so Near You updates.
    $(document).on('click.pngmLocGps', '#def-location a.locate-me.completed, #side-menu .box.location a.locate-me.completed', function (e) {
      e.preventDefault();
      e.stopPropagation();
      pngmCloseLocationModals();
      pngmReloadAfterLocation('Location updated');
    });

    // Expose for GPS success hook.
    window.pngmLocAfterGeoSuccess = function (data) {
      if (!data) {
        return;
      }
      pngmApplyLocationUi({
        label: (data.s_city || data.s_name || '').split(',')[0] || 'Location',
        s_name: data.s_name || data.s_city || '',
        s_location: data.s_location || data.s_name || ''
      });
      pngmCloseLocationModals();
      pngmReloadAfterLocation('Location updated');
    };

    // Toast after reload (Near You / recent list refreshed from server).
    try {
      var pendingToast = window.sessionStorage.getItem('pngm_loc_toast');
      if (pendingToast) {
        window.sessionStorage.removeItem('pngm_loc_toast');
        pngmLocToast(pendingToast);
      }
    } catch (err) {}
  }

  function initSearchSortUi() {
    var SORT_LABEL = 'Sort by';

    function optionMeta(option) {
      var type = (option.getAttribute('data-type') || '').toLowerCase();
      var order = String(option.getAttribute('data-order') || '').toLowerCase();
      var label = (option.textContent || '').replace(/\s+/g, ' ').trim();
      var icon = 'fas fa-sort';
      var hint = '';

      if (type.indexOf('price') !== -1) {
        if (order === 'asc' || order === '0') {
          icon = 'fas fa-arrow-down';
          hint = 'Lowest price first';
        } else {
          icon = 'fas fa-arrow-up';
          hint = 'Highest price first';
        }
      } else {
        icon = 'fas fa-clock';
        hint = 'Most recent first';
      }

      return { icon: icon, hint: hint, label: label };
    }

    function closeAll(except) {
      var open = document.querySelectorAll('.pngm-sort-control.is-open');
      var i;
      for (i = 0; i < open.length; i += 1) {
        if (except && open[i] === except) {
          continue;
        }
        open[i].classList.remove('is-open');
        var trigger = open[i].querySelector('.pngm-sort-trigger');
        if (trigger) {
          trigger.setAttribute('aria-expanded', 'false');
        }
      }
    }

    function syncActive(wrap) {
      var select = wrap.querySelector('select.orderSelect');
      var valueNode = wrap.querySelector('.pngm-sort-trigger-value');
      var options = wrap.querySelectorAll('.pngm-sort-option');
      if (!select || !valueNode) {
        return;
      }

      var selected = select.options[select.selectedIndex];
      var meta = selected ? optionMeta(selected) : { label: '', icon: 'fas fa-sort' };
      valueNode.textContent = meta.label || '';

      var i;
      for (i = 0; i < options.length; i += 1) {
        var isActive = options[i].getAttribute('data-value') === select.value;
        options[i].classList.toggle('is-active', isActive);
        options[i].setAttribute('aria-selected', isActive ? 'true' : 'false');
      }
    }

    function enhance(wrap) {
      if (!wrap || wrap.getAttribute('data-pngm-sort-ready') === '1') {
        return;
      }

      var select = wrap.querySelector('select.orderSelect');
      if (!select || !select.options.length) {
        return;
      }

      wrap.setAttribute('data-pngm-sort-ready', '1');

      var trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'pngm-sort-trigger';
      trigger.setAttribute('aria-haspopup', 'listbox');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.innerHTML =
        '<span class="pngm-sort-trigger-ico" aria-hidden="true"><i class="fas fa-sliders-h"></i></span>' +
        '<span class="pngm-sort-trigger-copy">' +
          '<span class="pngm-sort-trigger-eyebrow">' + SORT_LABEL + '</span>' +
          '<span class="pngm-sort-trigger-value"></span>' +
        '</span>' +
        '<span class="pngm-sort-trigger-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>';

      var menu = document.createElement('div');
      menu.className = 'pngm-sort-menu';
      menu.setAttribute('role', 'listbox');

      var i;
      for (i = 0; i < select.options.length; i += 1) {
        var opt = select.options[i];
        var meta = optionMeta(opt);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pngm-sort-option';
        btn.setAttribute('role', 'option');
        btn.setAttribute('data-value', opt.value);
        btn.innerHTML =
          '<span class="pngm-sort-option-ico" aria-hidden="true"><i class="' + meta.icon + '"></i></span>' +
          '<span class="pngm-sort-option-text">' +
            '<span class="pngm-sort-option-label"></span>' +
            '<span class="pngm-sort-option-hint"></span>' +
          '</span>' +
          '<span class="pngm-sort-option-check" aria-hidden="true"><i class="fas fa-check"></i></span>';
        btn.querySelector('.pngm-sort-option-label').textContent = meta.label;
        btn.querySelector('.pngm-sort-option-hint').textContent = meta.hint;
        menu.appendChild(btn);
      }

      wrap.appendChild(trigger);
      wrap.appendChild(menu);
      syncActive(wrap);

      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var willOpen = !wrap.classList.contains('is-open');
        closeAll(wrap);
        wrap.classList.toggle('is-open', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });

      menu.addEventListener('click', function (e) {
        var option = e.target.closest('.pngm-sort-option');
        if (!option || !menu.contains(option)) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();

        var value = option.getAttribute('data-value');
        if (!value || select.value === value) {
          closeAll();
          return;
        }

        select.value = value;
        syncActive(wrap);
        closeAll();

        if (window.jQuery) {
          window.jQuery(select).trigger('change');
        } else {
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }

    function enhanceAll() {
      var nodes = document.querySelectorAll('.pngm-sort-control[data-pngm-sort="1"]');
      var i;
      for (i = 0; i < nodes.length; i += 1) {
        enhance(nodes[i]);
      }
    }

    document.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('.pngm-sort-control')) {
        return;
      }
      closeAll();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeAll();
      }
    });

    enhanceAll();

    if (window.jQuery) {
      window.jQuery(document).ajaxComplete(function () {
        window.setTimeout(enhanceAll, 30);
      });
    }
  }

  function initSearchRecentScroll() {
    function refreshButtons() {
      if (typeof window.epsShowUsefulScrollButtons === 'function') {
        window.epsShowUsefulScrollButtons();
      }
      if (typeof window.epsManageScroll === 'function') {
        window.epsManageScroll();
      }
    }

    function bindDrag(scroller) {
      if (!scroller || scroller.getAttribute('data-pngm-drag') === '1') {
        return;
      }
      scroller.setAttribute('data-pngm-drag', '1');

      var active = false;
      var startX = 0;
      var startLeft = 0;
      var moved = false;

      scroller.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'touch') {
          return;
        }
        if (e.button !== 0) {
          return;
        }
        active = true;
        moved = false;
        startX = e.clientX;
        startLeft = scroller.scrollLeft;
        scroller.classList.add('is-dragging');
        try {
          scroller.setPointerCapture(e.pointerId);
        } catch (err) {}
      });

      scroller.addEventListener('pointermove', function (e) {
        if (!active) {
          return;
        }
        var dx = e.clientX - startX;
        if (Math.abs(dx) > 3) {
          moved = true;
        }
        scroller.scrollLeft = startLeft - dx;
      });

      function endDrag(e) {
        if (!active) {
          return;
        }
        active = false;
        scroller.classList.remove('is-dragging');
        if (moved) {
          scroller.setAttribute('data-pngm-suppress-click', '1');
          window.setTimeout(function () {
            scroller.removeAttribute('data-pngm-suppress-click');
          }, 80);
        }
        try {
          scroller.releasePointerCapture(e.pointerId);
        } catch (err) {}
      }

      scroller.addEventListener('pointerup', endDrag);
      scroller.addEventListener('pointercancel', endDrag);

      scroller.addEventListener('click', function (e) {
        if (scroller.getAttribute('data-pngm-suppress-click') === '1') {
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);
    }

    function enhance() {
      var scrollers = document.querySelectorAll('#recent-ads.onsearch .products.grid.nice-scroll');
      var i;
      for (i = 0; i < scrollers.length; i += 1) {
        bindDrag(scrollers[i]);
      }
      refreshButtons();
    }

    enhance();
    window.addEventListener('resize', refreshButtons);

    if (window.jQuery) {
      window.jQuery(document).ajaxComplete(function () {
        window.setTimeout(enhance, 40);
      });
    }
  }

  function pngmShowToast(message, isError) {
    var host = document.getElementById('pngm-loc-toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'pngm-loc-toast-host';
      host.className = 'pngm-loc-toast-host';
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }

    var toast = document.createElement('div');
    toast.className = 'pngm-loc-toast' + (isError ? ' is-error' : '');
    toast.innerHTML =
      '<span class="pngm-loc-toast-msg"></span>' +
      '<button type="button" class="pngm-loc-toast-close" aria-label="Dismiss">&times;</button>';
    toast.querySelector('.pngm-loc-toast-msg').textContent = message || '';
    host.appendChild(toast);

    function dismiss() {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }

    toast.querySelector('.pngm-loc-toast-close').addEventListener('click', dismiss);
    window.setTimeout(dismiss, 4800);
  }

  window.pngmShowToast = pngmShowToast;

  function initOwnListingChat() {
    document.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('a[data-pngm-chat-own="1"]') : null;
      if (!link) {
        return;
      }
      e.preventDefault();
      e.stopPropagation();
      var msg = link.getAttribute('title') || 'This is your listing. You cannot message yourself.';
      pngmShowToast(msg, true);
    }, true);
  }

  function initFlashToasts() {
    var box = document.getElementById('flashbox');
    if (!box) {
      return;
    }
    var msgs = box.querySelectorAll('.flashmessage');
    if (!msgs.length) {
      return;
    }

    Array.prototype.forEach.call(msgs, function (el) {
      var clone = el.cloneNode(true);
      var closer = clone.querySelector('.btn.ico-close, .close, a.ico-close');
      if (closer && closer.parentNode) {
        closer.parentNode.removeChild(closer);
      }
      var text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
      if (!text) {
        return;
      }
      var isError = el.className.indexOf('flashmessage-error') !== -1;
      pngmShowToast(text, isError);
    });

    box.innerHTML = '';
    box.style.display = 'none';
  }

  function initItemReport() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;
    var ajaxUrl = (typeof window.baseAjaxUrl === 'string' && window.baseAjaxUrl)
      ? window.baseAjaxUrl
      : (window.location.origin + window.location.pathname + '?ajaxRequest=1');

    function setReportModalOpen(open) {
      document.body.classList.toggle('pngm-report-modal-open', !!open);
      if (!open) {
        document.body.style.overflow = '';
      }
    }

    function closeExistingReportModals() {
      $('.modal-box.report-box, .modal-box.pngm-report-box').each(function () {
        var id = $(this).attr('data-modal-id');
        if (id && typeof window.epsModalClose === 'function') {
          window.epsModalClose(id);
        } else {
          $(this).remove();
          if (id) {
            $('.modal-cover[data-modal-id="' + id + '"]').remove();
          }
        }
      });
      setReportModalOpen(false);
    }

    function unbindParentReportHandler() {
      $('body').off('click', '.report-button');
    }

    unbindParentReportHandler();

    $('body').off('click.pngmReport', '.report-button, a[data-pngm-report="1"]');
    $('body').on('click.pngmReport', '.report-button, a[data-pngm-report="1"]', function (event) {
      event.preventDefault();
      event.stopPropagation();
      event.stopImmediatePropagation();

      unbindParentReportHandler();
      closeExistingReportModals();

      var $wrap = $(this).siblings('.report-wrap, .pngm-report-wrap').first();
      if (!$wrap.length) {
        $wrap = $(this).closest('#item-side').find('.report-wrap, .pngm-report-wrap').first();
      }
      if (!$wrap.length) {
        $wrap = $('.report-wrap, .pngm-report-wrap').first();
      }

      var html = $.trim($wrap.html() || '');
      if (!html || typeof window.epsModal !== 'function') {
        return false;
      }

      window.epsModal({
        width: 420,
        height: 620,
        content: html,
        wrapClass: 'report-box pngm-report-box',
        closeBtn: true,
        iframe: false,
        fullscreen: 'mobile',
        transition: 200,
        delay: 0,
        lockScroll: true
      });

      var $box = $('.modal-box.report-box, .modal-box.pngm-report-box').last();
      $box.prev('.modal-cover').addClass('pngm-report-cover');
      setReportModalOpen(true);

      return false;
    });

    $('body').off('click.pngmReportReason', '.modal-box.pngm-report-box .pngm-report-reason, .modal-box.report-box #report .text a');
    $('body').on('click.pngmReportReason', '.modal-box.pngm-report-box .pngm-report-reason, .modal-box.report-box #report .text a', function (event) {
      event.preventDefault();
      event.stopImmediatePropagation();

      var $link = $(this);
      if ($link.data('pngmBusy')) {
        return false;
      }
      $link.data('pngmBusy', true);

      var itemId = $link.attr('data-id') || '';
      var as = $link.attr('data-as') || '';
      var href = $link.attr('href') || '';

      $.ajax({
        url: ajaxUrl.replace(/ajaxRequest=1.*/, 'page=ajax&action=runhook&hook=pngm_report_item'),
        method: 'POST',
        dataType: 'json',
        data: { id: itemId, as: as }
      }).done(function (res) {
        closeExistingReportModals();
        if (res && res.ok) {
          pngmShowToast(res.message || 'Thanks! Your report was sent.', false);
        } else if (href) {
          window.location.href = href;
        } else {
          pngmShowToast((res && res.message) || 'Could not send the report.', true);
        }
      }).fail(function () {
        if (href) {
          window.location.href = href;
        } else {
          closeExistingReportModals();
          pngmShowToast('Could not send the report.', true);
        }
      });

      return false;
    });

    $('body').on('click.pngmReportClose', '.pngm-report-cover, .modal-box.pngm-report-box .modal-close, .modal-box.pngm-report-box .modal-close-alt', function () {
      window.setTimeout(function () {
        if (!$('.modal-box.pngm-report-box, .modal-box.report-box').length) {
          setReportModalOpen(false);
        }
      }, 250);
    });

    window.setTimeout(unbindParentReportHandler, 0);
    $(window).on('load.pngmReport', unbindParentReportHandler);
  }

  /**
   * Header/sidebar unread badges only reflected the state at page render, so a
   * message arriving while the tab sat open went unnoticed. Poll for the counts
   * and update the badges in place.
   */
  function initBadgePoller() {
    var badges = document.querySelectorAll('[data-pngm-badge]');
    if (!badges.length || !window.jQuery) {
      return;
    }

    var $ = window.jQuery;
    var base = (typeof window.baseAjaxUrl === 'string' && window.baseAjaxUrl)
      ? window.baseAjaxUrl
      : (window.location.origin + window.location.pathname + '?ajaxRequest=1');
    var url = base.replace(/ajaxRequest=1.*/, 'page=ajax&action=runhook&hook=pngm_badge_counts');
    var INTERVAL = document.querySelector('.pngm-im-convo-list') ? 12000 : 30000;
    var timer = null;

    function paint(counts) {
      if (!counts) {
        return;
      }
      $('[data-pngm-badge]').each(function () {
        var key = this.getAttribute('data-pngm-badge');
        if (!Object.prototype.hasOwnProperty.call(counts, key)) {
          return;
        }
        var n = parseInt(counts[key], 10) || 0;
        this.textContent = n > 99 ? '99+' : String(n);
        if (n > 0) {
          this.removeAttribute('hidden');
        } else {
          this.setAttribute('hidden', 'hidden');
        }
      });

      // Conversation-list unread dots (Messages page).
      if (counts.threads && typeof counts.threads === 'object') {
        var activeHref = '';
        var activeCard = document.querySelector('.pngm-im-convo.is-active');
        if (activeCard) {
          activeHref = activeCard.getAttribute('href') || '';
        }
        document.querySelectorAll('.pngm-im-convo[data-thread-id]').forEach(function (card) {
          var tid = String(card.getAttribute('data-thread-id') || '');
          var n = parseInt(counts.threads[tid], 10) || 0;
          var isActive = card.classList.contains('is-active')
            || (activeHref !== '' && card.getAttribute('href') === activeHref);
          if (isActive) {
            n = 0;
          }
          card.setAttribute('data-unread', String(n));
          card.classList.toggle('is-unread', n > 0);

          var label = n > 99 ? '99+' : String(n);
          var bottom = card.querySelector('.pngm-im-convo-bottom');
          var dot = card.querySelector('.pngm-im-unread-dot');
          // Remove legacy avatar badges — unread count stays on the time side only.
          var badge = card.querySelector('.pngm-im-convo-badge');
          if (badge && badge.parentNode) {
            badge.parentNode.removeChild(badge);
          }

          if (n > 0) {
            if (!dot && bottom) {
              dot = document.createElement('span');
              dot.className = 'pngm-im-unread-dot';
              dot.setAttribute('aria-label', 'Unread');
              bottom.appendChild(dot);
            }
            if (dot) {
              dot.textContent = label;
            }
          } else if (dot && dot.parentNode) {
            dot.parentNode.removeChild(dot);
          }
        });
      }
    }

    function poll() {
      // Skip while the tab is in the background; refresh on the way back.
      if (document.hidden) {
        return;
      }
      $.ajax({ url: url, method: 'GET', dataType: 'json', cache: false }).done(paint);
    }

    function start() {
      if (timer === null) {
        timer = window.setInterval(poll, INTERVAL);
      }
    }

    function stop() {
      if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        stop();
      } else {
        poll();
        start();
      }
    });

    // Immediate sync so sidebar/list match the navbar on first paint.
    poll();
    start();
  }

  function init() {
    try {
      var mobileIm = window.matchMedia && window.matchMedia('(max-width: 980px)').matches;
      document.cookie = 'pngm_im_mobile=' + (mobileIm ? '1' : '0') + '; path=/; max-age=31536000; SameSite=Lax';
    } catch (e) {}
    initCategories();
    initStickyHomeSearch();
    initPatternSearchLocation();
    initPopularCitiesOrder();
    initPostingCityGrouping();
    initSearchableLocationSelects();
    initVehicleMakeOther();
    initVehicleSearchModelLabel();
    initItemGallery();
    initUserAccountUx();
    initPostingPlaceholders();
    initGeoLocate();
    initSearchSubcats();
    initItemPostMinlength();
    initItemPostValidation();
    initPostPhotoPreview();
    initUppyOverNav();
    initLiveSearchBoard();
    initMobileSearchFilters();
    initChatLayout();
    initItemDescriptionClamp();
    initLocationModalUx();
    initSearchSortUi();
    initSearchRecentScroll();
    initItemReport();
    initFlashToasts();
    initBadgePoller();
    initOwnListingChat();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
