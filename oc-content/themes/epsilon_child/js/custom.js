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
                link.attr('href', String(window.location.href).replace('#', '')).addClass('completed');
                var alt = link.find('strong').attr('data-alt-text');
                if (alt) {
                  link.find('strong').text(alt);
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
    var list = document.querySelector('.pngm-search-subcats-list');
    if (!list) {
      return;
    }

    var active = list.querySelector('.is-active');
    if (active && typeof active.scrollIntoView === 'function') {
      active.scrollIntoView({ inline: 'center', block: 'nearest' });
    }
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
      var src = el.getAttribute('src') || '';
      return src.replace('_thumbnail.', '.').replace('_preview.', '.');
    }

    function open(src) {
      img.src = src;
      resetTransform();
      viewer.removeAttr('hidden');
      document.body.style.overflow = 'hidden';
    }

    function close() {
      viewer.attr('hidden', 'hidden');
      img.removeAttribute('src');
      resetTransform();
      document.body.style.overflow = '';
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

    $(document).on('click.pngmPhotoPreview', '.upload-photos .ajax_preview_img img, #photos .ajax_preview_img img', function (e) {
      if ($(e.target).closest('.qq-upload-delete, .qq-upload-rotate, .qq-upload-move, .qq-upload-rotate-img').length) {
        return;
      }

      e.preventDefault();
      e.stopPropagation();
      open(fullSrc(this));
    });

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
      var $side = $('form.search-side-form').not('#side-menu form').first().find('input[name="sPattern"]');
      if (!$side.length) {
        $side = $('.filter-menu form.search-side-form').first().find('input[name="sPattern"]');
      }
      if ($side.length) {
        $side.val(val);
        triggerAjax($side, event);
      }
    });

    $('body#search').on('submit', '.global-search-form', function (e) {
      e.preventDefault();
      var $input = $(this).find('input.pattern');
      var $side = $('.filter-menu form.search-side-form').first().find('input[name="sPattern"]');
      if ($side.length) {
        $side.val($input.val());
        triggerAjax($side, $.Event('keyup'));
      }
    });

    // Keep header keyword in sync after AJAX board refresh.
    $('body#search').on('keyup input', 'form.search-side-form input[name="sPattern"]', function () {
      if ($(this).closest('#side-menu .box.filter').length) {
        return;
      }
      $('.pngm-global-search input.pattern').val($(this).val());
    });

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
        if (inDrawer && event && event.type !== 'click') {
          return;
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
      $form.prepend($scroll);
      if ($btn.length) {
        $form.append($btn);
      }
      $form.addClass('pngm-filter-laid-out');
      $form.find('input[name="sPattern"]').addClass('pattern');
    }

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

    if (!form && !document.querySelector('.im-file-messages')) {
      return;
    }

    document.body.classList.add('im-chat-page');

    function layout(opts) {
      form = document.getElementById('im-message-form');
      board = document.querySelector('.im-table.im-messages');
      if (!board) {
        return;
      }

      var pinBottom = !!(opts && opts.pinBottom);
      var nearBottom = (board.scrollHeight - board.scrollTop - board.clientHeight) < 80;

      // Desktop: CSS handles board max-height; only pin scroll position.
      // Mobile: fill remaining viewport under header/composer (telegram-style).
      if (window.innerWidth > 767) {
        board.style.maxHeight = '';
        board.style.overflowY = '';
        if (pinBottom || nearBottom) {
          board.scrollTop = board.scrollHeight;
        }
        return;
      }

      var navi = document.getElementById('navi-bar');
      var naviH = 0;
      if (navi && window.getComputedStyle(navi).display !== 'none') {
        naviH = navi.offsetHeight;
      }

      var formH = form ? form.offsetHeight : 0;
      var top = board.getBoundingClientRect().top;
      var available = Math.floor(window.innerHeight - top - formH - naviH - 4);
      if (available < 80) {
        available = 80;
      }

      board.style.maxHeight = available + 'px';
      board.style.overflowY = 'auto';

      if (pinBottom || nearBottom) {
        board.scrollTop = board.scrollHeight;
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
    window.setTimeout(function () {
      layout({ pinBottom: true });
    }, 50);
    window.setTimeout(function () {
      layout({ pinBottom: true });
    }, 250);
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
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
