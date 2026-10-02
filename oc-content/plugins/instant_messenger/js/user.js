$(document).ready(function(){
  if(document.getElementById('im-message-form') || document.querySelector('.im-file-messages')) {
    $('body').addClass('im-chat-page');
    var box = document.querySelector('.im-table.im-messages');
    if(box) {
      requestAnimationFrame(function(){
        box.scrollTop = box.scrollHeight;
      });
    }

    // Mobile keyboard handling: keep the browser viewport stable.
    // Do not move the composer with keyboard height. Let the browser resize
    // naturally (works better on Android Chrome and iOS Safari).
    function imKeyboardRefresh() {
      if(window.visualViewport) {
        document.documentElement.style.setProperty('--im-visual-height', window.visualViewport.height + 'px');
      }
    }

    imKeyboardRefresh();
    if(window.visualViewport) {
      window.visualViewport.addEventListener('resize', imKeyboardRefresh);
      window.visualViewport.addEventListener('orientationchange', imKeyboardRefresh);
    }
  }

  function imComposerMinHeight() {
    return ($(window).width() <= 360) ? 85 : 50;
  }

  function imFitComposerHeight() {
    // The theme chat layout owns composer autosize (44-120px). Running both
    // grew the box and re-laid out the chat on every keystroke.
    if(typeof window.pngmLayoutChat === 'function' && document.body.classList.contains('im-chat-page')) {
      return;
    }

    var ta = document.getElementById('im-message');
    if(!ta) {
      return;
    }

    var min = imComposerMinHeight();
    ta.style.height = 'auto';
    var next = Math.max(min, ta.scrollHeight);
    ta.style.height = next + 'px';
    ta.style.overflowY = (next > 455) ? 'scroll' : 'hidden';

    if(next > 455) {
      $('body #im-message-form button.im-button-alt').css('right', '17px');
      $('body #im-message-form .im-attachment').css('right', '68px');
    } else {
      $('body #im-message-form button.im-button-alt').css('right', '');
      $('body #im-message-form .im-attachment').css('right', '');
    }

    if(typeof window.pngmLayoutChat === 'function') {
      window.pngmLayoutChat();
    }
  }

  window.imResetComposerHeight = function() {
    var ta = document.getElementById('im-message');
    if(!ta) {
      return;
    }

    ta.style.height = '';
    ta.style.overflowY = 'hidden';
    $('body #im-message-form button.im-button-alt').css('right', '');
    $('body #im-message-form .im-attachment').css('right', '');
    if(typeof window.pngmLayoutChat === 'function') {
      window.pngmLayoutChat();
    }
  };

  // AUTO-EXPAND TEXTAREA
  $('body').on('change keyup keydown paste cut input', 'textarea#im-message', function () {
    imFitComposerHeight();
  });


  // SUBMIT MESSAGE WITH CTRL+ENTER
  $('body').on('keydown', 'textarea#im-message', function(e) {
    if((e.ctrlKey || e.metaKey) && (e.key === 'Enter' || e.keyCode === 13)) {
      e.preventDefault();
      $('body #im-message-form button.im-button-alt').trigger('click');
    }
  });


  // TOOLTIPS IN USER ACCOUNT
  Tipped.create('.im-has-tooltip, .im-tooltip', { maxWidth: 200, radius: false });
  Tipped.create('.im-has-tooltip-left', { maxWidth: 200, radius: false } );

  // Native <label for="im-file"> opens the picker (works on Android). Only
  // fall back to programmatic click for non-label triggers.
  $('body').off('click.imAttachBtn', '#pngm-im-attach-trigger, button.pngm-im-attach-btn, .pngm-im-attach-btn')
    .on('click.imAttachBtn', 'button.pngm-im-attach-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var input = document.getElementById('im-file');
      if(input) {
        input.click();
      }
    });


  // ATTACHMENTS: keep a file list so users can add several and remove any of them
  (function() {
    var pending = [];
    var previewModal = null;
    var previewModalUrl = '';

    function isImageFile(file) {
      var mime = String(file && file.type ? file.type : '').toLowerCase();
      if(mime.indexOf('image/') === 0) {
        return true;
      }
      return /\.(jpe?g|png|gif|webp|avif|bmp|heic|heif)$/i.test(String(file && file.name ? file.name : ''));
    }

    function closeAttachPreview() {
      if(!previewModal) {
        return;
      }
      previewModal.hidden = true;
      document.body.classList.remove('pngm-im-attach-preview-open');
      var img = previewModal.querySelector('img');
      if(img) {
        img.removeAttribute('src');
      }
      if(previewModalUrl) {
        try { URL.revokeObjectURL(previewModalUrl); } catch (e) {}
        previewModalUrl = '';
      }
    }

    function openAttachPreview(file) {
      if(!file || !isImageFile(file) || !window.URL || typeof URL.createObjectURL !== 'function') {
        return;
      }
      if(!previewModal) {
        previewModal = document.createElement('div');
        previewModal.className = 'pngm-im-attach-preview';
        previewModal.setAttribute('role', 'dialog');
        previewModal.setAttribute('aria-modal', 'true');
        previewModal.hidden = true;
        previewModal.innerHTML =
          '<button type="button" class="pngm-im-attach-preview-x" data-close aria-label="Close">&times;</button>' +
          '<div class="pngm-im-attach-preview-stage"><img alt="" /></div>' +
          '<div class="pngm-im-attach-preview-dock">' +
            '<button type="button" class="pngm-im-attach-preview-close" data-close>Close</button>' +
          '</div>';
        document.body.appendChild(previewModal);
        previewModal.addEventListener('click', function(e) {
          if(e.target.closest('[data-close]') || e.target === previewModal) {
            e.preventDefault();
            closeAttachPreview();
          }
        });
        document.addEventListener('keydown', function(e) {
          if(!previewModal.hidden && (e.key === 'Escape' || e.keyCode === 27)) {
            closeAttachPreview();
          }
        });
      }
      closeAttachPreview();
      previewModalUrl = URL.createObjectURL(file);
      var img = previewModal.querySelector('img');
      if(img) {
        img.src = previewModalUrl;
        img.alt = file.name || '';
      }
      previewModal.hidden = false;
      document.body.classList.add('pngm-im-attach-preview-open');
    }

    function fileInput() {
      return document.getElementById('im-file');
    }

    function sameFile(a, b) {
      return a && b && a.name === b.name && a.size === b.size && a.lastModified === b.lastModified;
    }

    var syncing = false;

    function syncInput() {
      var input = fileInput();
      if(!input) {
        return;
      }

      // Always wipe first — otherwise removed files can linger in the native FileList.
      try {
        input.value = '';
      } catch (errClear) {}

      if(!pending.length) {
        return;
      }

      if(typeof DataTransfer === 'undefined') {
        return;
      }

      try {
        var dt = new DataTransfer();
        pending.forEach(function(file) {
          dt.items.add(file);
        });
        syncing = true;
        input.files = dt.files;
      } catch (e) {
        // If rebuild fails, drop pending so UI and payload stay consistent.
        pending = [];
        try {
          input.value = '';
        } catch (err2) {}
      }
      syncing = false;
    }

    function renderList() {
      var list = document.getElementById('im-file-list');
      if(!list) {
        return;
      }

      list.innerHTML = '';
      if(!pending.length) {
        list.hidden = true;
        list.setAttribute('hidden', 'hidden');
        closeAttachPreview();
        return;
      }

      list.hidden = false;
      list.removeAttribute('hidden');
      pending.forEach(function(file, index) {
        var chip = document.createElement('span');
        chip.className = 'im-file-chip';
        chip.setAttribute('data-file-index', String(index));
        if(isImageFile(file)) {
          chip.classList.add('is-image');
          chip.setAttribute('role', 'button');
          chip.setAttribute('tabindex', '0');
          chip.setAttribute('title', 'Tap to preview');
        }

        var name = document.createElement('em');
        name.textContent = file.name;
        chip.appendChild(name);

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'im-file-remove';
        remove.setAttribute('aria-label', 'Remove file');
        remove.innerHTML = '&times;';
        remove.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          var idx = parseInt(chip.getAttribute('data-file-index'), 10);
          if(isNaN(idx)) {
            idx = index;
          }
          if(idx >= 0 && idx < pending.length) {
            pending.splice(idx, 1);
          }
          syncInput();
          renderList();
          if(typeof window.pngmLayoutChat === 'function') {
            window.pngmLayoutChat();
          }
        });
        chip.appendChild(remove);

        if(isImageFile(file)) {
          chip.addEventListener('click', function(e) {
            if(e.target.closest('.im-file-remove')) {
              return;
            }
            e.preventDefault();
            openAttachPreview(file);
          });
          chip.addEventListener('keydown', function(e) {
            if(e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
              e.preventDefault();
              openAttachPreview(file);
            }
          });
        }

        list.appendChild(chip);
      });
    }

    window.imGetComposerFiles = function() {
      return pending.slice();
    };

    window.imResetComposerFiles = function() {
      pending = [];
      closeAttachPreview();
      var input = fileInput();
      if(input) {
        try {
          input.value = '';
        } catch (err) {}
      }
      renderList();
    };

    function fileAllowed(file) {
      var input = fileInput();
      var accept = (input && input.getAttribute('accept')) ? String(input.getAttribute('accept')) : '';
      if(!accept) {
        return true;
      }

      var name = String(file && file.name ? file.name : '');
      var mime = String(file && file.type ? file.type : '').toLowerCase();
      var ext = '';
      var dot = name.lastIndexOf('.');
      if(dot >= 0) {
        ext = name.slice(dot + 1).toLowerCase();
      }

      var parts = accept.split(',');
      var i;
      for(i = 0; i < parts.length; i += 1) {
        var rule = parts[i].replace(/^\s+|\s+$/g, '').toLowerCase();
        if(!rule) {
          continue;
        }
        if(rule.charAt(0) === '.' && ext && rule === ('.' + ext)) {
          return true;
        }
        if(rule.indexOf('/*') > 0 && mime) {
          var group = rule.split('/')[0];
          if(mime.indexOf(group + '/') === 0) {
            return true;
          }
        }
        if(rule.indexOf('/') > 0 && mime && rule === mime) {
          return true;
        }
      }
      return false;
    }

    function maxFileBytes() {
      var input = fileInput();
      var raw = input ? input.getAttribute('data-max-bytes') : '';
      var n = parseInt(raw, 10);
      if(n > 0) {
        return n;
      }
      return 51200000;
    }

    function fileWithinSize(file) {
      var size = file && typeof file.size === 'number' ? file.size : 0;
      return size <= maxFileBytes();
    }

    function warnUnsupported(fileName) {
      var msg = 'File type not supported: ' + (fileName || 'attachment');
      if(typeof window.pngmShowToast === 'function') {
        window.pngmShowToast(msg, true);
        return;
      }
      try {
        window.alert(msg);
      } catch (errAlert) {}
    }

    function warnTooLarge(fileName) {
      var mb = Math.round(maxFileBytes() / 1000000);
      var msg = 'File is too large (max ' + mb + 'MB): ' + (fileName || 'attachment');
      if(typeof window.pngmShowToast === 'function') {
        window.pngmShowToast(msg, true);
        return;
      }
      try {
        window.alert(msg);
      } catch (errAlert) {}
    }

    $('body').off('change.imFiles', '#im-file').on('change.imFiles', '#im-file', function() {
      if(syncing) {
        return;
      }

      var added = this.files ? Array.prototype.slice.call(this.files) : [];
      var rejectedType = [];
      var rejectedSize = [];
      added.forEach(function(file) {
        if(!fileAllowed(file)) {
          rejectedType.push(file && file.name ? file.name : 'file');
          return;
        }
        if(!fileWithinSize(file)) {
          rejectedSize.push(file && file.name ? file.name : 'file');
          return;
        }
        var exists = pending.some(function(current) {
          return sameFile(current, file);
        });
        if(!exists) {
          pending.push(file);
        }
      });
      // Re-sync native input from pending only (source of truth).
      syncInput();
      renderList();
      $('#im-message').removeAttr('required').removeClass('error');
      $('#im-message-form').find('label.error, .error[for="im-file"]').remove();
      $('#im-error-list').empty().hide();
      if(rejectedType.length) {
        warnUnsupported(rejectedType[0] + (rejectedType.length > 1 ? (' (+' + (rejectedType.length - 1) + ' more)') : ''));
      }
      if(rejectedSize.length) {
        warnTooLarge(rejectedSize[0] + (rejectedSize.length > 1 ? (' (+' + (rejectedSize.length - 1) + ' more)') : ''));
      }
      if(typeof window.pngmLayoutChat === 'function') {
        window.pngmLayoutChat();
      }
    });

    // Shared helpers for the optimistic send path (im_ui.php).
    window.pngmImFileMaxBytes = maxFileBytes;
    window.pngmImWarnUnsupportedFile = warnUnsupported;
    window.pngmImWarnFileTooLarge = warnTooLarge;
    window.pngmImFileAllowed = fileAllowed;
    window.pngmImFileWithinSize = fileWithinSize;
  })();

  // Whole conversation row opens the thread
  $('body').on('click', '.im-threads .im-table-row', function(e) {
    if($(e.target).closest('a, button, input, label').length) {
      return;
    }

    var href = $(this).attr('data-href') || $(this).find('a.im-mes-title').attr('href');
    if(href) {
      window.location.href = href;
    }
  });


  // FORM VALIDATION
  if($('form.im-form-validate').length) {
    var imRqName2  = (typeof imRqName  !== 'undefined') ? imRqName  : 'Your Name: This field is required!';
    var imDsName2  = (typeof imDsName  !== 'undefined') ? imDsName  : 'Your Name: Name is too short!';
    var imRqEmail2 = (typeof imRqEmail !== 'undefined') ? imRqEmail : 'Your Email: This field is required!';
    var imDsEmail2 = (typeof imDsEmail !== 'undefined') ? imDsEmail : 'Your Email: Email your have entered is not in valid format!';
    var imRqMessage2 = (typeof imRqMessage !== 'undefined') ? imRqMessage : 'Message: This field is required!';
    var imDsMessage2 = (typeof imDsMessage !== 'undefined') ? imDsMessage : 'Message: Message is too short!';

    $('form.im-form-validate').validate({
      rules: {
        "im-from-user-name": {
          // Only guests see this field; logged-in users send a hidden registered name.
          required: {
            depends: function () {
              return $('#im-from-user-name').is(':visible');
            }
          }
        },
        
        "im-from-user-email": {
          required: {
            depends: function () {
              return $('#im-from-user-email').is(':visible');
            }
          },
          email: {
            depends: function () {
              return $('#im-from-user-email').is(':visible');
            }
          }
        },
        
        /*
        "im-title": {
          required: true,
          minlength: 2
        },
        */
        
        "im-message": {
          required: {
            depends: function () {
              // Attachment-only is allowed (pending file list or native input).
              if (typeof window.imGetComposerFiles === 'function' && window.imGetComposerFiles().length) {
                return false;
              }
              var fileInput = document.getElementById('im-file');
              return !(fileInput && fileInput.files && fileInput.files.length);
            }
          },
          minlength: {
            depends: function () {
              return $.trim($('#im-message').val() || '').length > 0;
            },
            param: 2
          }
        }
      },
      
      messages: {
        "im-from-user-name": {
          required: imRqName2
        },
        
        "im-from-user-email": {
          required: imRqEmail2,
          email: imDsEmail2
        },
        
        "im-message": {
          required: imRqMessage2,
          minlength: imDsMessage2
        },
      },
      
      wrapper: "li",
      errorLabelContainer: "#im-error-list",
      invalidHandler: function(form, validator) {
        // Chat composer never surfaces validation errors, and scrolling the page
        // here would push the input and thread header out of view.
        if(document.body.classList.contains('im-chat-page')) {
          $('#im-error-list').empty().hide();
          return;
        }
        $('html,body').animate({ scrollTop: $('#im-error-list').offset().top - 100 }, { duration: 250, easing: 'swing'});
      },
      submitHandler: function(form){
        $('button[type=submit], input[type=submit]').attr('disabled', 'disabled');
        form.submit();
      }
    });
  }

});


// Toggle send button icon (paper plane / spinner)
function imSubmitButtonLoading(button, loading) {
  var icon = $(button).find('i').first();
  if(!icon.length) {
    return;
  }

  if(loading) {
    icon.removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');
  } else {
    icon.removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
  }
}


// PNGM mobile keyboard fix: visual viewport + composer offset
(function(){
  function pngmUpdateKeyboardOffset(){
    var vv = window.visualViewport;
    var keyboard = 0;
    if(vv){
      keyboard = Math.max(0, window.innerHeight - vv.height);
    }
    document.documentElement.style.setProperty('--im-keyboard-height', keyboard + 'px');
  }

  pngmUpdateKeyboardOffset();

  if(window.visualViewport){
    window.visualViewport.addEventListener('resize', pngmUpdateKeyboardOffset);
    window.visualViewport.addEventListener('scroll', pngmUpdateKeyboardOffset);
  }
  window.addEventListener('resize', pngmUpdateKeyboardOffset);
  window.addEventListener('orientationchange', pngmUpdateKeyboardOffset);

  $(document).on('focus', '#im-message', function(){
    setTimeout(function(){
      pngmUpdateKeyboardOffset();
    }, 250);
  });

  $(document).on('blur', '#im-message', function(){
    setTimeout(function(){
      pngmUpdateKeyboardOffset();
    }, 250);
  });
})();
