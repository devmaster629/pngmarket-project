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

    // Mobile: go to the category page. Subcategories are listed there.
    return;
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
        .replace(/\s+/g, ' ');
    }

    function mainKeysForRegion(regionName) {
      var keys = {};
      var rk = locKey(regionName);
      var capitals = cfg.capitals || {};
      var towns = cfg.mainTowns || {};
      var i;

      if (capitals[rk]) {
        keys[locKey(capitals[rk])] = true;
      }

      if (towns[rk] && towns[rk].length) {
        for (i = 0; i < towns[rk].length; i++) {
          keys[locKey(towns[rk][i])] = true;
        }
      }

      // Fallback: national main cities when province is unmapped.
      if (!Object.keys(keys).length && cfg.mainCities && cfg.mainCities.length) {
        for (i = 0; i < cfg.mainCities.length; i++) {
          keys[locKey(cfg.mainCities[i])] = true;
        }
      }

      return keys;
    }

    function isMainCity(name, regionName, tier) {
      if (tier === 'main') {
        return true;
      }

      if (tier === 'other') {
        return false;
      }

      var keys = mainKeysForRegion(regionName);
      return !!keys[locKey(name)];
    }

    function optionHtml(id, name, selectedId) {
      var sel = String(id) === String(selectedId) ? ' selected' : '';
      return '<option value="' + id + '"' + sel + '>' + name + '</option>';
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

    function buildGroupedHtml(items, selectedId) {
      var main = [];
      var other = [];
      var i;
      var html = '<option value="">' + labelSelect + '</option>';

      for (i = 0; i < items.length; i++) {
        if (items[i].main) {
          main.push(items[i]);
        } else {
          other.push(items[i]);
        }
      }

      main.sort(function (a, b) {
        var pa = mainPriority(a.name);
        var pb = mainPriority(b.name);

        if (pa !== pb) {
          return pa - pb;
        }

        return String(a.name).localeCompare(String(b.name));
      });

      other.sort(function (a, b) {
        return String(a.name).localeCompare(String(b.name));
      });

      if (main.length) {
        html += '<optgroup label="' + labelMain + '">';
        for (i = 0; i < main.length; i++) {
          html += optionHtml(main[i].id, main[i].name, selectedId);
        }
        html += '</optgroup>';
      }

      if (other.length) {
        html += '<optgroup label="' + labelOther + '">';
        for (i = 0; i < other.length; i++) {
          html += optionHtml(other[i].id, other[i].name, selectedId);
        }
        html += '</optgroup>';
      }

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

      if ($.isArray(data) && data.length) {
        for (i = 0; i < data.length; i++) {
          if (!data[i] || data[i].pk_i_id === undefined) {
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
            main: isMainCity(name, regionName, tier)
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
            main: isMainCity(name, regionName, '')
          });
        });
      }

      if (!items.length) {
        return;
      }

      // Keep relative order (already main-first from server when data provided).
      $city.html(buildGroupedHtml(items, selectedId));

      if (selectedId) {
        $city.val(selectedId);
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

    // After core builds the flat city list, regroup with optgroups.
    $(document).ajaxSuccess(function (event, xhr, settings) {
      var url = settings && settings.url ? String(settings.url) : '';

      if (url.indexOf('action=cities') === -1) {
        return;
      }

      var data;

      try {
        data = JSON.parse(xhr.responseText);
      } catch (e) {
        return;
      }

      if (!$.isArray(data)) {
        return;
      }

      // Defer so parent success handler finishes writing options first.
      setTimeout(function () {
        regroupCitySelect($('#cityId'), currentRegionName(), data);
      }, 0);
    });

    // Initial publish/edit form: regroup the PHP-rendered city list.
    setTimeout(function () {
      var $city = $('#cityId');

      if ($city.length && $city.find('option[value!=""]').length > 0) {
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
      var label = selectedLabel($select);

      $input.val(label);
      $input.prop('disabled', disabled);
      widget.toggleClass('is-disabled', disabled);
      widget.find('.pngm-search-select-clear').toggle(!!label && !disabled);
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

      if ($select.prop('disabled')) {
        return;
      }

      closeAll(widget);
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
        var key = String(name || '').toLowerCase().replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
        var i;

        for (i = 0; i < popular.length; i++) {
          if (popular[i] === key) {
            return i;
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

      syncFromSelect($el.closest('.pngm-search-select'));
    }

    window.pngmRefreshSearchSelect = refresh;

    // Only enhance classic region/city selects (not autocomplete location mode).
    if ($('#regionId').is('select') || $('#cityId').is('select')) {
      wrapSelect($('#regionId'));
      wrapSelect($('#cityId'));
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
   * ITEM-01 / ITEM-02 — Listing photo gallery:
   * swipe, pinch-zoom (mobile), fullscreen lightbox.
   */
  function initItemGallery() {
    if (typeof window.jQuery === 'undefined') {
      return;
    }

    var $ = window.jQuery;
    var root = $('#item-image');

    if (!root.length || !root.find('.swiper-container').length) {
      return;
    }

    var container = root.find('.swiper-container').first();
    var isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

    root.find('.swiper-thumbs').attr('data-pngm-gallery', '1');
    root.addClass('pngm-gallery-ready');

    function syncThumbs(index) {
      root.find('.swiper-thumbs li').removeClass('active');
      root.find('.swiper-thumbs li[data-id="' + index + '"]').addClass('active');
    }

    function distance(a, b) {
      var dx = a.clientX - b.clientX;
      var dy = a.clientY - b.clientY;
      return Math.sqrt(dx * dx + dy * dy);
    }

    function bindPinchZoom(stage, getImg) {
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
      var originX = 50;
      var originY = 50;
      var lastTap = 0;

      function imgEl() {
        return typeof getImg === 'function' ? getImg() : getImg;
      }

      function apply() {
        var img = imgEl();
        if (!img) {
          return;
        }
        img.style.transition = 'none';
        img.style.transformOrigin = originX + '% ' + originY + '%';
        img.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(' + scale + ')';
        img.style.willChange = scale > 1 ? 'transform' : 'auto';
      }

      function reset() {
        scale = 1;
        tx = 0;
        ty = 0;
        originX = 50;
        originY = 50;
        apply();
        if (window.pngmItemSwiper && window.pngmItemSwiper.allowTouchMove !== undefined) {
          window.pngmItemSwiper.allowTouchMove = true;
        }
      }

      function setAllowSwipe() {
        if (window.pngmItemSwiper && window.pngmItemSwiper.allowTouchMove !== undefined) {
          window.pngmItemSwiper.allowTouchMove = scale <= 1.05;
        }
      }

      function clampPan() {
        var limit = 180 * Math.max(0, scale - 1);
        if (tx > limit) { tx = limit; }
        if (tx < -limit) { tx = -limit; }
        if (ty > limit) { ty = limit; }
        if (ty < -limit) { ty = -limit; }
      }

      stage.addEventListener('touchstart', function (e) {
        var img = imgEl();
        if (e.touches.length === 2) {
          startDist = distance(e.touches[0], e.touches[1]);
          startScale = scale;
          panning = false;
          if (img) {
            var rect = img.getBoundingClientRect();
            var midX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
            var midY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
            originX = rect.width ? ((midX - rect.left) / rect.width) * 100 : 50;
            originY = rect.height ? ((midY - rect.top) / rect.height) * 100 : 50;
          }
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
        if (e.touches.length >= 2 && startDist > 0) {
          e.preventDefault();
          e.stopPropagation();
          scale = Math.min(5, Math.max(1, startScale * (distance(e.touches[0], e.touches[1]) / startDist)));
          if (scale <= 1.02) {
            scale = 1;
            tx = 0;
            ty = 0;
          }
          apply();
          setAllowSwipe();
        } else if (e.touches.length === 1 && panning) {
          e.preventDefault();
          tx = panX + (e.touches[0].clientX - lastX);
          ty = panY + (e.touches[0].clientY - lastY);
          clampPan();
          apply();
        }
      }, { passive: false });

      stage.addEventListener('touchend', function (e) {
        if (e.touches.length === 0 && scale < 1.08) {
          reset();
        }
        if (e.touches.length < 2) {
          startDist = 0;
        }

        if (e.changedTouches && e.changedTouches.length === 1 && !panning && scale <= 1.05) {
          var now = Date.now();
          if (now - lastTap < 280) {
            scale = 2.4;
            apply();
            setAllowSwipe();
            lastTap = 0;
          } else {
            lastTap = now;
          }
        }
      }, { passive: true });

      return {
        reset: reset,
        isZoomed: function () {
          return scale > 1.05;
        }
      };
    }

    function initSwiper() {
      if (typeof window.Swiper === 'undefined') {
        return null;
      }

      var el = container[0];

      if (el && el.swiper) {
        try {
          el.swiper.destroy(true, true);
        } catch (e) {
          // ignore
        }
      }

      return new window.Swiper(el, {
        slideClass: 'swiper-slide',
        resistanceRatio: 0.65,
        zoom: false,
        navigation: {
          nextEl: root.find('.swiper-next')[0],
          prevEl: root.find('.swiper-prev')[0]
        },
        pagination: {
          el: root.find('.swiper-pg')[0],
          type: 'fraction'
        },
        on: {
          activeIndexChange: function (swp) {
            if (typeof window.epsLazyLoadImages === 'function') {
              window.epsLazyLoadImages('item-gallery');
            }

            var slide = swp.slides[swp.activeIndex];
            if (slide) {
              var lazy = slide.querySelector('img[data-src]');
              if (lazy && lazy.getAttribute('src') !== lazy.getAttribute('data-src')) {
                lazy.setAttribute('src', lazy.getAttribute('data-src'));
              }
            }

            syncThumbs(swp.activeIndex);

            if (window.pngmGalleryPinch) {
              window.pngmGalleryPinch.reset();
            }
          },
          init: function () {
            container.find('img[data-src]').each(function () {
              var img = window.jQuery(this);
              var real = img.attr('data-src');
              if (real && img.attr('src') !== real) {
                img.attr('src', real);
              }
            });
          }
        }
      });
    }

    function initLightbox() {
      if (typeof $.fn.lightGallery === 'undefined') {
        return;
      }

      try {
        if (container.data('lightGallery')) {
          container.data('lightGallery').destroy(true);
        }
      } catch (e) {
        // ignore
      }

      // Detach parent click→lg path so we fully control open behaviour.
      container.off('onBeforeOpen.lg onAfterOpen.lg');

      container.lightGallery({
        mode: 'lg-slide',
        thumbnail: true,
        cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
        selector: 'li.swiper-slide > a',
        getCaptionFromTitleOrAlt: true,
        download: false,
        share: false,
        zoom: true,
        scale: 1,
        enableZoomAfter: 100,
        actualSize: true,
        showZoomInOutIcons: true,
        fullScreen: true,
        counter: true,
        closable: true,
        escKey: true,
        keyPress: true,
        controls: true,
        mousewheel: false,
        hideBarsDelay: 4000,
        thumbWidth: 90,
        thumbContHeight: 80,
        swipeThreshold: 80,
        enableDrag: !isTouch,
        enableSwipe: true,
        speed: 280
      });
    }

    function openLightboxAt(index) {
      var link = root.find('.swiper-slide').eq(index).find('> a').get(0);

      if (link) {
        // Ensure real image src is loaded before lightbox opens.
        var img = $(link).find('img');
        if (img.length && img.attr('data-src') && img.attr('src') !== img.attr('data-src')) {
          img.attr('src', img.attr('data-src'));
        }

        link.click();
      }
    }

    // Wait for parent global.js Swiper/lightGallery, then replace.
    setTimeout(function () {
      window.pngmItemSwiper = initSwiper();
      initLightbox();

      window.pngmGalleryPinch = bindPinchZoom(container[0], function () {
        var slide = container.find('.swiper-slide-active .swiper-zoom-container img');
        if (!slide.length) {
          slide = container.find('.swiper-slide .swiper-zoom-container img').first();
        }
        return slide[0] || null;
      });

      // Pinch / pan must not open the lightbox. A clean tap still does.
      var touchMoved = false;
      var touchCount = 1;

      container.on('touchstart.pngmZoom', 'li.swiper-slide > a', function (e) {
        var touches = e.originalEvent && e.originalEvent.touches;
        touchCount = touches ? touches.length : 1;
        touchMoved = false;
      });

      container.on('touchmove.pngmZoom', 'li.swiper-slide > a', function (e) {
        var touches = e.originalEvent && e.originalEvent.touches;
        if (touches && touches.length > 1) {
          touchCount = touches.length;
        }
        touchMoved = true;
      });

      container[0].addEventListener('click', function (e) {
        var link = e.target.closest ? e.target.closest('li.swiper-slide > a') : null;
        if (!link || !container[0].contains(link)) {
          return;
        }

        var zoomed = window.pngmGalleryPinch && window.pngmGalleryPinch.isZoomed();

        if (touchCount > 1 || touchMoved || zoomed) {
          e.preventDefault();
          e.stopPropagation();
          if (typeof e.stopImmediatePropagation === 'function') {
            e.stopImmediatePropagation();
          }
        }
      }, true);

      root.find('.pngm-gallery-fullscreen').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var idx = window.pngmItemSwiper ? window.pngmItemSwiper.activeIndex : 0;
        openLightboxAt(idx);
      });

      root.off('click.pngmThumbs').on('click.pngmThumbs', '.swiper-thumbs li', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var elemId = parseInt($(this).attr('data-id'), 10) || 0;
        syncThumbs(elemId);

        if (window.pngmItemSwiper) {
          if (typeof window.epsFixImgSourcesThumb === 'function') {
            window.epsFixImgSourcesThumb();
          }

          window.pngmItemSwiper.slideTo(elemId);
        }
      });
    }, 350);

    $(document).on('onAfterOpen.lg', function () {
      $('body').addClass('pngm-lg-open');

      if (typeof window.epsFixImgSources === 'function') {
        window.epsFixImgSources();
      }

      var lgStage = document.querySelector('.lg-outer');
      if (lgStage && !lgStage.getAttribute('data-pngm-pinch')) {
        lgStage.setAttribute('data-pngm-pinch', '1');
        lgStage.addEventListener('touchmove', function (e) {
          if (e.touches && e.touches.length > 1) {
            e.preventDefault();
          }
        }, { passive: false });
      }

      var startX = 0;
      var startY = 0;
      var startFingers = 1;
      var pinch = {
        scale: 1,
        tx: 0,
        ty: 0,
        startScale: 1,
        startDist: 0,
        panX: 0,
        panY: 0,
        lastX: 0,
        lastY: 0,
        panning: false
      };

      function currentImage() {
        return document.querySelector('.lg-item.lg-current .lg-image, .lg-item.lg-current img');
      }

      function applyLg() {
        var img = currentImage();
        if (!img) {
          return;
        }
        img.style.transformOrigin = 'center center';
        img.style.transition = 'none';
        img.style.transform = 'translate3d(' + pinch.tx + 'px,' + pinch.ty + 'px,0) scale(' + pinch.scale + ')';
      }

      function resetLg() {
        pinch.scale = 1;
        pinch.tx = 0;
        pinch.ty = 0;
        applyLg();
      }

      function dist(a, b) {
        var dx = a.clientX - b.clientX;
        var dy = a.clientY - b.clientY;
        return Math.sqrt(dx * dx + dy * dy);
      }

      $(document).off('touchstart.pngmLgClose touchmove.pngmLgClose touchend.pngmLgClose');
      $(document).on('touchstart.pngmLgClose', '.lg-outer', function (e) {
        var touches = e.originalEvent && e.originalEvent.touches;
        startFingers = touches ? touches.length : 1;
        var t = touches && touches[0];
        if (!t) {
          return;
        }
        startX = t.clientX;
        startY = t.clientY;

        if (touches.length === 2) {
          pinch.startDist = dist(touches[0], touches[1]);
          pinch.startScale = pinch.scale;
          pinch.panning = false;
        } else if (touches.length === 1 && pinch.scale > 1.08) {
          pinch.panning = true;
          pinch.lastX = t.clientX;
          pinch.lastY = t.clientY;
          pinch.panX = pinch.tx;
          pinch.panY = pinch.ty;
        } else {
          pinch.panning = false;
        }
      });

      $(document).on('touchmove.pngmLgClose', '.lg-outer', function (e) {
        var touches = e.originalEvent && e.originalEvent.touches;
        if (!touches) {
          return;
        }

        if (touches.length >= 2 && pinch.startDist > 0) {
          e.preventDefault();
          pinch.scale = Math.min(5, Math.max(1, pinch.startScale * (dist(touches[0], touches[1]) / pinch.startDist)));
          if (pinch.scale <= 1.02) {
            pinch.scale = 1;
            pinch.tx = 0;
            pinch.ty = 0;
          }
          applyLg();
        } else if (touches.length === 1 && pinch.panning) {
          e.preventDefault();
          pinch.tx = pinch.panX + (touches[0].clientX - pinch.lastX);
          pinch.ty = pinch.panY + (touches[0].clientY - pinch.lastY);
          applyLg();
        }
      });

      $(document).on('touchend.pngmLgClose', '.lg-outer', function (e) {
        var touches = e.originalEvent && e.originalEvent.touches;
        var t = e.originalEvent && e.originalEvent.changedTouches ? e.originalEvent.changedTouches[0] : null;
        if (!t || startFingers > 1 || (touches && touches.length > 0)) {
          if (!touches || touches.length === 0) {
            pinch.startDist = 0;
            if (pinch.scale < 1.08) {
              resetLg();
            }
          }
          return;
        }

        if (pinch.scale > 1.08) {
          return;
        }

        var dx = t.clientX - startX;
        var dy = t.clientY - startY;
        var absX = Math.abs(dx);
        var absY = Math.abs(dy);

        if ((dy > 70 && absX < 90) || (dx > 80 && absY < 80) || (dx < -80 && absY < 80)) {
          $('.lg-close').trigger('click');
        }
      });
    });

    $(document).on('onCloseAfter.lg', function () {
      $('body').removeClass('pngm-lg-open');
      $(document).off('touchstart.pngmLgClose touchend.pngmLgClose');
    });
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
        $(this).rules('add', {
          minlength: 10,
          messages: {
            minlength: 'Description: enter at least 10 characters.'
          }
        });
      });

      return true;
    }

    if (!apply()) {
      setTimeout(apply, 50);
      setTimeout(apply, 300);
    }
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
        var isKeyword = $elem.attr('name') === 'sPattern';

        if (inDrawer && event && event.type !== 'click') {
          return;
        }

        if (isKeyword && event && (event.type === 'keyup' || event.type === 'input')) {
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

    $('body').on('click', '#open-search-filters, .action.open-filters', function () {
      $('body').addClass('pngm-filter-open');
      setTimeout(function () {
        var $panel = $('#side-menu .box.filter');
        uniqueIds($panel.find('.section.filter-menu'));
        $panel.find('input[name="sPattern"]').attr('autocomplete', 'off');
      }, 40);
    });

    $('body').on('click', '#side-menu .box.filter .back, #menu-cover', function () {
      $('body').removeClass('pngm-filter-open');
    });
  }

  function init() {
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
    initPostPhotoPreview();
    initUppyOverNav();
    initMobileSearchFilters();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
