$(document).ready(function(){
  if(document.getElementById('im-message-form') || document.querySelector('.im-file-messages')) {
    $('body').addClass('im-chat-page');
    var box = document.querySelector('.im-table.im-messages');
    if(box) {
      box.scrollTop = box.scrollHeight;
    }
  }

  function imComposerMinHeight() {
    return ($(window).width() <= 360) ? 85 : 50;
  }

  function imFitComposerHeight() {
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


  // ATTACHMENTS: keep a file list so users can add several and remove any of them
  (function() {
    var pending = [];

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

      if(typeof DataTransfer === 'undefined') {
        if(!pending.length) {
          input.value = '';
        }
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
        return;
      }

      list.hidden = false;
      pending.forEach(function(file, index) {
        var chip = document.createElement('span');
        chip.className = 'im-file-chip';

        var name = document.createElement('em');
        name.textContent = file.name;
        chip.appendChild(name);

        var remove = document.createElement('span');
        remove.className = 'im-file-remove';
        remove.setAttribute('role', 'button');
        remove.setAttribute('tabindex', '0');
        remove.setAttribute('aria-label', 'Remove file');
        remove.innerHTML = '&times;';
        remove.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          pending.splice(index, 1);
          syncInput();
          renderList();
        });
        chip.appendChild(remove);
        list.appendChild(chip);
      });
    }

    window.imResetComposerFiles = function() {
      pending = [];
      var input = fileInput();
      if(input) {
        input.value = '';
      }
      renderList();
    };

    $('body').on('change', '#im-file', function() {
      if(syncing) {
        return;
      }

      var added = this.files ? Array.prototype.slice.call(this.files) : [];
      added.forEach(function(file) {
        var exists = pending.some(function(current) {
          return sameFile(current, file);
        });
        if(!exists) {
          pending.push(file);
        }
      });
      syncInput();
      renderList();
      $('#im-message').removeAttr('required').removeClass('error');
    });
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
          required: true,
          minlength: 3
        },
        
        "im-from-user-email": {
          required: true,
          email: true
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
          required: imRqName2,
          minlength: imDsName2
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

