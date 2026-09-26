/**
 * PNG Market 6-step post listing wizard.
 */
(function ($) {
  'use strict';

  function boot() {
    try {
      return JSON.parse(document.getElementById('pngm-post-boot').textContent || '{}');
    } catch (e) {
      return {};
    }
  }

  function subcats() {
    try {
      return JSON.parse(document.getElementById('pngm-post-subcats').textContent || '{}');
    } catch (e) {
      return {};
    }
  }

  function catKeywords() {
    try {
      return JSON.parse(document.getElementById('pngm-post-cat-keywords').textContent || '{}');
    } catch (e) {
      return {};
    }
  }

  function init() {
    var $form = $('form.pngm-post-form[data-pngm-wizard="1"]');
    if (!$form.length) {
      return;
    }

    var cfg = boot();
    var map = subcats();
    var keywords = catKeywords();
    var step = 1;
    var total = 6;
    var labels = cfg.labels || {};

    var $panels = $form.find('.pngm-post-step-panel');
    var $stepper = $('.pngm-post-stepper');
    var $next = $('#pngm-post-next');
    var $back = $('#pngm-post-back');
    var $cancel = $('#pngm-post-cancel');
    var $publish = $('#pngm-post-publish');
    var $preview = $('#pngm-post-preview');
    var $catId = $('#catId');
    var $root = $('#pngm_root_cat');
    var $sub = $('#pngm_subcategory');
    var $subWrap = $('.pngm-post-subcat-wrap');
    var $subList = $('#pngm-post-subcat-list');
    var $subPicked = $('#pngm-post-subcat-picked');
    var $subChange = $('#pngm-post-subcat-change');
    var $sheet = $('#pngm-post-sheet');
    var $sheetBackdrop = $('#pngm-post-sheet-backdrop');
    var $sheetTitle = $('#pngm-post-sheet-title');
    var $sheetBody = $('#pngm-post-sheet-body');
    var sheetOpen = false;
    var $txNative = $('#sTransaction');
    var $errorList = $('#error_list');
    var $suggestBox = $('#pngm-cat-suggest');
    var $suggestChips = $('#pngm-cat-suggest-chips');
    var summaryMessages = [];
    var suggestTimer = null;
    var lastSuggestKey = '';

    function normalizeText(s) {
      return String(s || '')
        .toLowerCase()
        .replace(/['’]/g, '')
        .replace(/[^a-z0-9\s&+]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function buildCatIndex() {
      var index = [];
      var byLeafName = {};
      $('.pngm-post-cat-card').each(function () {
        var rootId = parseInt($(this).data('root-id'), 10) || 0;
        var rootName = String($(this).data('root-name') || '');
        var list = map[String(rootId)] || map[rootId] || [];
        list.forEach(function (row) {
          var leafName = String(row.name || '');
          var entry = {
            rootId: rootId,
            rootName: rootName,
            leafId: parseInt(row.id, 10) || 0,
            leafName: leafName,
            leafNorm: normalizeText(leafName),
            rootNorm: normalizeText(rootName)
          };
          if (!entry.leafId || !entry.leafNorm) {
            return;
          }
          index.push(entry);
          if (!byLeafName[entry.leafNorm]) {
            byLeafName[entry.leafNorm] = [];
          }
          byLeafName[entry.leafNorm].push(entry);
        });
      });
      return { index: index, byLeafName: byLeafName };
    }

    var catIndex = buildCatIndex();

    function resolveLeafByName(name) {
      var norm = normalizeText(name);
      var hits = catIndex.byLeafName[norm] || [];
      if (!hits.length) {
        // Fuzzy: leaf name contains or equals keyword target
        hits = catIndex.index.filter(function (c) {
          return c.leafNorm === norm || c.leafNorm.indexOf(norm) !== -1 || norm.indexOf(c.leafNorm) !== -1;
        });
      }
      if (!hits.length) {
        return null;
      }
      if (hits.length === 1) {
        return hits[0];
      }
      // Prefer Home over Phones for duplicate "Home Appliances"
      var preferred = hits.filter(function (h) {
        return /home/i.test(h.rootName) && !/phone/i.test(h.rootName);
      });
      return preferred[0] || hits[0];
    }

    function scoreTitleSuggestions(title) {
      var raw = normalizeText(title);
      if (raw.length < 3) {
        return [];
      }
      var stop = {
        a: 1, an: 1, the: 1, and: 1, or: 1, for: 1, with: 1, from: 1, to: 1,
        of: 1, in: 1, on: 1, at: 1, new: 1, used: 1, sale: 1, sell: 1, selling: 1,
        buy: 1, free: 1, png: 1, good: 1, great: 1, nice: 1, brand: 1, model: 1
      };
      var tokens = raw.split(' ').filter(function (t) {
        return t.length > 1 && !stop[t];
      });
      if (!tokens.length) {
        return [];
      }
      var scores = {};

      function bump(entry, pts) {
        if (!entry || !entry.leafId) {
          return;
        }
        var key = String(entry.leafId);
        if (!scores[key]) {
          scores[key] = { entry: entry, score: 0 };
        }
        scores[key].score += pts;
      }

      // Keyword / brand dictionary (multi-word first)
      Object.keys(keywords).forEach(function (kw) {
        var kn = normalizeText(kw);
        if (!kn || kn.length < 2) {
          return;
        }
        var hit = false;
        if (kn.indexOf(' ') !== -1) {
          hit = raw.indexOf(kn) !== -1;
        } else {
          hit = tokens.indexOf(kn) !== -1 || new RegExp('(?:^|\\s)' + kn.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(?:\\s|$)').test(raw);
        }
        if (!hit) {
          return;
        }
        var target = resolveLeafByName(keywords[kw]);
        // Longer / more specific keywords score higher
        bump(target, 40 + Math.min(20, kn.length));
      });

      catIndex.index.forEach(function (entry) {
        // Full subcategory name in title
        if (entry.leafNorm.length >= 3 && raw.indexOf(entry.leafNorm) !== -1) {
          bump(entry, 55);
        }
        var leafParts = entry.leafNorm.split(' ').filter(function (p) {
          return p.length > 2 && !stop[p];
        });
        leafParts.forEach(function (p) {
          if (tokens.indexOf(p) !== -1) {
            bump(entry, p.length >= 5 ? 18 : 12);
          }
        });
        // Root name token match (weaker)
        var rootParts = entry.rootNorm.split(/[\s&]+/).filter(function (p) {
          return p.length > 3 && !stop[p];
        });
        rootParts.forEach(function (p) {
          if (tokens.indexOf(p) !== -1) {
            bump(entry, 6);
          }
        });
      });

      return Object.keys(scores)
        .map(function (k) { return scores[k]; })
        .filter(function (row) { return row.score >= 12; })
        .sort(function (a, b) { return b.score - a.score; })
        .slice(0, 3)
        .map(function (row) { return row.entry; });
    }

    function renderCategorySuggestions(title) {
      var key = normalizeText(title);
      if (key === lastSuggestKey) {
        return;
      }
      lastSuggestKey = key;
      var suggestions = scoreTitleSuggestions(title);
      $suggestChips.empty();
      if (!suggestions.length) {
        $suggestBox.prop('hidden', true).attr('hidden', 'hidden');
        return;
      }
      var selectedLeaf = String($catId.val() || $sub.val() || '');
      suggestions.forEach(function (s) {
        var label = s.rootName + ' › ' + s.leafName;
        var $btn = $('<button type="button" class="pngm-post-suggest-chip" role="listitem"/>')
          .attr({
            'data-root-id': s.rootId,
            'data-root-name': s.rootName,
            'data-leaf-id': s.leafId,
            'data-leaf-name': s.leafName
          })
          .text(label);
        if (selectedLeaf && String(s.leafId) === selectedLeaf) {
          $btn.addClass('is-selected');
        }
        $suggestChips.append($btn);
      });
      $suggestBox.prop('hidden', false).removeAttr('hidden');
    }

    function scheduleCategorySuggestions() {
      window.clearTimeout(suggestTimer);
      suggestTimer = window.setTimeout(function () {
        renderCategorySuggestions($form.find('input[name^="title"]').val() || '');
      }, 180);
    }

    function applyCategorySuggestion(rootId, rootName, leafId, leafName) {
      rootId = parseInt(rootId, 10) || 0;
      leafId = parseInt(leafId, 10) || 0;
      if (!rootId || !leafId) {
        return;
      }
      $catId.val(String(leafId));
      setRoot(rootId, rootName, true, false);
      fillSubcats(rootId, leafId);
      $sub.val(String(leafId));
      syncCatFromSubcategory();
      updateSubcatPickedUI(leafName || ($sub.find('option:selected').text() || ''));
      $suggestChips.find('.pngm-post-suggest-chip').removeClass('is-selected');
      $suggestChips.find('.pngm-post-suggest-chip[data-leaf-id="' + leafId + '"]').addClass('is-selected');
      updateSummary(rootName, leafName || ($sub.find('option:selected').text() || ''));
    }

    function usesPostSubcatSheet() {
      return !(window.matchMedia && window.matchMedia('(min-width: 768px)').matches);
    }

    function subcatRows(rootId) {
      return map[String(rootId)] || map[rootId] || [];
    }

    function buildSubcatButtons(rootId, selectedLeaf, intoSheet) {
      var list = subcatRows(rootId);
      var html = '';
      list.forEach(function (row) {
        var selected = selectedLeaf && String(row.id) === String(selectedLeaf);
        html += '<button type="button" class="pngm-post-subcat-item' + (selected ? ' is-selected' : '') + '"'
          + ' data-leaf-id="' + row.id + '"'
          + ' data-leaf-name="' + String(row.name || '').replace(/"/g, '&quot;') + '"'
          + ' role="option" aria-selected="' + (selected ? 'true' : 'false') + '">'
          + '<span>' + $('<div/>').text(row.name || '').html() + '</span>'
          + '</button>';
      });
      if (intoSheet) {
        $sheetBody.html(html || '<p class="pngm-post-muted">' + (labels.selectSub || 'No subcategories') + '</p>');
      } else {
        $subList.html(html);
      }
    }

    function updateSubcatPickedUI(leafName) {
      var hasLeaf = !!String($sub.val() || $catId.val() || '').trim();
      var name = leafName || ($sub.find('option:selected').text() || '').trim();
      if (hasLeaf && name && name !== (labels.selectSub || 'Select a subcategory')) {
        $subPicked.text(name).prop('hidden', false).removeAttr('hidden');
        $subChange.prop('hidden', false).removeAttr('hidden');
        $subList.addClass('is-collapsed');
      } else {
        $subPicked.text('').prop('hidden', true).attr('hidden', 'hidden');
        $subChange.prop('hidden', true).attr('hidden', 'hidden');
        $subList.removeClass('is-collapsed');
      }
    }

    function closePostSubcatSheet() {
      if (!sheetOpen) {
        return;
      }
      sheetOpen = false;
      $sheet.removeClass('is-open').prop('hidden', true).attr('hidden', 'hidden');
      $sheetBackdrop.removeClass('is-open').prop('hidden', true).attr('hidden', 'hidden');
      document.body.style.overflow = '';
    }

    function openPostSubcatSheet(rootId, rootName) {
      var title = rootName || ($('.pngm-post-cat-card.is-selected').data('root-name') || labels.pickSubcategory || 'Choose a subcategory');
      $sheetTitle.text(title);
      buildSubcatButtons(rootId, $sub.val() || $catId.val(), true);
      $sheet.prop('hidden', false).removeAttr('hidden');
      $sheetBackdrop.prop('hidden', false).removeAttr('hidden');
      // Force reflow so the slide-up transition runs.
      void $sheet[0].offsetHeight;
      $sheet.addClass('is-open');
      $sheetBackdrop.addClass('is-open');
      document.body.style.overflow = 'hidden';
      sheetOpen = true;
    }

    function openPostSubcatPicker(rootId, rootName, forceSheet) {
      rootId = parseInt(rootId, 10) || 0;
      if (!rootId) {
        return;
      }
      if (forceSheet || usesPostSubcatSheet()) {
        openPostSubcatSheet(rootId, rootName);
        return;
      }
      closePostSubcatSheet();
      buildSubcatButtons(rootId, $sub.val() || $catId.val(), false);
      $subList.removeClass('is-collapsed');
      if ($sub.val()) {
        var picked = ($sub.find('option:selected').text() || '').trim();
        if (picked) {
          $subPicked.text(picked).prop('hidden', false).removeAttr('hidden');
        }
        $subChange.prop('hidden', false).removeAttr('hidden');
      } else {
        $subPicked.text('').prop('hidden', true).attr('hidden', 'hidden');
        $subChange.prop('hidden', true).attr('hidden', 'hidden');
      }
    }

    function pickSubcategory(leafId, leafName) {
      leafId = parseInt(leafId, 10) || 0;
      if (!leafId) {
        return;
      }
      if (!$sub.find('option[value="' + leafId + '"]').length) {
        var rootId = parseInt($root.val(), 10) || 0;
        fillSubcats(rootId, leafId);
      }
      $sub.val(String(leafId));
      syncCatFromSubcategory();
      $catId.trigger('change');
      buildSubcatButtons($root.val(), leafId, false);
      updateSubcatPickedUI(leafName || ($sub.find('option:selected').text() || ''));
      $suggestChips.find('.pngm-post-suggest-chip').removeClass('is-selected');
      $suggestChips.find('.pngm-post-suggest-chip[data-leaf-id="' + leafId + '"]').addClass('is-selected');
      closePostSubcatSheet();
      clearSubcategoryWarning();
      clearErrorFor($sub);
      clearErrorFor($form.find('.pngm-post-cat-grid'));
      hideFieldError($form.find('.pngm-post-field-error[data-for="catId"]'));
    }

    function fillSubcats(rootId, selectedLeaf) {
      var list = subcatRows(rootId);
      $sub.empty().append($('<option/>').val('').text(labels.selectSub || 'Select a subcategory'));
      list.forEach(function (row) {
        $sub.append($('<option/>').val(row.id).text(row.name));
      });
      if (selectedLeaf) {
        $sub.val(String(selectedLeaf));
      }
      $subWrap.toggleClass('is-hidden', !rootId);
      buildSubcatButtons(rootId, selectedLeaf || $sub.val(), false);
      updateSubcatPickedUI();
    }

    function setRoot(rootId, rootName, keepLeaf, openPicker) {
      rootId = parseInt(rootId, 10) || 0;
      if (typeof openPicker === 'undefined') {
        openPicker = !keepLeaf;
      }
      $root.val(rootId || '');
      $('.pngm-post-cat-card').removeClass('is-selected').attr('aria-selected', 'false');
      var $card = $('.pngm-post-cat-card[data-root-id="' + rootId + '"]');
      $card.addClass('is-selected').attr('aria-selected', 'true');
      fillSubcats(rootId, keepLeaf ? $catId.val() : '');
      if (!keepLeaf) {
        $catId.val('');
        $sub.val('');
        updateSubcatPickedUI('');
      }
      clearErrorFor($form.find('.pngm-post-cat-grid'));
      $form.find('.pngm-post-cat-grid').removeClass('is-invalid');
      hideFieldError($form.find('.pngm-post-field-error[data-for="category"]'));
      clearSubcategoryWarning();
      updateSummary(rootName || ($card.data('root-name') || ''), keepLeaf && $sub.val() ? ($sub.find('option:selected').text() || '') : '—');
      if (keepLeaf && $sub.val()) {
        updateSummary(null, $sub.find('option:selected').text());
        updateSubcatPickedUI($sub.find('option:selected').text());
      }
      if (rootId && openPicker) {
        openPostSubcatPicker(rootId, rootName || ($card.data('root-name') || ''), false);
      }
    }

    /**
     * Category attributes (Make, Seats, ...) are rendered server-side for the
     * category known at page load. The wizard picks the category in step 1, so
     * reload the plugin fields whenever that choice changes — but never wipe
     * values the seller already entered when they only press Back/Next.
     */
    var attrsLoadedFor = String(cfg.leaf || '');
    var attrsRequest = null;
    var attrsDraft = {};

    function attrsDraftKey($el) {
      var name = String($el.attr('name') || '');
      var id = String($el.attr('id') || '');
      if (name) {
        return 'n:' + name;
      }
      if (id) {
        return 'i:' + id;
      }
      return '';
    }

    function captureAttrsDraft() {
      var $box = $('#post-hooks');
      if (!$box.length) {
        return;
      }
      var next = {};
      var present = {};
      $box.find('input, select, textarea').each(function () {
        var $el = $(this);
        var key = attrsDraftKey($el);
        if (!key) {
          return;
        }
        var type = String($el.attr('type') || '').toLowerCase();
        if (type === 'file' || type === 'button' || type === 'submit') {
          return;
        }
        // Keep named hiddens (Attributes cascade often stores atr_* there).
        if (type === 'hidden' && !$el.attr('name')) {
          return;
        }
        present[key] = true;
        if ($el.is(':checkbox')) {
          if (!next[key]) {
            next[key] = [];
          }
          if ($el.is(':checked')) {
            next[key].push(String($el.val()));
          }
        } else if ($el.is(':radio')) {
          if ($el.is(':checked')) {
            next[key] = String($el.val());
          } else if (!Object.prototype.hasOwnProperty.call(next, key)) {
            next[key] = '';
          }
        } else {
          next[key] = String($el.val() == null ? '' : $el.val());
        }
      });
      // Update keys currently in the DOM; keep cascade keys that were temporarily removed.
      Object.keys(present).forEach(function (k) {
        attrsDraft[k] = Object.prototype.hasOwnProperty.call(next, k) ? next[k] : '';
      });
    }

    function applyAttrsDraftPass() {
      var $box = $('#post-hooks');
      if (!$box.length || !attrsDraft || !Object.keys(attrsDraft).length) {
        return;
      }
      $box.find('input, select, textarea').each(function () {
        var $el = $(this);
        var key = attrsDraftKey($el);
        if (!key || !Object.prototype.hasOwnProperty.call(attrsDraft, key)) {
          return;
        }
        var saved = attrsDraft[key];
        if ($el.is(':checkbox')) {
          var list = $.isArray(saved) ? saved : [saved];
          $el.prop('checked', list.indexOf(String($el.val())) !== -1);
        } else if ($el.is(':radio')) {
          $el.prop('checked', String($el.val()) === String(saved));
        } else {
          var cur = String($el.val() == null ? '' : $el.val());
          var want = String(saved == null ? '' : saved);
          if (cur !== want) {
            $el.val(want);
          }
        }
      });
    }

    function applyAttrsDraft() {
      applyAttrsDraftPass();
      // Cascade Make/Brand: only fetch children when level-2 is missing.
      // Re-firing change when Ford/Everest already rendered causes atr-loading forever
      // (cascade AJAX is also runhook → used to re-enter this path).
      var $lvl1 = $('#post-hooks').find('select[data-level="1"]');
      if ($lvl1.length) {
        var needCascade = false;
        $lvl1.each(function () {
          var $s = $(this);
          if (!$s.val()) {
            return;
          }
          if ($s.nextAll('select[data-level]').length) {
            return;
          }
          needCascade = true;
          $s.trigger('change');
        });
        if (needCascade) {
          window.setTimeout(applyAttrsDraftPass, 350);
          window.setTimeout(applyAttrsDraftPass, 900);
        }
      }
      syncAttrsSectionVisibility();
    }

    function loadCategoryAttributes(catId, force) {
      var $box = $('#post-hooks');
      catId = String(catId || '');

      if (!$box.length || !catId) {
        return;
      }
      // Same category already in the DOM — keep seller input (Back/Next must not wipe).
      if (!force && catId === attrsLoadedFor) {
        return;
      }
      // Different category → drop previous attribute draft (do not copy old Make onto new cat).
      if (attrsLoadedFor && attrsLoadedFor !== catId) {
        attrsDraft = {};
      } else {
        // Same category forced reload — snapshot current values first.
        captureAttrsDraft();
      }
      attrsLoadedFor = catId;

      var base = cfg.ajaxUrl || (window.location.pathname + '?');
      var data = {
        page: 'ajax',
        action: 'runhook',
        hook: cfg.edit ? 'item_edit' : 'item_form',
        catId: catId
      };
      if (cfg.edit && cfg.itemId) {
        data.itemId = cfg.itemId;
      }

      if (attrsRequest && attrsRequest.abort) {
        attrsRequest.abort();
      }

      $box.addClass('is-loading');
      attrsRequest = $.ajax({
        url: base,
        type: 'POST',
        dataType: 'html',
        data: data
      }).done(function (html) {
        var markup = $.trim(html || '');
        if (markup && markup.charAt(0) === '{') {
          return; // JSON error payload — keep what we have
        }
        $box.html(markup);
        // Freshly loaded fields start neutral — red only after a failed Next.
        $box.find('.error').removeClass('error');
        $box.find('.is-invalid').removeClass('is-invalid');
        applyAttrsDraft();
        $(document).trigger('pngm:attrs-loaded');
        if (window.pngmItemValidation && window.pngmItemValidation.forceEnhanceValidator) {
          window.pngmItemValidation.forceEnhanceValidator();
        }
      }).always(function () {
        $box.removeClass('is-loading');
        attrsRequest = null;
      });
    }

    /**
     * Hide the whole attributes block (and Make/Brand tip) when this category
     * has no extra fields — e.g. Cameras with only Contact Phone (already removed).
     */
    function syncAttrsSectionVisibility() {
      var $wrap = $('#pngm-post-attrs-wrap');
      var $box = $('#post-hooks');
      var $hint = $wrap.find('.pngm-post-make-hint');
      if (!$wrap.length || !$box.length) {
        return;
      }

      var $fields = $box.find('.control-group.atr-field, .atr-field.control-group').filter(function () {
        var $g = $(this);
        if ($g.hasClass('atr-type-phone') || $g.hasClass('atr-type-divider')) {
          return false;
        }
        // Must have a real control — empty atr-form shells do not count.
        return $g.find('input, select, textarea').length > 0;
      });

      var hasFields = $fields.length > 0;
      $wrap.toggle(hasFields);
      if (hasFields) {
        $wrap.removeAttr('hidden').prop('hidden', false);
      } else {
        $wrap.attr('hidden', 'hidden').prop('hidden', true);
      }

      var hasMake = $fields.filter(function () {
        var id = String($(this).attr('id') || '').toLowerCase();
        var label = $.trim($(this).find('> label, .control-label').first().text()).toLowerCase();
        return /make|brand|model/.test(id + ' ' + label);
      }).length > 0;

      if ($hint.length) {
        $hint.toggle(hasMake);
        if (hasMake) {
          $hint.removeAttr('hidden').prop('hidden', false);
        } else {
          $hint.attr('hidden', 'hidden').prop('hidden', true);
        }
      }
    }

    function updateSummary(rootName, leafName, titleText) {
      if (rootName !== null && rootName !== undefined) {
        $('[data-sum="root"]').text(rootName || '—');
      }
      if (leafName !== null && leafName !== undefined) {
        $('[data-sum="leaf"]').text(leafName || '—');
      }
      if (titleText !== null && titleText !== undefined) {
        $('[data-sum="title"]').text(titleText || '—');
      }
    }

    function showStep(n) {
      step = Math.max(1, Math.min(total, n));
      $panels.each(function () {
        var s = parseInt(this.getAttribute('data-step'), 10);
        var on = s === step;
        this.hidden = !on;
        this.setAttribute('data-active', on ? '1' : '0');
        if (on) {
          this.removeAttribute('hidden');
        } else {
          this.setAttribute('hidden', 'hidden');
        }
        $(this).toggleClass('is-active', on);
        // Force visibility even if parent theme CSS fights [hidden]
        this.style.setProperty('display', on ? 'block' : 'none', 'important');
      });

      try {
        document.dispatchEvent(new CustomEvent('pngm:post-step', { detail: { step: step, total: total } }));
      } catch (err) {}

      $stepper.find('.pngm-post-step').each(function () {
        var s = parseInt(this.getAttribute('data-step'), 10);
        $(this).toggleClass('is-active', s === step).toggleClass('is-done', s < step);
        var $dot = $(this).find('.pngm-post-step-dot');
        if (s < step) {
          $dot.html('<i class="fas fa-check"></i>');
        } else {
          $dot.text(String(s));
        }
      });
      $stepper.find('.pngm-post-step-line').each(function (i) {
        $(this).toggleClass('is-done', i + 1 < step);
      });

      $back.prop('hidden', step === 1);
      $cancel.prop('hidden', step !== 1);
      $next.prop('hidden', step === total);
      $publish.prop('hidden', step !== total);
      if (step === total) {
        $preview.prop('hidden', false).removeAttr('hidden').show();
      } else {
        $preview.prop('hidden', true).attr('hidden', 'hidden').hide();
      }

      // Body class for step-specific layout (e.g. hide side chrome)
      var b = document.body;
      if (b) {
        b.className = b.className.replace(/\bpngm-wizard-step-\d+\b/g, '').replace(/\s+/g, ' ').trim();
        b.classList.add('pngm-wizard-step-' + step);
      }

      if (step === total) {
        buildReview();
      }

      try {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } catch (e) {
        window.scrollTo(0, 0);
      }
    }

    function clearFieldErrors() {
      summaryMessages = [];
      clearErrorSummary();
      $form.find('.pngm-post-field-error').each(function () {
        hideFieldError($(this));
      });
      $form.find('.pngm-post-field.is-invalid, .pngm-post-select.is-invalid, .pngm-post-upload-card.is-invalid, .pngm-post-price-field.is-invalid, .pngm-post-cat-grid.is-invalid, .pngm-post-phone-row.is-invalid, .control-group.is-invalid, input.is-invalid, textarea.is-invalid, select.is-invalid').removeClass('is-invalid');
    }

    function clearErrorSummary() {
      if (!$errorList.length) {
        return;
      }
      $errorList.empty().removeClass('is-visible').removeAttr('role').hide();
    }

    function renderErrorSummary(messages) {
      if (!$errorList.length) {
        return;
      }
      $errorList.empty();
      if (!messages || !messages.length) {
        $errorList.removeClass('is-visible').removeAttr('role').hide();
        return;
      }
      var heading = labels.fixErrors || 'Please fix the following:';
      $errorList.append($('<li class="pngm-post-errors-heading"/>').text(heading));
      var seen = {};
      messages.forEach(function (msg) {
        msg = String(msg || '').trim();
        if (!msg || seen[msg]) {
          return;
        }
        seen[msg] = true;
        $errorList.append($('<li/>').text(msg));
      });
      $errorList.addClass('is-visible').attr('role', 'alert').show();
    }

    function hideFieldError($err) {
      if (!$err || !$err.length) {
        return;
      }
      $err.removeClass('is-visible').addClass('is-hidden');
      $err.attr('hidden', 'hidden');
      $err.prop('hidden', true);
      $err.hide();
    }

    function showFieldError($err, msg) {
      if (!$err || !$err.length) {
        return;
      }
      if (msg) {
        $err.text(msg);
      }
      $err.removeClass('is-hidden').addClass('is-visible');
      $err.removeAttr('hidden');
      $err.prop('hidden', false);
      $err.show();
    }

    function clearSubcategoryWarning() {
      var $field = $sub.closest('.pngm-post-field, .pngm-post-subcat-wrap');
      $sub.removeClass('is-invalid');
      $field.removeClass('is-invalid');
      hideFieldError($field.find('.pngm-post-field-error'));
      hideFieldError($form.find('.pngm-post-field-error[data-for="catId"]'));
    }

    function syncCatFromSubcategory() {
      var id = String($sub.val() || '').trim();
      if (id) {
        $catId.val(id);
        clearSubcategoryWarning();
        updateSummary(null, $sub.find('option:selected').text() || '');
        loadCategoryAttributes(id);
      } else {
        // Clearing the placeholder must wipe the previous leaf (e.g. Cars).
        $catId.val('');
        updateSummary(null, '—');
        attrsLoadedFor = '';
        attrsDraft = {};
        $('#post-hooks').empty();
        syncAttrsSectionVisibility();
      }
      return id;
    }

    function countUploadedPhotos() {
      var count = 0;
      var seen = {};
      $('#photos .qq-upload-success .ajax_preview_img img, #photos li.qq-upload-success, #photos .qq-upload-list li.qq-upload-success, #uppy-gallery img, #photos .uppy-Dashboard-Item--complete img').each(function () {
        var key = this.getAttribute('src') || this.getAttribute('data-src') || this.getAttribute('qq-file-id') || this.id || '';
        if (this.tagName === 'LI') {
          key = 'li-' + (this.getAttribute('qq-file-id') || $(this).index());
        }
        if (!key || seen[key]) {
          return;
        }
        // Skip empty temp placeholders
        if (typeof key === 'string' && /uploads\/temp\/?$/.test(key)) {
          return;
        }
        seen[key] = true;
        count += 1;
      });
      // FineUploader also stores successful uploads as hidden inputs in some themes
      if (count < 1) {
        count = $('#photos input[name="photos[]"], #photos input[name^="photos["], input[name="ajax_photos[]"]').filter(function () {
          return $.trim($(this).val() || '') !== '';
        }).length;
      }
      return count;
    }

    function showError($el, msg) {
      if (!$el || !$el.length) {
        return;
      }
      if (msg) {
        summaryMessages.push(String(msg));
      }
      $el.addClass('is-invalid');
      var $wrap = $el.closest('.pngm-post-field, .pngm-post-subcat-wrap, .pngm-post-price-field, .pngm-post-upload-card, .control-group.atr-field, .atr-field, .pngm-post-cat-grid');
      if (!$wrap.length && $el.hasClass('pngm-post-upload-card')) {
        $wrap = $el;
      }
      if (!$wrap.length && $el.hasClass('pngm-post-cat-grid')) {
        $wrap = $el;
      }
      if ($wrap.length) {
        $wrap.addClass('is-invalid');
      }
      // Phone: red border is on the composite row, not the borderless input.
      if ($el.is('input[name="contactPhone"], input[name="sPhone"], #sPhone, #contactPhone')
          || $wrap.hasClass('phone')) {
        $wrap.find('.pngm-post-phone-row').addClass('is-invalid');
      }
      // Category grid: dedicated error slot under the cards (not the subcategory).
      if ($el.hasClass('pngm-post-cat-grid') || $wrap.hasClass('pngm-post-cat-grid')) {
        var $catErr = $form.find('.pngm-post-field-error[data-for="category"]');
        if (!$catErr.length) {
          $catErr = $('<p class="pngm-post-field-error" data-for="category" role="alert"/>');
          $form.find('.pngm-post-cat-grid').after($catErr);
        }
        showFieldError($catErr, msg);
        return;
      }
      // Never fall back to an error slot in another step — it would stay invisible.
      var $err = $wrap.find('.pngm-post-field-error').first();
      if (!$err.length && $wrap.length) {
        $err = $('<div class="pngm-post-field-error" role="alert"/>');
        $wrap.append($err);
      }
      if (!$err.length) {
        $err = $el.closest('.pngm-post-step-panel').find('.pngm-post-field-error').first();
      }
      showFieldError($err, msg);
    }

    /**
     * Drop the error state for one field as soon as it holds a valid value.
     */
    function clearErrorFor($el) {
      if (!$el || !$el.length) {
        return;
      }
      $el.removeClass('is-invalid error');
      var $wrap = $el.closest('.pngm-post-field, .pngm-post-subcat-wrap, .pngm-post-price-field, .pngm-post-upload-card, .pngm-post-terms-wrap, .control-group.atr-field, .atr-field, .pngm-post-cat-grid');
      if (!$wrap.length) {
        return;
      }
      $wrap.removeClass('is-invalid');
      $wrap.find('.is-invalid').removeClass('is-invalid');
      $wrap.find('.pngm-post-phone-row').removeClass('is-invalid');
      hideFieldError($wrap.find('.pngm-post-field-error').first());
    }

    /**
     * Scroll first invalid control into view and focus it (desktop + mobile).
     */
    function focusFirstInvalid() {
      renderErrorSummary(summaryMessages);

      var $bad = $form.find('.pngm-post-step-panel.is-active .is-invalid').filter(function () {
        var $el = $(this);
        if ($el.hasClass('is-hidden') || $el.attr('hidden') || !$el.is(':visible')) {
          return false;
        }
        return true;
      }).first();

      if (!$bad.length) {
        $bad = $form.find('.is-invalid:visible').first();
      }

      var $focus = $();
      if ($bad.length) {
        if ($bad.is('input, textarea, select, button')) {
          $focus = $bad;
        } else {
          $focus = $bad.find('input:visible, textarea:visible, select:visible, button:visible').not('[type="hidden"]').first();
        }
      }

      var $scrollTarget = $focus.length ? $focus : ($bad.length ? $bad : $errorList);
      if (!$scrollTarget.length) {
        return;
      }

      window.setTimeout(function () {
        var el = $scrollTarget.get(0);
        if (!el) {
          return;
        }
        try {
          if (typeof el.scrollIntoView === 'function') {
            el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
          } else {
            var top = $scrollTarget.offset();
            if (top) {
              $('html, body').stop(true).animate({ scrollTop: Math.max(0, top.top - 110) }, 220);
            }
          }
        } catch (err) {
          var top2 = $scrollTarget.offset();
          if (top2) {
            window.scrollTo(0, Math.max(0, top2.top - 110));
          }
        }

        if ($focus.length) {
          try {
            $focus.trigger('focus');
          } catch (e2) { /* ignore */ }
        }
      }, 50);
    }

    function attrGroupLabel($group) {
      var $lab = $group.find('> label.control-label, > .control-label').first();
      if (!$lab.length) {
        $lab = $group.find('label').first();
      }
      var text = $.trim($lab.clone().children('.req, .req *').remove().end().text().replace(/\*/g, ''));
      return text || (labels.requiredField || 'Required field');
    }

    /** Duplicate of Step 5 contact phone (Attributes plugin PHONE field). */
    function isContactPhoneAttribute($group) {
      if (!$group || !$group.length) {
        return false;
      }
      var cls = String($group.attr('class') || '').toLowerCase();
      if (cls.indexOf('atr-type-phone') !== -1) {
        return true;
      }
      var id = String($group.attr('id') || '').toLowerCase();
      if (id === 'atr-phone' || id.indexOf('atr-phone') === 0) {
        return true;
      }
      return /contact\s*phone|^phone(\s*number)?$/i.test(attrGroupLabel($group));
    }

    function isFilledSelect($sel) {
      var v = String($sel.val() || '');
      return v !== '' && v !== '0';
    }

    /**
     * Validate Attributes plugin fields marked required (*) in the current step.
     * Make/Brand cascade: levels 1–2 that are visible with options must be filled;
     * level 3+ stays optional (VEHICLE-03).
     */
    function validateRequiredAttributes($scope) {
      var ok = true;
      var $groups = $scope.find('.atr-form .control-group.atr-field, .atr-form .atr-field.control-group, #post-hooks .control-group.atr-field');
      $groups.each(function () {
        var $group = $(this);
        if (!$group.is(':visible')) {
          return;
        }
        if (isContactPhoneAttribute($group)) {
          return;
        }
        if (!$group.find('label .req, .control-label .req, span.req').length) {
          return;
        }

        var name = attrGroupLabel($group);
        var msgNeed = (labels.needAttr || 'Please complete: %s').replace('%s', name);
        var typeClass = ($group.attr('class') || '').toLowerCase();

        if (typeClass.indexOf('atr-type-radio') !== -1) {
          if (!$group.find('input[type="radio"]:checked').length) {
            showError($group.find('input[type="radio"]').first(), msgNeed);
            ok = false;
          }
          return;
        }

        if (typeClass.indexOf('atr-type-checkbox') !== -1) {
          if (!$group.find('input[type="checkbox"]:checked').length) {
            showError($group.find('input[type="checkbox"]').first(), msgNeed);
            ok = false;
          }
          return;
        }

        if (typeClass.indexOf('atr-type-select') !== -1) {
          var $selects = $group.find('select:visible');
          var missing = false;
          $selects.each(function () {
            var $sel = $(this);
            var level = parseInt($sel.attr('data-level'), 10) || 1;
            if (level >= 3) {
              return;
            }
            var hasOptions = $sel.find('option').filter(function () {
              return String($(this).val() || '') !== '';
            }).length > 0;
            if (!hasOptions) {
              return;
            }
            if (!isFilledSelect($sel)) {
              showError($sel, msgNeed);
              missing = true;
              return false;
            }
          });
          if (missing) {
            ok = false;
          }
          return;
        }

        // TEXT / NUMBER / PHONE / EMAIL / TEXTAREA / DATE
        var $inputs = $group.find('input:visible:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), textarea:visible');
        $inputs.each(function () {
          if (!$.trim($(this).val() || '')) {
            showError($(this), msgNeed);
            ok = false;
          }
        });
      });

      // "Other" free-text make/model when visible
      var $otherBox = $scope.find('#atr-make_other');
      if ($otherBox.length && ($otherBox.hasClass('pngm-other-visible') || $otherBox.is(':visible'))) {
        var $otherInp = $otherBox.find('input, textarea').filter(':visible').first();
        if ($otherInp.length && !$.trim($otherInp.val() || '')) {
          showError($otherInp, labels.needMakeOther || 'Please specify make / model.');
          ok = false;
        }
      }

      return ok;
    }

    function validateStep(n) {
      clearFieldErrors();
      $form.find('.control-group.is-invalid, .atr-field.is-invalid, .pngm-post-price-field.is-invalid').removeClass('is-invalid');
      if (n === 1) {
        var rootId = String($root.val() || '').trim();
        var leafId = String($sub.val() || '').trim();
        var $catGrid = $form.find('.pngm-post-cat-grid');
        var title = $form.find('input[name^="title"]').val() || '';
        var okStep1 = true;

        if ($.trim(title).length < 3) {
          showError($form.find('input[name^="title"]'), labels.needTitle);
          okStep1 = false;
        }

        // No category chosen — clear leaf state and only flag the category grid.
        if (!rootId || !$catGrid.find('.pngm-post-cat-card.is-selected').length) {
          $root.val('');
          $catId.val('');
          $sub.val('');
          $subWrap.addClass('is-hidden');
          updateSubcatPickedUI('');
          clearSubcategoryWarning();
          updateSummary('—', '—');
          showError($catGrid, labels.selectCategory || 'Please select a category.');
          okStep1 = false;
        } else {
          $subWrap.removeClass('is-hidden');
          // Placeholder / cleared subcategory must not keep a previous leaf id.
          if (!leafId) {
            $catId.val('');
            updateSummary(null, '—');
            updateSubcatPickedUI('');
            showError($subWrap, labels.selectSub || 'Please select a subcategory.');
            $sub.addClass('is-invalid');
            openPostSubcatPicker(parseInt(rootId, 10), $('.pngm-post-cat-card.is-selected').data('root-name') || '');
            okStep1 = false;
          } else {
            $catId.val(leafId);
            clearSubcategoryWarning();
            $catGrid.removeClass('is-invalid');
            hideFieldError($form.find('.pngm-post-field-error[data-for="category"]'));
            updateSummary(null, $sub.find('option:selected').text() || '');
          }
        }

        if (!okStep1) {
          focusFirstInvalid();
          return false;
        }
        return true;
      }
      if (n === 2) {
        var $step2 = $form.find('.pngm-post-step-panel[data-step="2"]');
        var desc = $form.find('textarea[name^="description"]').val() || '';
        var ok = true;
        var descTrim = $.trim(desc);
        if (descTrim.length < 1) {
          showError($form.find('textarea[name^="description"]'), labels.needDesc);
          ok = false;
        }

        var $priceField = $step2.find('.pngm-post-price-field');
        if ($priceField.length) {
          var mode = String($('input[name="pngm_price_mode"]:checked').val() || 'PAID');
          if (mode === 'PAID') {
            var priceRaw = String($('#price').val() || '').replace(/,/g, '').trim();
            var priceNum = parseFloat(priceRaw);
            if (!priceRaw || isNaN(priceNum) || priceNum <= 0) {
              showError($('#price').length ? $('#price') : $priceField, labels.needPrice || 'Please enter a price, or choose Check with seller.');
              ok = false;
            }
          }
        }

        if (!validateRequiredAttributes($step2)) {
          ok = false;
        }

        if (!ok) {
          focusFirstInvalid();
        }
        return ok;
      }
      if (n === 3) {
        // Photos are optional — sellers can continue / publish with none.
        return true;
      }
      if (n === 4) {
        var okLoc = true;
        var $region = $('#regionId');
        var $city = $('#cityId');
        if ($region.length && !$region.val() && !$('#sRegion').val()) {
          showError($region, labels.needRegion || 'Please select a province / region.');
          okLoc = false;
        }
        if ($city.length && !$city.val() && !$('#sCity').val()) {
          showError($city, labels.needCity || 'Please select a city / town.');
          okLoc = false;
        }
        if (!okLoc) {
          focusFirstInvalid();
        }
        return okLoc;
      }
      if (n === 5) {
        var okContact = true;
        var $name = $form.find('input[name="contactName"]');
        var $phone = $form.find('input[name="contactPhone"], input[name="sPhone"], #sPhone, #contactPhone').first();
        var $email = $form.find('input[name="contactEmail"]');
        if ($name.length && $.trim($name.val() || '').length < 2) {
          showError($name, labels.needName || 'Please enter your name.');
          okContact = false;
        }
        if ($phone.length && $.trim($phone.val() || '').replace(/^\+?675\s*/, '').length < 6) {
          showError($phone, labels.needPhone || 'Please enter a phone number.');
          okContact = false;
        }
        if ($('#pngm_email_notify').is(':checked') && $email.length && $.trim($email.val() || '').indexOf('@') < 1) {
          showError($email, labels.needEmail || 'Please enter a valid email.');
          okContact = false;
        }
        if (!okContact) {
          focusFirstInvalid();
        }
        return okContact;
      }
      if (n === 6) {
        var okReview = true;
        if (!$('#pngm_terms').is(':checked')) {
          showError($('#pngm_terms'), labels.needTerms);
          var $termsErr = $form.find('.pngm-post-field-error[data-for="pngm_terms"]');
          showFieldError($termsErr, labels.needTerms);
          okReview = false;
        }

        // Block publish until reCAPTCHA is completed (do not POST and fail later).
        var $captchaWidgets = $form.find('.g-recaptcha, [data-pngm-recaptcha]');
        if ($captchaWidgets.length) {
          try {
            document.dispatchEvent(new CustomEvent('pngm:post-step'));
          } catch (errCap) {}
          var captchaToken = '';
          $form.find('textarea[name="g-recaptcha-response"]').each(function () {
            var v = $.trim($(this).val() || '');
            if (v) {
              captchaToken = v;
            }
          });
          if (!captchaToken) {
            var anyTa = document.querySelector('textarea[name="g-recaptcha-response"]');
            if (anyTa && anyTa.value) {
              captchaToken = String(anyTa.value).trim();
            }
          }
          var $captchaWrap = $form.find('.pngm-post-captcha');
          var $captchaErr = $form.find('.pngm-post-field-error[data-for="pngm_captcha"]');
          var captchaMsg = labels.needCaptcha || 'Please complete the reCAPTCHA before publishing.';
          if (!captchaToken) {
            $captchaWrap.addClass('is-missing-captcha');
            showFieldError($captchaErr, captchaMsg);
            if (typeof window.pngmShowToast === 'function') {
              // Soft green toast (same as login) — not the red is-error chrome.
              var host = document.getElementById('pngm-loc-toast-host');
              if (!host) {
                host = document.createElement('div');
                host.id = 'pngm-loc-toast-host';
                host.className = 'pngm-loc-toast-host';
                host.setAttribute('aria-live', 'assertive');
                document.body.appendChild(host);
              }
              var existing = host.querySelector('.pngm-captcha-toast');
              if (existing && existing.parentNode) {
                existing.parentNode.removeChild(existing);
              }
              var toast = document.createElement('div');
              toast.className = 'pngm-loc-toast pngm-captcha-toast';
              toast.setAttribute('role', 'alert');
              toast.innerHTML =
                '<span class="pngm-loc-toast-msg"></span>' +
                '<button type="button" class="pngm-loc-toast-close" aria-label="Dismiss">&times;</button>';
              toast.querySelector('.pngm-loc-toast-msg').textContent = captchaMsg;
              host.appendChild(toast);
              function dismissCapToast() {
                if (toast.parentNode) {
                  toast.parentNode.removeChild(toast);
                }
              }
              toast.querySelector('.pngm-loc-toast-close').addEventListener('click', dismissCapToast);
              window.setTimeout(dismissCapToast, 4800);
            }
            okReview = false;
          } else {
            $captchaWrap.removeClass('is-missing-captcha is-error is-invalid');
            hideFieldError($captchaErr);
          }
        }

        if (!okReview) {
          focusFirstInvalid();
          var $cap = $form.find('.pngm-post-captcha.is-missing-captcha').first();
          if ($cap.length && typeof $cap[0].scrollIntoView === 'function') {
            $cap[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return false;
        }
        return true;
      }
      return true;
    }

    function applyPriceMode(mode) {
      var $enter = $('#pngm-price-enter');
      if (!$enter.length) {
        $enter = $('.pngm-post-price-enter');
      }
      var $price = $('#price');
      mode = String(mode || 'PAID');
      if (mode !== 'CHECK' && mode !== 'FREE') {
        mode = 'PAID';
      }

      if (mode === 'PAID') {
        $enter.removeClass('is-disabled is-hidden-mode');
        $enter.removeAttr('hidden');
        $enter.prop('hidden', false);
        $enter.css('display', '');
        if (String($price.val()) === '0') {
          $price.val('');
        }
      } else {
        // CHECK with seller or FREE — hide amount field
        $enter.addClass('is-disabled is-hidden-mode');
        $enter.attr('hidden', 'hidden');
        $enter.prop('hidden', true);
        $enter.css('display', 'none');
        if (mode === 'FREE') {
          $price.val('0');
        } else {
          $price.val('');
        }
      }
    }

    function syncTransaction(val) {
      val = String(val);
      if (val === '0') {
        $txNative.val('');
        applyPriceMode('FREE');
        return;
      }
      $txNative.val(val);
      var mode = $('input[name="pngm_price_mode"]:checked').val() || 'PAID';
      applyPriceMode(mode);
    }

    function buildReview() {
      var title = $.trim($form.find('input[name^="title"]').val() || '');
      var priceVal = $('#price').val();
      var mode = $('input[name="pngm_price_mode"]:checked').val() || 'PAID';
      var priceText = labels.checkSeller || 'Check with seller';
      if (mode === 'FREE' || priceVal === '0') {
        priceText = labels.free || 'Free';
      } else if (mode === 'PAID' && priceVal) {
        var cur = $('#currency option:selected').text() || $('#currency').val() || '';
        priceText = priceVal + (cur ? ' ' + cur : '');
      }

      $('#pngm-review-title').text(title || '—');
      $('#pngm-review-price').text(priceText);

      var meta = [];
      meta.push([labels.category || 'Category', $('[data-sum="root"]').first().text()]);
      meta.push([labels.subcategory || 'Subcategory', $('[data-sum="leaf"]').first().text()]);
      var condText = $.trim($('#sCondition option:selected').text() || '');
      if (!condText || /select/i.test(condText)) {
        condText = '—';
      }
      meta.push([labels.condition || 'Condition', condText]);
      var $txPill = $('.pngm-post-pills[data-pills="transaction"] .pngm-post-pill.is-selected');
      var txLabel = $.trim($txPill.text() || '');
      if (!txLabel) {
        txLabel = $.trim($('#sTransaction option:selected').text() || '') || '—';
      }
      meta.push([labels.transaction || 'Transaction', txLabel]);
      var attrRows = collectAttributeRows();
      attrRows.forEach(function (row) {
        meta.push(row);
      });
      var locParts = [];
      var city = $('#cityId option:selected').text() || $('#city').val() || '';
      var region = $('#regionId option:selected').text() || $('#region').val() || '';
      var addr = $('#address').val() || '';
      if (addr) {
        locParts.push(addr);
      }
      if (city && city.indexOf('Select') === -1) {
        locParts.push(city);
      }
      if (region && region.indexOf('Select') === -1) {
        locParts.push(region);
      }
      meta.push([labels.location || 'Location', locParts.join(', ') || '—']);
      var postedBy = $.trim($form.find('input[name="contactName"]').val() || '');
      meta.push([labels.postedBy || 'Posted by', postedBy || '—']);
      var phone = $.trim($form.find('input[name="contactPhone"], input[name="sPhone"], #sPhone, #contactPhone').first().val() || '');
      phone = phone.replace(/^\+?675\s*/, '');
      meta.push([labels.phone || 'Phone', phone ? ('+675 ' + phone) : '—']);
      meta.push([labels.whatsapp || 'WhatsApp', $('#pngm_whatsapp').is(':checked') ? (labels.yes || 'Yes') : (labels.no || 'No')]);

      var $meta = $('#pngm-review-meta').empty();
      meta.forEach(function (row) {
        $meta.append('<div><dt>' + $('<div/>').text(row[0]).html() + '</dt><dd>' + $('<div/>').text(row[1]).html() + '</dd></div>');
      });

      var $cover = $('#pngm-review-cover').empty();
      var $thumbs = $('#pngm-review-thumbs').empty();
      var imgs = [];
      $('#photos .qq-upload-success .ajax_preview_img img, #photos .qq-upload-list img, #uppy-gallery img').each(function () {
        var src = this.getAttribute('src') || this.getAttribute('data-src') || '';
        if (!src || src.indexOf('data:') === 0) {
          return;
        }
        if (imgs.indexOf(src) === -1) {
          imgs.push(src);
        }
      });

      if (imgs.length) {
        $cover.append($('<img/>').attr({ src: imgs[0], alt: title }));
        $cover.append(
          '<button type="button" class="pngm-photo-view" title="View photo" aria-label="View photo">' +
            '<i class="far fa-eye" aria-hidden="true"></i>' +
          '</button>'
        );
        imgs.slice(0, 8).forEach(function (src, idx) {
          var $btn = $('<button type="button" class="pngm-post-review-thumb"/>').append($('<img/>').attr('src', src));
          if (idx === 0) {
            $btn.addClass('is-active');
          }
          $btn.on('click', function () {
            if ($btn.hasClass('is-active') && typeof window.pngmOpenPhotoViewer === 'function') {
              window.pngmOpenPhotoViewer(src);
              return;
            }
            $thumbs.find('.pngm-post-review-thumb').removeClass('is-active');
            $btn.addClass('is-active');
            $cover.find('img').attr('src', src);
          });
          $thumbs.append($btn);
        });
      } else {
        $cover.append('<span class="pngm-post-review-placeholder">' + (labels.noPhoto || 'No photo yet') + '</span>');
      }

      var missing = [];
      if (!$catId.val()) {
        missing.push(labels.selectSub || 'Select a subcategory');
      }
      if ($.trim(title).length < 3) {
        missing.push(labels.needTitle || 'Title required');
      }
      if (!$('#regionId').val() && !$('#sRegion').val()) {
        missing.push(labels.needRegion || 'Select a province / region');
      }
      if ($('#cityId').length && !$('#cityId').val() && !$('#sCity').val()) {
        missing.push(labels.needCity || 'Select a city / town');
      }
      if ($.trim($form.find('input[name="contactName"]').val() || '').length < 2) {
        missing.push(labels.needName || 'Full name required');
      }
      if ($.trim($form.find('input[name="contactPhone"], input[name="sPhone"], #sPhone, #contactPhone').first().val() || '').replace(/^\+?675\s*/, '').length < 6) {
        missing.push(labels.needPhone || 'Phone number required');
      }
      var $box = $('#pngm-missing-box');
      var $list = $('#pngm-missing-list').empty();
      if (missing.length) {
        missing.forEach(function (m) {
          $list.append($('<li/>').text(m));
        });
        $box.prop('hidden', false).removeAttr('hidden');
      } else {
        $box.prop('hidden', true).attr('hidden', 'hidden');
      }
      var $photoTip = $('#pngm-photo-recommend');
      if ($photoTip.length) {
        if (countUploadedPhotos() < 1) {
          $photoTip.prop('hidden', false).removeAttr('hidden');
        } else {
          $photoTip.prop('hidden', true).attr('hidden', 'hidden');
        }
      }
    }

    function updateMapPreview(coords) {
      var $map = $('#pngm-post-map');
      var $frame = $('#pngm-post-map-frame');
      var $placeholder = $('#pngm-post-map-placeholder');
      if (!$map.length || !$frame.length) {
        return;
      }

      var q = '';
      if (coords && coords.lat != null && coords.lng != null) {
        q = String(coords.lat) + ',' + String(coords.lng);
      } else {
        var parts = [];
        var addr = $.trim($('#address').val() || '');
        var city = $.trim($('#cityId option:selected').text() || $('#city').val() || '');
        var region = $.trim($('#regionId option:selected').text() || $('#region').val() || '');
        var country = $.trim($('#countryId option:selected').text() || $('#country').val() || 'Papua New Guinea');
        if (addr) {
          parts.push(addr);
        }
        if (city && !/^select/i.test(city)) {
          parts.push(city);
        }
        if (region && !/^select/i.test(region)) {
          parts.push(region);
        }
        if (country && !/^select/i.test(country)) {
          parts.push(country);
        }
        q = parts.join(', ');
      }

      if (!q) {
        $frame.attr('src', 'about:blank').attr('hidden', 'hidden').prop('hidden', true);
        $map.removeClass('has-map is-located');
        $placeholder.show();
        return;
      }

      var src = 'https://maps.google.com/maps?q=' + encodeURIComponent(q) + '&z=13&output=embed';
      $frame.attr('src', src).removeAttr('hidden').prop('hidden', false);
      $map.addClass('has-map');
      $placeholder.hide();
    }

    function collectAttributeRows() {
      var rows = [];
      var seen = {};
      var $groups = $form.find('#post-hooks .control-group.atr-field, #post-hooks .atr-field.control-group, .atr-form .control-group.atr-field');
      $groups.each(function () {
        var $group = $(this);
        // Do not require :visible — Details step is hidden on Review/Preview.
        var name = attrGroupLabel($group);
        if (!name || seen[name]) {
          return;
        }
        // Contact phone is Step 5 only — skip Attributes PHONE duplicates.
        if (isContactPhoneAttribute($group) || /contact\s*phone/i.test(name)) {
          return;
        }
        var value = '';
        var typeClass = String($group.attr('class') || '');

        if (typeClass.indexOf('atr-type-radio') !== -1) {
          var $checked = $group.find('input[type="radio"]:checked');
          if ($checked.length) {
            var $lab = $group.find('label[for="' + $checked.attr('id') + '"]');
            value = $.trim($lab.text() || $checked.val() || '');
          }
        } else if (typeClass.indexOf('atr-type-checkbox') !== -1) {
          var parts = [];
          $group.find('input[type="checkbox"]:checked').each(function () {
            var $c = $(this);
            var $l = $group.find('label[for="' + $c.attr('id') + '"]');
            var t = $.trim($l.text() || $c.val() || '');
            if (t) {
              parts.push(t);
            }
          });
          value = parts.join(', ');
        } else if ($group.find('select').length) {
          var $sel = $group.find('select').first();
          var sv = String($sel.val() || '');
          var st = $.trim($sel.find('option:selected').text() || '');
          if (sv && sv !== '0' && st && !/^select/i.test(st)) {
            value = st;
          }
        } else {
          var $inp = $group.find('input[type="text"], input[type="number"], input[type="tel"], textarea').first();
          if ($inp.length) {
            value = $.trim(String($inp.val() || ''));
          }
        }

        if (!value) {
          return;
        }
        seen[name] = true;
        rows.push([name, value]);
      });

      // Custom make / model "Other" text box
      var $other = $form.find('#atr-make_other input, input[name*="make_other"], #make_other').first();
      if ($other.length) {
        var otherVal = $.trim(String($other.val() || ''));
        if (otherVal) {
          rows.push([labels.makeOther || 'Make / Model', otherVal]);
        }
      }

      return rows;
    }

    function collectPreviewData() {
      var title = $.trim($form.find('input[name^="title"]').val() || '');
      var desc = $.trim($form.find('textarea[name^="description"]').val() || '');
      var priceVal = $('#price').val();
      var mode = $('input[name="pngm_price_mode"]:checked').val() || 'PAID';
      var priceText = labels.checkSeller || 'Check with seller';
      if (mode === 'FREE' || priceVal === '0') {
        priceText = labels.free || 'Free';
      } else if (mode === 'PAID' && priceVal) {
        var cur = $('#currency option:selected').text() || $('#currency').val() || '';
        priceText = priceVal + (cur ? ' ' + cur : '');
      }

      var $txPill = $('.pngm-post-pills[data-pills="transaction"] .pngm-post-pill.is-selected');
      var txLabel = $.trim($txPill.text() || '') || $.trim($('#sTransaction option:selected').text() || '') || '—';
      if (!txLabel || /^select/i.test(txLabel)) {
        txLabel = '—';
      }

      var condText = $.trim($('#sCondition option:selected').text() || '');
      if (!condText || /select/i.test(condText)) {
        condText = '—';
      }

      var city = $.trim($('#cityId option:selected').text() || $('#city').val() || '');
      var region = $.trim($('#regionId option:selected').text() || $('#region').val() || '');
      var addr = $.trim($('#address').val() || '');
      var locParts = [];
      if (addr) {
        locParts.push(addr);
      }
      if (city && !/^select/i.test(city)) {
        locParts.push(city);
      }
      if (region && !/^select/i.test(region)) {
        locParts.push(region);
      }

      var phone = $.trim($form.find('input[name="contactPhone"], input[name="sPhone"], #sPhone, #contactPhone').first().val() || '').replace(/^\+?675\s*/, '');
      var imgs = [];
      $('#photos .qq-upload-success .ajax_preview_img img, #photos .qq-upload-list img, #uppy-gallery img').each(function () {
        var src = this.getAttribute('src') || this.getAttribute('data-src') || '';
        if (src && src.indexOf('data:') !== 0 && imgs.indexOf(src) === -1) {
          imgs.push(src);
        }
      });

      return {
        title: title || (labels.untitled || 'Untitled listing'),
        desc: desc,
        priceText: priceText,
        category: $('[data-sum="root"]').first().text() || '—',
        subcategory: $('[data-sum="leaf"]').first().text() || '—',
        condition: condText,
        transaction: txLabel,
        location: locParts.join(', ') || '—',
        name: $.trim($form.find('input[name="contactName"]').val() || '') || '—',
        phone: phone,
        whatsapp: $('#pngm_whatsapp').is(':checked'),
        allowMessages: $('#pngm_allow_messages').is(':checked'),
        contactPref: $('#pngm_contact_pref').val() || 'message',
        imgs: imgs,
        attributes: collectAttributeRows()
      };
    }

    function previewAttrIcon(name) {
      var hay = String(name || '').toLowerCase();
      var svgs = {
        car: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M5 16l1.5-6h11L19 16"/><path d="M3 16h18v2a1 1 0 0 1-1 1h-1a2 2 0 0 1-4 0H9a2 2 0 0 1-4 0H4a1 1 0 0 1-1-1v-2z"/><circle cx="7.5" cy="16" r="1.2"/><circle cx="16.5" cy="16" r="1.2"/></svg>',
        fuel: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="4" y="4" width="10" height="16" rx="1.5"/><path d="M14 8h2.5a2 2 0 0 1 2 2v5.5a1.5 1.5 0 0 0 3 0V9.5L19 7"/></svg>',
        seats: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="8" cy="7" r="2"/><circle cx="16" cy="7" r="2"/><path d="M4.5 19v-1.2A3.3 3.3 0 0 1 7.8 14.5h.4A3.3 3.3 0 0 1 11.5 17.8V19"/><path d="M12.5 19v-1.2A3.3 3.3 0 0 1 15.8 14.5h.4A3.3 3.3 0 0 1 19.5 17.8V19"/></svg>',
        cog: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M12 3.5v2.2M12 18.3v2.2M4.9 6.5l1.6 1.6M17.5 15.9l1.6 1.6M3.5 12h2.2M18.3 12h2.2M4.9 17.5l1.6-1.6M17.5 8.1l1.6-1.6"/></svg>',
        plus: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 8v8M8 12h8"/></svg>',
        clock: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="8.5"/><path d="M12 8v4.5l3 1.5"/></svg>',
        phone: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M7 3.5h3.2l1.1 3.3-2 1.4a12 12 0 0 0 5.5 5.5l1.4-2 3.3 1.1V16a2 2 0 0 1-2.2 2A15 15 0 0 1 5 5.7 2 2 0 0 1 7 3.5z"/></svg>',
        tag: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3.5 12.5V5.8A2.3 2.3 0 0 1 5.8 3.5h6.7L20.5 11.5l-7.8 7.8L3.5 12.5z"/><circle cx="8.2" cy="8.2" r="1.1"/></svg>'
      };
      var key = 'tag';
      if (/make|brand|body|model/.test(hay)) {
        key = 'car';
      } else if (/fuel/.test(hay)) {
        key = 'fuel';
      } else if (/seat/.test(hay)) {
        key = 'seats';
      } else if (/transmission|gear/.test(hay)) {
        key = 'cog';
      } else if (/accessor|feature/.test(hay)) {
        key = 'plus';
      } else if (/condition/.test(hay)) {
        key = 'clock';
      } else if (/phone/.test(hay)) {
        key = 'phone';
      }
      return svgs[key] || svgs.tag;
    }

    function openPreviewModal() {
      buildReview();
      var data = collectPreviewData();
      var $modal = $('#pngm-preview-modal');
      var $body = $('#pngm-preview-modal-body');
      if (!$modal.length || !$body.length) {
        return;
      }

      var esc = function (s) {
        return $('<div/>').text(s == null ? '' : String(s)).html();
      };

      var coverHtml = data.imgs.length
        ? '<img src="' + esc(data.imgs[0]) + '" alt="' + esc(data.title) + '" />'
        : '<div class="pngm-public-cover-empty">' + esc(labels.noPhoto || 'No photo yet') + '</div>';

      var thumbsHtml = data.imgs.map(function (src, idx) {
        return '<button type="button" class="pngm-public-thumb' + (idx === 0 ? ' is-active' : '') + '" data-src="' + esc(src) + '"><img src="' + esc(src) + '" alt="" /></button>';
      }).join('');

      var pref = String(data.contactPref || 'message');
      var contactBtns = '';
      if (data.allowMessages) {
        contactBtns += '<span class="pngm-public-contact-btn is-chat' + (pref === 'message' ? ' is-preferred' : '') + '">'
          + '<i class="fas fa-comment" aria-hidden="true"></i> ' + esc(labels.message || 'Chat')
          + (pref === 'message' ? '<em class="pngm-public-pref">' + esc(labels.preferred || 'PREFERRED') + '</em>' : '')
          + '</span>';
      }
      if (data.phone) {
        contactBtns += '<span class="pngm-public-contact-btn is-call' + (pref === 'call' ? ' is-preferred' : '') + '">'
          + '<i class="fas fa-phone" aria-hidden="true"></i> ' + esc(labels.call || 'Call')
          + (pref === 'call' ? '<em class="pngm-public-pref">' + esc(labels.preferred || 'PREFERRED') + '</em>' : '')
          + '</span>';
      }
      if (data.whatsapp && data.phone) {
        contactBtns += '<span class="pngm-public-contact-btn is-wa' + (pref === 'whatsapp' ? ' is-preferred' : '') + '">'
          + '<i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp'
          + (pref === 'whatsapp' ? '<em class="pngm-public-pref">' + esc(labels.preferred || 'PREFERRED') + '</em>' : '')
          + '</span>';
      }

      var attrItems = '';
      var attrList = [];
      if (data.category && data.category !== '—') {
        attrList.push([labels.category || 'Category', data.category]);
      }
      if (data.subcategory && data.subcategory !== '—') {
        attrList.push([labels.subcategory || 'Subcategory', data.subcategory]);
      }
      if (data.attributes && data.attributes.length) {
        data.attributes.forEach(function (row) {
          attrList.push(row);
        });
      }
      attrList.forEach(function (row) {
        attrItems += '<li class="atr-line">'
          + '<span class="pngm-atr-ico" aria-hidden="true">' + previewAttrIcon(row[0]) + '</span>'
          + '<span class="atr-name">' + esc(row[0]) + '</span>'
          + '<span class="atr-value">' + esc(row[1]) + '</span>'
          + '</li>';
      });

      var mapQ = data.location && data.location !== '—' ? data.location : '';
      var mapHtml = '';
      if (mapQ) {
        mapHtml = '<div class="pngm-public-map">'
          + '<iframe class="pngm-public-map-frame" title="Map" src="https://maps.google.com/maps?q='
          + encodeURIComponent(mapQ) + '&amp;z=13&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>'
          + '</div>'
          + '<div class="pngm-public-address">' + esc(mapQ) + '</div>'
          + '<span class="pngm-public-map-link">' + esc(labels.viewOnMap || 'View on map') + ' →</span>';
      } else {
        mapHtml = '<p class="pngm-public-muted">' + esc(labels.unknownLocation || 'Unknown location') + '</p>';
      }

      var sideActions = '';
      sideActions += '<span class="pngm-public-action">' + esc(labels.sellerProfile || "Seller's profile") + '</span>';
      if (data.phone) {
        sideActions += '<span class="pngm-public-action is-phone"><i class="fas fa-phone" aria-hidden="true"></i> +675 ' + esc(data.phone) + '</span>';
      }

      var html = ''
        + '<div class="pngm-public-preview">'
        +   '<div class="pngm-public-main">'
        +     '<div class="pngm-public-gallery">'
        +       '<div class="pngm-public-cover" id="pngm-public-cover">' + coverHtml + '</div>'
        +       '<div class="pngm-public-thumbs">' + thumbsHtml + '</div>'
        +     '</div>'
        +     '<div class="pngm-public-basic">'
        +       '<h1>' + esc(data.title) + '</h1>'
        +       '<div class="pngm-public-price">' + esc(data.priceText) + '</div>'
        +       '<div class="pngm-public-posted">' + esc(labels.previewMeta || 'Listing preview') + '</div>'
        +     '</div>'
        +     (contactBtns
          ? '<div class="pngm-public-contact">'
            + '<h2>' + esc(labels.contactSeller || 'Contact Seller') + '</h2>'
            + '<div class="pngm-public-contact-row">' + contactBtns + '</div>'
            + '</div>'
          : '')
        +     '<div class="pngm-public-block">'
        +       '<h2><i class="fas fa-align-left" aria-hidden="true"></i> ' + esc(labels.description || 'Description') + '</h2>'
        +       '<div class="pngm-public-desc">' + (data.desc ? esc(data.desc) : '—') + '</div>'
        +     '</div>'
        +     (attrItems
          ? '<div class="pngm-public-block pngm-public-attrs">'
            + '<ul class="pngm-atr-visual" id="pngm-preview-atr">' + attrItems + '</ul>'
            + '</div>'
          : '')
        +     '<div class="pngm-public-block">'
        +       '<h2><i class="fas fa-map-marker-alt" aria-hidden="true"></i> ' + esc(labels.location || 'Location') + '</h2>'
        +       mapHtml
        +     '</div>'
        +   '</div>'
        +   '<aside class="pngm-public-side">'
        +     '<div class="pngm-public-seller-card">'
        +       '<p class="pngm-public-seller-name">' + esc(data.name) + '</p>'
        +       '<p class="pngm-public-seller-loc"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> ' + esc(data.location) + '</p>'
        +       '<div class="pngm-public-actions">' + sideActions + '</div>'
        +     '</div>'
        +   '</aside>'
        + '</div>';

      $body.html(html);
      $body.off('click.pngmPrevThumb').on('click.pngmPrevThumb', '.pngm-public-thumb', function () {
        var src = $(this).attr('data-src');
        $body.find('.pngm-public-thumb').removeClass('is-active');
        $(this).addClass('is-active');
        $body.find('#pngm-public-cover').html('<img src="' + esc(src) + '" alt="" />');
      });

      $modal.prop('hidden', false).removeAttr('hidden').show();
      $('body').addClass('pngm-preview-open');
    }

    function closePreviewModal() {
      var $modal = $('#pngm-preview-modal');
      $modal.prop('hidden', true).attr('hidden', 'hidden').hide();
      $('#pngm-preview-modal-body').empty();
      $('body').removeClass('pngm-preview-open');
    }

    function syncEmailFieldVisibility() {
      var on = $('#pngm_email_notify').is(':checked');
      var $field = $('#pngm-email-field');
      if (!$field.length) {
        return;
      }
      if (on) {
        $field.removeClass('is-hidden-email').prop('hidden', false).removeAttr('hidden').show();
      } else {
        $field.addClass('is-hidden-email').prop('hidden', true).attr('hidden', 'hidden');
      }
    }

    // Counters
    function bindCounter(sel, max) {
      var $input = $form.find(sel);
      var $c = $form.find('.pngm-post-counter[data-counter-for="' + (sel.indexOf('title') >= 0 ? 'title' : 'description') + '"] span');
      function tick() {
        $c.text(String(($input.val() || '').length));
      }
      $input.on('input', tick);
      tick();
    }
    bindCounter('input[name^="title"]', 100);
    bindCounter('textarea[name^="description"]', 5000);

    // Live error clearing — an error disappears the moment the field is valid.
    $form.on('input.pngmPostClear', 'input[name^="title"]', function () {
      if ($.trim($(this).val() || '').length >= 3) {
        clearErrorFor($(this));
      }
    });
    $form.on('input.pngmPostClear', 'textarea[name^="description"]', function () {
      if ($.trim($(this).val() || '').length >= 10) {
        clearErrorFor($(this));
      }
    });
    $form.on('input.pngmPostClear change.pngmPostClear', '#price', function () {
      var raw = String($(this).val() || '').replace(/,/g, '').trim();
      var num = parseFloat(raw);
      if (raw && !isNaN(num) && num > 0) {
        clearErrorFor($(this));
      }
    });
    $form.on('change.pngmPostClear', 'input[name="pngm_price_mode"]', function () {
      if (String($(this).val()) !== 'PAID') {
        clearErrorFor($form.find('.pngm-post-price-field').first());
      }
    });
    $form.on(
      'input.pngmPostClear change.pngmPostClear',
      '#post-hooks input, #post-hooks select, #post-hooks textarea, .atr-form input, .atr-form select, .atr-form textarea',
      function () {
        captureAttrsDraft();
        var $el = $(this);
        var $group = $el.closest('.control-group.atr-field, .atr-field');
        if (!$group.length) {
          clearErrorFor($el);
          return;
        }
        var filled = false;
        if ($el.is(':checkbox, :radio')) {
          filled = $group.find('input:checked').length > 0;
        } else {
          var v = String($el.val() || '');
          filled = v !== '' && v !== '0';
        }
        if (filled) {
          clearErrorFor($el);
        }
      }
    );
    $form.on(
      'change.pngmPostClear',
      '#regionId, #cityId, #region, #city, input[name="contactName"], input[name="contactPhone"], input[name="contactEmail"], #pngm_call_availability, #pngm_terms',
      function () {
        var $el = $(this);
        var filled = $el.is(':checkbox') ? $el.is(':checked') : $.trim(String($el.val() || '')) !== '';
        if (filled) {
          clearErrorFor($el);
        }
      }
    );

    // Category cards
    $form.on('click', '.pngm-post-cat-card', function () {
      setRoot($(this).data('root-id'), $(this).data('root-name'), false);
      $suggestChips.find('.pngm-post-suggest-chip').removeClass('is-selected');
    });

    $form.on('click', '.pngm-post-subcat-item', function () {
      var $btn = $(this);
      pickSubcategory($btn.data('leaf-id'), $btn.data('leaf-name'));
    });

    $sheetBody.on('click', '.pngm-post-subcat-item', function () {
      var $btn = $(this);
      pickSubcategory($btn.data('leaf-id'), $btn.data('leaf-name'));
    });

    $subChange.on('click', function () {
      var rootId = parseInt($root.val(), 10) || 0;
      var rootName = $('.pngm-post-cat-card.is-selected').data('root-name') || '';
      if (rootId) {
        openPostSubcatPicker(rootId, rootName, usesPostSubcatSheet());
      }
    });

    $sheetBackdrop.on('click', closePostSubcatSheet);

    $(document).on('keydown.pngmPostSubcat', function (event) {
      if (event.key === 'Escape' || event.keyCode === 27) {
        closePostSubcatSheet();
      }
    });

    $form.on('click', '.pngm-post-suggest-chip', function () {
      var $chip = $(this);
      applyCategorySuggestion(
        $chip.data('root-id'),
        $chip.data('root-name'),
        $chip.data('leaf-id'),
        $chip.data('leaf-name')
      );
    });

    $form.on('input', 'input[name^="title"]', function () {
      var t = $.trim($(this).val() || '');
      updateSummary(null, null, t || '—');
      scheduleCategorySuggestions();
    });

    $sub.on('change input', function () {
      syncCatFromSubcategory();
      if ($sub.val()) {
        $catId.trigger('change');
        $suggestChips.find('.pngm-post-suggest-chip').removeClass('is-selected');
        $suggestChips.find('.pngm-post-suggest-chip[data-leaf-id="' + $sub.val() + '"]').addClass('is-selected');
      }
    });

    // Capture any subcategory change (including programmatic option picks)
    $form.on('change', '#pngm_subcategory', function () {
      syncCatFromSubcategory();
    });

    // Also clear as soon as the user interacts with the dropdown after an error
    $form.on('click focus', '#pngm_subcategory', function () {
      if ($sub.val()) {
        clearSubcategoryWarning();
      }
    });

    // Price mode — real radios; amount only for "Set a price"
    $form.on('change', 'input[name="pngm_price_mode"]', function () {
      applyPriceMode($(this).val());
    });
    applyPriceMode($form.find('input[name="pngm_price_mode"]:checked').val() || 'PAID');

    // Pills
    $form.on('click', '.pngm-post-pills .pngm-post-pill', function () {
      var $btn = $(this);
      var $group = $btn.closest('.pngm-post-pills');
      var value = String($btn.data('value') || '');
      if ($group.data('pills') === 'contact-pref' && value === 'whatsapp' && !$('#pngm_whatsapp').is(':checked')) {
        $('#pngm_whatsapp').prop('checked', true);
      }
      $group.find('.pngm-post-pill').removeClass('is-selected');
      $btn.addClass('is-selected');
      if ($group.data('pills') === 'transaction') {
        syncTransaction(value);
      }
      if ($group.data('pills') === 'contact-pref') {
        $('#pngm_contact_pref').val(value);
      }
    });

    $form.on('change', '#pngm_whatsapp', function () {
      if ($(this).is(':checked')) {
        return;
      }
      if (String($('#pngm_contact_pref').val() || '') === 'whatsapp') {
        var $msg = $form.find('.pngm-post-contact-pref .pngm-post-pill[data-value="message"]');
        $form.find('.pngm-post-contact-pref .pngm-post-pill').removeClass('is-selected');
        $msg.addClass('is-selected');
        $('#pngm_contact_pref').val('message');
      }
    });

    $next.on('click', function () {
      if (!validateStep(step)) {
        return;
      }
      if (step === 1) {
        var leafId = String($sub.val() || $catId.val() || '').trim();
        if (leafId) {
          // Do not force-reload: that wiped Make/Accessories/etc. on every Next.
          loadCategoryAttributes(leafId);
        }
        clearErrorSummary();
        showStep(step + 1);
        return;
      }
      if (step === 2) {
        clearErrorSummary();
        showStep(step + 1);
        return;
      }
      clearErrorSummary();
      showStep(step + 1);
    });
    $back.on('click', function () {
      showStep(step - 1);
    });
    $cancel.on('click', function () {
      window.location.href = cfg.homeUrl || '/';
    });
    $preview.on('click', function (e) {
      e.preventDefault();
      openPreviewModal();
    });

    $(document).on('click', '[data-preview-close]', function (e) {
      e.preventDefault();
      closePreviewModal();
    });

    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closePreviewModal();
      }
    });

    $form.on('submit', function (e) {
      if (step !== total) {
        e.preventDefault();
        if (validateStep(step)) {
          showStep(step + 1);
        }
        return false;
      }
      // Final publish still checks Contact (step 5) and Terms + reCAPTCHA (step 6).
      if (!validateStep(5)) {
        e.preventDefault();
        showStep(5);
        return false;
      }
      if (!validateStep(6)) {
        e.preventDefault();
        showStep(6);
        return false;
      }
      // Ensure free / check pricing before submit
      var mode = $('input[name="pngm_price_mode"]:checked').val();
      var txPill = $('.pngm-post-pills[data-pills="transaction"] .pngm-post-pill.is-selected').data('value');
      if (String(txPill) === '0') {
        $('#price').val('0');
      } else if (mode === 'CHECK') {
        $('#price').val('');
      }

      startPublishing();
    });

    /**
     * Publishing can take a few seconds (photos, plugins) — show progress and
     * block a second submit.
     */
    function startPublishing() {
      if ($form.data('pngmPublishing')) {
        return;
      }
      $form.data('pngmPublishing', true);

      if (!$publish.data('pngmLabel')) {
        $publish.data('pngmLabel', $publish.text());
      }
      $publish.addClass('is-loading').text(labels.publishing || 'Publishing…');
      $preview.prop('disabled', true);
      $back.prop('disabled', true);

      // Disable after the browser has started the POST, so the button is never
      // disabled while the submit is still being dispatched.
      window.setTimeout(function () {
        $publish.prop('disabled', true);
      }, 0);

      $('body').addClass('pngm-post-publishing');
      if (!$('#pngm-post-publishing').length) {
        $('body').append(
          '<div id="pngm-post-publishing" class="pngm-post-publishing-overlay" role="status" aria-live="polite">' +
          '<div class="pngm-post-publishing-card">' +
          '<span class="pngm-post-spinner" aria-hidden="true"></span>' +
          '<span class="pngm-post-publishing-text"></span>' +
          '</div></div>'
        );
        $('#pngm-post-publishing .pngm-post-publishing-text')
          .text(labels.publishingHint || 'Publishing your listing, please wait…');
      }
    }

    function stopPublishing() {
      $form.removeData('pngmPublishing');
      $publish.removeClass('is-loading').prop('disabled', false);
      var label = $publish.data('pngmLabel');
      if (label) {
        $publish.text(label);
      }
      $preview.prop('disabled', false);
      $back.prop('disabled', false);
      $('body').removeClass('pngm-post-publishing');
      $('#pngm-post-publishing').remove();
    }

    // Back/forward cache restore must not leave the form stuck in "Publishing…".
    $(window).on('pageshow', function (event) {
      if (event.originalEvent && event.originalEvent.persisted) {
        stopPublishing();
      }
    });

    // Map follows region / city / address
    $form.on('change', '#regionId, #cityId, #countryId, #region, #city, #country', function () {
      setTimeout(updateMapPreview, 50);
    });
    $form.on('input blur', '#address', function () {
      updateMapPreview();
    });
    $(document).ajaxComplete(function (event, xhr, settings) {
      var url = settings && settings.url ? String(settings.url) : '';
      var data = settings && settings.data != null ? String(settings.data) : '';
      if (url.indexOf('city') !== -1 || url.indexOf('region') !== -1 || url.indexOf('ajaxLoc') !== -1) {
        setTimeout(updateMapPreview, 80);
      }
      // Only react to category attribute form loads — NOT Make cascade
      // (hook=atr_select_url also uses runhook and would loop atr-loading forever).
      var isAttrFormLoad =
        (url.indexOf('item_form') !== -1 || url.indexOf('item_edit') !== -1
          || data.indexOf('item_form') !== -1 || data.indexOf('item_edit') !== -1)
        && url.indexOf('atr_select_url') === -1
        && data.indexOf('atr_select_url') === -1;
      if (isAttrFormLoad) {
        window.setTimeout(function () {
          captureAttrsDraft();
          // Do not call applyAttrsDraft() here — it can re-trigger cascades.
          applyAttrsDraftPass();
          $('#post-hooks .atr-loading').removeClass('atr-loading');
          syncAttrsSectionVisibility();
          $(document).trigger('pngm:attrs-loaded');
        }, 30);
      }
      // A finished upload should clear the "photo required" error.
      setTimeout(function () {
        if (countUploadedPhotos() > 0) {
          clearErrorFor($form.find('#pngm-step-photos .pngm-post-upload-card').first());
        }
      }, 120);
    });

    // Geolocation — pin map to current coordinates
    $('#pngm-use-location').on('click', function () {
      var $btn = $(this);
      if (!navigator.geolocation) {
        return;
      }
      $btn.prop('disabled', true);
      navigator.geolocation.getCurrentPosition(function (pos) {
        $btn.prop('disabled', false);
        updateMapPreview({ lat: pos.coords.latitude, lng: pos.coords.longitude });
        $('#pngm-post-map').addClass('is-located');
      }, function () {
        $btn.prop('disabled', false);
      }, { enableHighAccuracy: true, timeout: 8000 });
    });

    // Normalize phone: strip +675 prefix from the number field
    var $phoneInput = $form.find('input[name="contactPhone"], input[name="sPhone"], #sPhone, #contactPhone').first();
    if ($phoneInput.length) {
      var phoneVal = String($phoneInput.val() || '').replace(/^\+?675\s*/, '');
      $phoneInput.val(phoneVal).attr('placeholder', '7XX XXX XXX');
    }
    $form.find('input[name="contactName"]').attr('placeholder', 'e.g. John K.');

    // Boot state
    if (cfg.root) {
      setRoot(cfg.root, null, !!cfg.leaf);
      if (cfg.leaf) {
        $catId.val(String(cfg.leaf));
        $sub.val(String(cfg.leaf));
        updateSummary($('.pngm-post-cat-card.is-selected').data('root-name') || '', $sub.find('option:selected').text());
      } else {
        // Root alone is not enough — require an explicit subcategory before Next.
        $catId.val('');
        $sub.val('');
      }
    } else {
      $catId.val('');
    }
    if (cfg.priceType === 'CHECK' || cfg.priceType === 'FREE') {
      applyPriceMode(cfg.priceType);
    } else {
      applyPriceMode($form.find('input[name="pngm_price_mode"]:checked').val() || 'PAID');
    }
    var $selTx = $('.pngm-post-pills[data-pills="transaction"] .pngm-post-pill.is-selected');
    if ($selTx.length) {
      syncTransaction($selTx.data('value'));
    }

    updateMapPreview();
    syncEmailFieldVisibility();
    $form.on('change', '#pngm_email_notify', syncEmailFieldVisibility);
    // Load leaf attributes into #post-hooks (Make/Brand for parts, etc.).
    // Osclass also AJAX-fills #plugin-hook; we own #post-hooks so sellers see fields.
    if (cfg.leaf) {
      loadCategoryAttributes(String(cfg.leaf), true);
    } else {
      syncAttrsSectionVisibility();
      captureAttrsDraft();
    }

    var bootTitle = $.trim($form.find('input[name^="title"]').val() || '');
    if (bootTitle) {
      updateSummary(null, null, bootTitle);
      renderCategorySuggestions(bootTitle);
    }
    // Retry visibility after late plugin AJAX.
    window.setTimeout(syncAttrsSectionVisibility, 200);
    window.setTimeout(syncAttrsSectionVisibility, 800);
    showStep(1);
  }

  $(init);
})(jQuery);
