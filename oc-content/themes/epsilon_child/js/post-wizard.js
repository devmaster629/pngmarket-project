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

  function init() {
    var $form = $('form.pngm-post-form[data-pngm-wizard="1"]');
    if (!$form.length) {
      return;
    }

    var cfg = boot();
    var map = subcats();
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
    var $txNative = $('#sTransaction');

    function fillSubcats(rootId, selectedLeaf) {
      var list = map[String(rootId)] || map[rootId] || [];
      $sub.empty().append($('<option/>').val('').text(labels.selectSub || 'Select a subcategory'));
      list.forEach(function (row) {
        $sub.append($('<option/>').val(row.id).text(row.name));
      });
      if (selectedLeaf) {
        $sub.val(String(selectedLeaf));
      }
      $subWrap.toggleClass('is-hidden', !rootId);
    }

    function setRoot(rootId, rootName, keepLeaf) {
      rootId = parseInt(rootId, 10) || 0;
      $root.val(rootId || '');
      $('.pngm-post-cat-card').removeClass('is-selected').attr('aria-selected', 'false');
      var $card = $('.pngm-post-cat-card[data-root-id="' + rootId + '"]');
      $card.addClass('is-selected').attr('aria-selected', 'true');
      fillSubcats(rootId, keepLeaf ? $catId.val() : '');
      if (!keepLeaf) {
        $catId.val('');
      }
      updateSummary(rootName || ($card.data('root-name') || ''), '');
      if (keepLeaf && $sub.val()) {
        updateSummary(null, $sub.find('option:selected').text());
      }
    }

    /**
     * Category attributes (Make, Seats, ...) are rendered server-side for the
     * category known at page load. The wizard picks the category in step 1, so
     * reload the plugin fields whenever that choice changes.
     */
    var attrsLoadedFor = String(cfg.leaf || '');
    var attrsRequest = null;

    function loadCategoryAttributes(catId) {
      var $box = $('#post-hooks');
      catId = String(catId || '');

      if (!$box.length || !catId || catId === attrsLoadedFor) {
        return;
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
        $(document).trigger('pngm:attrs-loaded');
        if (window.pngmItemValidation && window.pngmItemValidation.forceEnhanceValidator) {
          window.pngmItemValidation.forceEnhanceValidator();
        }
      }).always(function () {
        $box.removeClass('is-loading');
        attrsRequest = null;
      });
    }

    function updateSummary(rootName, leafName) {
      if (rootName !== null && rootName !== undefined) {
        $('[data-sum="root"]').text(rootName || '—');
      }
      if (leafName !== null && leafName !== undefined) {
        $('[data-sum="leaf"]').text(leafName || '—');
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
      $form.find('.pngm-post-field-error').each(function () {
        hideFieldError($(this));
      });
      $form.find('.pngm-post-field.is-invalid, .pngm-post-select.is-invalid, .pngm-post-upload-card.is-invalid, .pngm-post-price-field.is-invalid, .control-group.is-invalid, input.is-invalid, textarea.is-invalid, select.is-invalid').removeClass('is-invalid');
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
      var id = String($sub.val() || '');
      if (id) {
        $catId.val(id);
        clearSubcategoryWarning();
        updateSummary(null, $sub.find('option:selected').text() || '');
        loadCategoryAttributes(id);
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
      $el.addClass('is-invalid');
      var $wrap = $el.closest('.pngm-post-field, .pngm-post-subcat-wrap, .pngm-post-price-field, .pngm-post-upload-card, .control-group.atr-field, .atr-field');
      if (!$wrap.length && $el.hasClass('pngm-post-upload-card')) {
        $wrap = $el;
      }
      if ($wrap.length) {
        $wrap.addClass('is-invalid');
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
      var $wrap = $el.closest('.pngm-post-field, .pngm-post-subcat-wrap, .pngm-post-price-field, .pngm-post-upload-card, .pngm-post-terms-wrap, .control-group.atr-field, .atr-field');
      if (!$wrap.length) {
        return;
      }
      $wrap.removeClass('is-invalid');
      $wrap.find('.is-invalid').removeClass('is-invalid');
      hideFieldError($wrap.find('.pngm-post-field-error').first());
    }

    function focusFirstInvalid() {
      var $bad = $form.find('.is-invalid:visible').first();
      if (!$bad.length) {
        return;
      }
      var top = $bad.offset();
      if (top) {
        $('html, body').stop(true).animate({ scrollTop: Math.max(0, top.top - 110) }, 220);
      }
      if ($bad.is('input, textarea, select')) {
        try {
          $bad.trigger('focus');
        } catch (e) { /* ignore */ }
      }
    }

    function attrGroupLabel($group) {
      var $lab = $group.find('> label.control-label, > .control-label').first();
      if (!$lab.length) {
        $lab = $group.find('label').first();
      }
      var text = $.trim($lab.clone().children('.req, .req *').remove().end().text().replace(/\*/g, ''));
      return text || (labels.requiredField || 'Required field');
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
        syncCatFromSubcategory();
        if (!$catId.val()) {
          showError($sub, labels.selectSub);
          $sub.addClass('is-invalid');
          focusFirstInvalid();
          return false;
        }
        clearSubcategoryWarning();
        return true;
      }
      if (n === 2) {
        var $step2 = $form.find('.pngm-post-step-panel[data-step="2"]');
        var title = $form.find('input[name^="title"]').val() || '';
        var desc = $form.find('textarea[name^="description"]').val() || '';
        var ok = true;
        if ($.trim(title).length < 3) {
          showError($form.find('input[name^="title"]'), labels.needTitle);
          ok = false;
        }
        if ($.trim(desc).length < 10) {
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
        var photoCount = countUploadedPhotos();
        if (photoCount < 1) {
          var $photoCard = $form.find('#pngm-step-photos .pngm-post-upload-card, #photos').first();
          showError($photoCard.length ? $photoCard : $form.find('#photos'), labels.needPhoto || 'Please upload at least one photo.');
          focusFirstInvalid();
          return false;
        }
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
        var $avail = $('#pngm_call_availability');
        if ($name.length && $.trim($name.val() || '').length < 2) {
          showError($name, labels.needName || 'Please enter your name.');
          okContact = false;
        }
        if ($phone.length && $.trim($phone.val() || '').replace(/^\+?675\s*/, '').length < 6) {
          showError($phone, labels.needPhone || 'Please enter a phone number.');
          okContact = false;
        }
        if ($avail.length && !$avail.val()) {
          showError($avail, labels.needAvail || 'Please select call availability.');
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
        if (!$('#pngm_terms').is(':checked')) {
          showError($('#pngm_terms'), labels.needTerms);
          var $termsErr = $form.find('.pngm-post-field-error[data-for="pngm_terms"]');
          showFieldError($termsErr, labels.needTerms);
          focusFirstInvalid();
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
        imgs.slice(0, 8).forEach(function (src, idx) {
          var $btn = $('<button type="button" class="pngm-post-review-thumb"/>').append($('<img/>').attr('src', src));
          if (idx === 0) {
            $btn.addClass('is-active');
          }
          $btn.on('click', function () {
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
      if (countUploadedPhotos() < 1) {
        missing.push(labels.needPhoto || 'At least one photo required');
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
      if ($('#pngm_call_availability').length && !$('#pngm_call_availability').val()) {
        missing.push(labels.needAvail || 'Call availability required');
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

      var condText = $.trim($('#sCondition option:selected').text() || '');
      if (!condText || /select/i.test(condText)) {
        condText = '—';
      }
      var $txPill = $('.pngm-post-pills[data-pills="transaction"] .pngm-post-pill.is-selected');
      var txLabel = $.trim($txPill.text() || '') || $.trim($('#sTransaction option:selected').text() || '') || '—';

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
        imgs: imgs
      };
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

      var actions = [];
      if (data.allowMessages) {
        actions.push('<span class="pngm-public-action is-primary"><i class="fas fa-comment"></i> ' + esc(labels.message || 'Message') + '</span>');
      }
      if (data.phone) {
        actions.push('<span class="pngm-public-action"><i class="fas fa-phone"></i> +675 ' + esc(data.phone) + '</span>');
      }
      if (data.whatsapp && data.phone) {
        actions.push('<span class="pngm-public-action is-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</span>');
      }
      if (!actions.length) {
        actions.push('<span class="pngm-public-action is-muted">' + esc(labels.noContact || 'No contact options') + '</span>');
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
        +       '<div class="pngm-public-meta">'
        +         '<span class="pngm-public-chip"><i class="fas fa-map-marker-alt"></i> ' + esc(data.location) + '</span>'
        +         '<span class="pngm-public-chip">' + esc(data.condition) + '</span>'
        +         '<span class="pngm-public-chip">' + esc(data.transaction) + '</span>'
        +       '</div>'
        +       (data.desc ? '<p class="pngm-public-desc">' + esc(data.desc.substring(0, 600)) + (data.desc.length > 600 ? '…' : '') + '</p>' : '')
        +     '</div>'
        +   '</div>'
        +   '<aside class="pngm-public-side">'
        +     '<p class="pngm-public-seller-name">' + esc(data.name) + '</p>'
        +     '<p class="pngm-public-seller-loc">' + esc(data.location) + '</p>'
        +     '<div class="pngm-public-actions">' + actions.join('') + '</div>'
        +     '<dl class="pngm-public-detail-list">'
        +       '<div><dt>' + esc(labels.category || 'Category') + '</dt><dd>' + esc(data.category) + '</dd></div>'
        +       '<div><dt>' + esc(labels.subcategory || 'Subcategory') + '</dt><dd>' + esc(data.subcategory) + '</dd></div>'
        +       '<div><dt>' + esc(labels.condition || 'Condition') + '</dt><dd>' + esc(data.condition) + '</dd></div>'
        +       '<div><dt>' + esc(labels.transaction || 'Transaction') + '</dt><dd>' + esc(data.transaction) + '</dd></div>'
        +       '<div><dt>' + esc(labels.whatsapp || 'WhatsApp') + '</dt><dd>' + esc(data.whatsapp ? (labels.yes || 'Yes') : (labels.no || 'No')) + '</dd></div>'
        +     '</dl>'
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
    });

    $sub.on('change input', function () {
      syncCatFromSubcategory();
      if ($sub.val()) {
        $catId.trigger('change');
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
      $group.find('.pngm-post-pill').removeClass('is-selected');
      $btn.addClass('is-selected');
      if ($group.data('pills') === 'transaction') {
        syncTransaction($btn.data('value'));
      }
      if ($group.data('pills') === 'contact-pref') {
        $('#pngm_contact_pref').val($btn.data('value'));
      }
    });

    $next.on('click', function () {
      if (!validateStep(step)) {
        return;
      }
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
      if (!validateStep(6)) {
        e.preventDefault();
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
      if (url.indexOf('city') !== -1 || url.indexOf('region') !== -1 || url.indexOf('ajaxLoc') !== -1) {
        setTimeout(updateMapPreview, 80);
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
      setRoot(cfg.root, null, true);
      if (cfg.leaf) {
        $catId.val(String(cfg.leaf));
        $sub.val(String(cfg.leaf));
        updateSummary($('.pngm-post-cat-card.is-selected').data('root-name') || '', $sub.find('option:selected').text());
      }
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
    showStep(1);
  }

  $(init);
})(jQuery);
