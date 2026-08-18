/**
 * Native-style listing photo viewer.
 *
 * Page zoom is locked (viewport max-scale 1). All zoom/pan happens on the
 * image with CSS transforms so it feels like iOS Photos / Android Gallery:
 *  - pinch around the fingers
 *  - drag to pan while zoomed
 *  - pinch out / double-tap to return to 1x
 *  - swipe down or up in fullscreen to return to the ad
 *  - swipe left/right at 1x for next/previous photo
 */
(function () {
  'use strict';

  var MIN_SCALE = 1;
  var MAX_SCALE = 5;
  var CLOSE_DY = 88;
  var CLOSE_V = 0.55;
  var PAGE_DX = 56;
  var PAGE_V = 0.45;
  var VIEWPORT = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover';

  function dist(a, b) {
    var dx = a.clientX - b.clientX;
    var dy = a.clientY - b.clientY;
    return Math.sqrt(dx * dx + dy * dy);
  }

  function mid(a, b) {
    return {
      x: (a.clientX + b.clientX) / 2,
      y: (a.clientY + b.clientY) / 2
    };
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function lockViewport() {
    var metas = document.querySelectorAll('meta[name="viewport"]');
    var i;
    for (i = 0; i < metas.length; i += 1) {
      metas[i].setAttribute('content', VIEWPORT);
    }
  }

  function createController(stage, getImg, options) {
    options = options || {};

    var scale = 1;
    var tx = 0;
    var ty = 0;
    var startScale = 1;
    var startDist = 0;
    var startTx = 0;
    var startTy = 0;
    var panStartX = 0;
    var panStartY = 0;
    var panOriginX = 0;
    var panOriginY = 0;
    var startX = 0;
    var startY = 0;
    var startT = 0;
    var mode = 'none';
    var axis = '';
    var lastTap = 0;
    var lastTapX = 0;
    var lastTapY = 0;
    var closing = 0;
    var paging = 0;
    var dragging = false;
    var moved = false;

    function img() {
      return typeof getImg === 'function' ? getImg() : getImg;
    }

    function apply(animated) {
      var el = img();
      if (!el) {
        return;
      }
      el.style.transition = animated ? 'transform 0.22s cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
      el.style.transformOrigin = 'center center';
      el.style.transform = 'translate3d(' + tx + 'px,' + ty + 'px,0) scale(' + scale + ')';
      el.style.willChange = scale > 1.01 ? 'transform' : 'auto';
    }

    function fittedSize(el) {
      var rect = stage.getBoundingClientRect();
      var nw = el.naturalWidth || el.width || 1;
      var nh = el.naturalHeight || el.height || 1;
      var fit = Math.min(rect.width / nw, rect.height / nh);
      return {
        w: nw * fit,
        h: nh * fit,
        stageW: rect.width,
        stageH: rect.height
      };
    }

    function clampPan(resist) {
      var el = img();
      if (!el || scale <= 1.001) {
        tx = 0;
        ty = 0;
        scale = 1;
        return;
      }

      var size = fittedSize(el);
      var maxX = Math.max(0, (size.w * scale - size.stageW) / 2);
      var maxY = Math.max(0, (size.h * scale - size.stageH) / 2);

      if (resist) {
        if (tx > maxX) {
          tx = maxX + (tx - maxX) * 0.32;
        } else if (tx < -maxX) {
          tx = -maxX + (tx + maxX) * 0.32;
        }
        if (ty > maxY) {
          ty = maxY + (ty - maxY) * 0.32;
        } else if (ty < -maxY) {
          ty = -maxY + (ty + maxY) * 0.32;
        }
        return;
      }

      tx = clamp(tx, -maxX, maxX);
      ty = clamp(ty, -maxY, maxY);
    }

    function zoomAround(focalX, focalY, nextScale) {
      nextScale = clamp(nextScale, 0.7, MAX_SCALE);
      var rect = stage.getBoundingClientRect();
      var cx = rect.left + rect.width / 2;
      var cy = rect.top + rect.height / 2;
      var relX = focalX - cx;
      var relY = focalY - cy;
      var ratio = nextScale / (scale || 1);
      tx = relX * (1 - ratio) + tx * ratio;
      ty = relY * (1 - ratio) + ty * ratio;
      scale = nextScale;
    }

    function notifyZoom() {
      if (typeof options.onZoomChange === 'function') {
        options.onZoomChange(scale > 1.05);
      }
    }

    function reset(animated) {
      scale = 1;
      tx = 0;
      ty = 0;
      closing = 0;
      paging = 0;
      if (options.overlay) {
        options.overlay.style.background = '';
      }
      apply(animated !== false);
      notifyZoom();
    }

    function setClosing(dy) {
      closing = dy;
      var el = img();
      if (!el) {
        return;
      }
      var fade = clamp(1 - Math.abs(dy) / 420, 0.22, 1);
      if (options.overlay) {
        options.overlay.style.background = 'rgba(0,0,0,' + (0.96 * fade) + ')';
      }
      el.style.transition = 'none';
      el.style.transform = 'translate3d(0,' + dy + 'px,0) scale(' + clamp(1 - Math.abs(dy) / 1100, 0.78, 1) + ')';
    }

    function setPaging(dx) {
      paging = dx;
      var el = img();
      if (!el) {
        return;
      }
      el.style.transition = 'none';
      el.style.transform = 'translate3d(' + dx + 'px,0,0) scale(1)';
    }

    function clearOverlayMotion() {
      closing = 0;
      paging = 0;
      if (options.overlay) {
        options.overlay.style.background = '';
      }
      apply(true);
    }

    function beginPinch(touches) {
      mode = 'pinch';
      axis = '';
      startDist = dist(touches[0], touches[1]) || 1;
      startScale = scale;
      startTx = tx;
      startTy = ty;
      dragging = true;
      moved = true;
      if (typeof options.onZoomChange === 'function') {
        options.onZoomChange(true);
      }
    }

    function onStart(e) {
      var touches = e.touches;
      if (!touches || !touches.length) {
        return;
      }

      lockViewport();
      startT = Date.now();
      startX = touches[0].clientX;
      startY = touches[0].clientY;
      dragging = false;
      moved = false;
      axis = '';
      closing = 0;
      paging = 0;
      mode = 'none';

      if (touches.length >= 2) {
        e.preventDefault();
        beginPinch(touches);
        apply(false);
        return;
      }

      if (scale > 1.05) {
        mode = 'pan';
        panStartX = touches[0].clientX;
        panStartY = touches[0].clientY;
        panOriginX = tx;
        panOriginY = ty;
      } else {
        mode = 'swipe';
      }
    }

    function onMove(e) {
      var touches = e.touches;
      if (!touches || !touches.length) {
        return;
      }

      if (touches.length >= 2) {
        e.preventDefault();
        if (mode !== 'pinch' || startDist <= 0) {
          beginPinch(touches);
        }
        var point = mid(touches[0], touches[1]);
        var next = startScale * (dist(touches[0], touches[1]) / startDist);
        scale = startScale;
        tx = startTx;
        ty = startTy;
        zoomAround(point.x, point.y, next);
        apply(false);
        notifyZoom();
        return;
      }

      var x = touches[0].clientX;
      var y = touches[0].clientY;
      var dx = x - startX;
      var dy = y - startY;

      if (mode === 'pinch') {
        return;
      }

      if (mode === 'pan') {
        e.preventDefault();
        tx = panOriginX + (x - panStartX);
        ty = panOriginY + (y - panStartY);
        clampPan(true);
        dragging = true;
        moved = true;
        apply(false);
        return;
      }

      if (mode !== 'swipe') {
        return;
      }

      if (!axis && (Math.abs(dx) > 10 || Math.abs(dy) > 10)) {
        axis = Math.abs(dy) > Math.abs(dx) * 0.85 ? 'y' : 'x';
      }

      if (!axis) {
        return;
      }

      moved = true;
      dragging = true;

      if (axis === 'y' && options.swipeClose) {
        e.preventDefault();
        setClosing(dy);
      } else if (axis === 'x' && options.onPage && scale <= 1.05) {
        e.preventDefault();
        setPaging(dx);
      }
    }

    function finishSwipe(dx, dy, dt) {
      var vx = dx / Math.max(dt, 1);
      var vy = dy / Math.max(dt, 1);

      if (axis === 'y' && options.swipeClose) {
        if (Math.abs(dy) >= CLOSE_DY || (Math.abs(vy) >= CLOSE_V && Math.abs(dy) > 36)) {
          if (typeof options.onClose === 'function') {
            options.onClose();
            return true;
          }
        }
        clearOverlayMotion();
        return true;
      }

      if (axis === 'x' && options.onPage && scale <= 1.05) {
        if (Math.abs(dx) >= PAGE_DX || (Math.abs(vx) >= PAGE_V && Math.abs(dx) > 28)) {
          options.onPage(dx < 0 ? 1 : -1);
          paging = 0;
          apply(false);
          return true;
        }
        clearOverlayMotion();
        return true;
      }

      if (closing || paging) {
        clearOverlayMotion();
      }
      return false;
    }

    function onEnd(e) {
      var remains = e.touches ? e.touches.length : 0;
      var changed = e.changedTouches && e.changedTouches[0];
      var endX = changed ? changed.clientX : startX;
      var endY = changed ? changed.clientY : startY;
      var dx = endX - startX;
      var dy = endY - startY;
      var dt = Date.now() - startT;

      if (remains >= 2) {
        return;
      }

      if (mode === 'pinch') {
        startDist = 0;
        if (remains === 0) {
          if (scale < 0.9 && options.swipeClose && typeof options.onClose === 'function') {
            options.onClose();
            mode = 'none';
            return;
          }
          if (scale < 1.08) {
            reset(true);
          } else {
            scale = clamp(scale, MIN_SCALE, MAX_SCALE);
            clampPan(false);
            apply(true);
            notifyZoom();
          }
          mode = 'none';
        } else {
          mode = scale > 1.05 ? 'pan' : 'swipe';
          panStartX = e.touches[0].clientX;
          panStartY = e.touches[0].clientY;
          panOriginX = tx;
          panOriginY = ty;
          startX = panStartX;
          startY = panStartY;
          startT = Date.now();
        }
        return;
      }

      if (remains === 1) {
        mode = scale > 1.05 ? 'pan' : 'swipe';
        panStartX = e.touches[0].clientX;
        panStartY = e.touches[0].clientY;
        panOriginX = tx;
        panOriginY = ty;
        startX = panStartX;
        startY = panStartY;
        return;
      }

      if (mode === 'pan') {
        clampPan(false);
        apply(true);
      } else if (mode === 'swipe' && finishSwipe(dx, dy, dt)) {
        mode = 'none';
        dragging = false;
        return;
      } else if (!moved && dt < 320 && Math.abs(dx) < 14 && Math.abs(dy) < 14) {
        var now = Date.now();
        if (now - lastTap < 300 && Math.abs(endX - lastTapX) < 44 && Math.abs(endY - lastTapY) < 44) {
          if (scale > 1.15) {
            reset(true);
          } else {
            zoomAround(endX, endY, 2.7);
            clampPan(false);
            apply(true);
            notifyZoom();
          }
          lastTap = 0;
        } else {
          lastTap = now;
          lastTapX = endX;
          lastTapY = endY;
          if (typeof options.onTap === 'function') {
            if (options.immediateTap) {
              options.onTap();
            } else {
              window.setTimeout(function () {
                if (lastTap === now && scale <= 1.05) {
                  options.onTap();
                }
              }, 260);
            }
          }
        }
      }

      mode = 'none';
      axis = '';
      dragging = false;
      startDist = 0;
    }

    function onWheel(e) {
      e.preventDefault();
      var next = scale * (e.deltaY > 0 ? 0.88 : 1.14);
      zoomAround(e.clientX, e.clientY, next);
      if (scale < 1.05) {
        reset(false);
      } else {
        clampPan(false);
        apply(false);
        notifyZoom();
      }
    }

    function onMouseDown(e) {
      if (e.button !== 0) {
        return;
      }
      if (e.target && e.target.closest && e.target.closest('button, a.pngm-nv-close')) {
        return;
      }

      startX = e.clientX;
      startY = e.clientY;
      panStartX = e.clientX;
      panStartY = e.clientY;
      panOriginX = tx;
      panOriginY = ty;
      startT = Date.now();
      dragging = false;
      moved = false;
      axis = '';
      mode = scale > 1.05 ? 'pan' : 'swipe';

      function move(ev) {
        var dx = ev.clientX - startX;
        var dy = ev.clientY - startY;
        if (!axis && (Math.abs(dx) > 8 || Math.abs(dy) > 8)) {
          axis = Math.abs(dy) > Math.abs(dx) ? 'y' : 'x';
        }
        if (!axis) {
          return;
        }
        moved = true;
        dragging = true;
        if (mode === 'pan') {
          tx = panOriginX + (ev.clientX - panStartX);
          ty = panOriginY + (ev.clientY - panStartY);
          clampPan(true);
          apply(false);
        } else if (axis === 'y' && options.swipeClose) {
          setClosing(dy);
        } else if (axis === 'x' && options.onPage) {
          setPaging(dx);
        }
      }

      function up(ev) {
        document.removeEventListener('mousemove', move);
        document.removeEventListener('mouseup', up);
        var dx = ev.clientX - startX;
        var dy = ev.clientY - startY;
        var dt = Date.now() - startT;
        if (mode === 'pan') {
          clampPan(false);
          apply(true);
        } else if (mode === 'swipe') {
          finishSwipe(dx, dy, dt);
        }
        mode = 'none';
        axis = '';
      }

      document.addEventListener('mousemove', move);
      document.addEventListener('mouseup', up);
    }

    var touchOpts = { passive: false, capture: true };
    stage.addEventListener('touchstart', onStart, touchOpts);
    stage.addEventListener('touchmove', onMove, touchOpts);
    stage.addEventListener('touchend', onEnd, touchOpts);
    stage.addEventListener('touchcancel', onEnd, touchOpts);
    stage.addEventListener('wheel', onWheel, { passive: false });
    stage.addEventListener('mousedown', onMouseDown);

    ['gesturestart', 'gesturechange', 'gestureend'].forEach(function (type) {
      stage.addEventListener(type, function (e) {
        e.preventDefault();
      }, { passive: false });
    });

    return {
      reset: reset,
      isZoomed: function () {
        return scale > 1.05;
      },
      destroy: function () {
        stage.removeEventListener('touchstart', onStart, touchOpts);
        stage.removeEventListener('touchmove', onMove, touchOpts);
        stage.removeEventListener('touchend', onEnd, touchOpts);
        stage.removeEventListener('touchcancel', onEnd, touchOpts);
      }
    };
  }

  function buildOverlay() {
    var existing = document.querySelector('.pngm-native-viewer');
    if (existing) {
      return existing;
    }

    var overlay = document.createElement('div');
    overlay.className = 'pngm-native-viewer';
    overlay.setAttribute('hidden', 'hidden');
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Photo');
    overlay.innerHTML =
      '<div class="pngm-nv-ui">' +
        '<button type="button" class="pngm-nv-close" aria-label="Close">&times;</button>' +
        '<div class="pngm-nv-count"></div>' +
      '</div>' +
      '<div class="pngm-nv-stage"><img class="pngm-nv-img" alt="" draggable="false" /></div>' +
      '<div class="pngm-nv-hint">Swipe down to close</div>';
    document.body.appendChild(overlay);
    return overlay;
  }

  function preventSafariPageZoom(root, overlay) {
    function shouldBlock(target) {
      if (!target || !target.closest) {
        return false;
      }
      return !!(target.closest('#item-image') || target.closest('.pngm-native-viewer'));
    }

    ['gesturestart', 'gesturechange', 'gestureend'].forEach(function (type) {
      document.addEventListener(type, function (e) {
        if (shouldBlock(e.target) || (overlay && overlay.classList.contains('is-open'))) {
          e.preventDefault();
        }
      }, { passive: false });
    });
  }

  window.pngmInitItemGallery = function ($) {
    $ = $ || window.jQuery;
    if (!$) {
      return;
    }

    var root = $('#item-image');
    if (!root.length || !root.find('.swiper-container').length) {
      return;
    }

    lockViewport();

    var container = root.find('.swiper-container').first();
    root.addClass('pngm-gallery-ready');
    root.find('.swiper-thumbs').attr('data-pngm-gallery', '1');

    function syncThumbs(index) {
      root.find('.swiper-thumbs li').removeClass('active');
      root.find('.swiper-thumbs li[data-id="' + index + '"]').addClass('active');
    }

    function collectItems() {
      var items = [];
      root.find('li.swiper-slide > a').each(function () {
        var href = this.getAttribute('href') || '';
        var image = this.querySelector('img');
        var src = href;
        if ((!src || src === '#') && image) {
          src = image.getAttribute('data-src') || image.getAttribute('src') || '';
        }
        items.push({
          src: src,
          alt: image ? (image.getAttribute('alt') || '') : ''
        });
      });
      return items;
    }

    function preload(src) {
      if (!src) {
        return;
      }
      var pic = new Image();
      pic.src = src;
    }

    function initSwiper() {
      if (typeof window.Swiper === 'undefined') {
        return null;
      }

      var el = container[0];
      if (el && el.swiper) {
        try {
          el.swiper.destroy(true, true);
        } catch (err) {}
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
              window.pngmGalleryPinch.reset(false);
            }
          },
          init: function () {
            container.find('img[data-src]').each(function () {
              var real = this.getAttribute('data-src');
              if (real && this.getAttribute('src') !== real) {
                this.setAttribute('src', real);
              }
            });
          }
        }
      });
    }

    var overlay = buildOverlay();
    var stage = overlay.querySelector('.pngm-nv-stage');
    var viewerImg = overlay.querySelector('.pngm-nv-img');
    var countEl = overlay.querySelector('.pngm-nv-count');
    var hintEl = overlay.querySelector('.pngm-nv-hint');
    var items = [];
    var index = 0;
    var open = false;
    var viewerZoom = null;
    var openedByTap = false;
    var hist = false;
    var scrollY = 0;
    var hintTimer = 0;

    preventSafariPageZoom(root[0], overlay);

    function lockBody() {
      scrollY = window.scrollY || window.pageYOffset || 0;
      document.body.classList.add('pngm-lg-open');
      document.body.style.overflow = 'hidden';
      document.body.style.position = 'fixed';
      document.body.style.top = '-' + scrollY + 'px';
      document.body.style.left = '0';
      document.body.style.right = '0';
      document.body.style.width = '100%';
    }

    function unlockBody() {
      document.body.classList.remove('pngm-lg-open');
      document.body.style.overflow = '';
      document.body.style.position = '';
      document.body.style.top = '';
      document.body.style.left = '';
      document.body.style.right = '';
      document.body.style.width = '';
      window.scrollTo(0, scrollY);
    }

    function showItem(nextIndex) {
      items = collectItems();
      if (!items.length) {
        return;
      }
      index = (nextIndex + items.length) % items.length;
      var item = items[index];
      viewerImg.style.transition = 'none';
      viewerImg.style.transform = 'translate3d(0,0,0) scale(1)';
      if (viewerZoom) {
        viewerZoom.reset(false);
      }
      viewerImg.alt = item.alt;
      if (viewerImg.src !== item.src) {
        viewerImg.src = item.src;
      }
      countEl.textContent = (index + 1) + ' / ' + items.length;
      preload(items[(index + 1) % items.length].src);
      if (items.length > 1) {
        preload(items[(index - 1 + items.length) % items.length].src);
      }
    }

    function closeViewer(fromPop) {
      if (!open) {
        return;
      }
      open = false;
      overlay.setAttribute('hidden', 'hidden');
      overlay.classList.remove('is-open');
      overlay.style.background = '';
      if (viewerZoom) {
        viewerZoom.reset(false);
      }
      unlockBody();
      if (hintTimer) {
        window.clearTimeout(hintTimer);
        hintTimer = 0;
      }
      if (hist) {
        hist = false;
        if (!fromPop) {
          try {
            history.back();
          } catch (err) {}
        }
      }
    }

    viewerImg.addEventListener('load', function () {
      if (open && viewerZoom) {
        viewerZoom.reset(false);
      }
    });

    overlay.addEventListener('touchmove', function (e) {
      e.preventDefault();
    }, { passive: false });

    function openViewer(startIndex) {
      items = collectItems();
      if (!items.length) {
        return;
      }
      if (open) {
        showItem(startIndex || 0);
        return;
      }
      open = true;
      overlay.removeAttribute('hidden');
      overlay.classList.add('is-open');
      overlay.classList.remove('pngm-nv-chrome-hidden');
      overlay.style.background = '';
      lockBody();
      showItem(startIndex || 0);
      if (hintEl) {
        hintEl.classList.remove('is-gone');
        if (hintTimer) {
          window.clearTimeout(hintTimer);
        }
        hintTimer = window.setTimeout(function () {
          hintEl.classList.add('is-gone');
        }, 2600);
      }
      if (!hist) {
        hist = true;
        try {
          history.pushState({ pngmNv: 1 }, '');
        } catch (err) {
          hist = false;
        }
      }
    }

    viewerZoom = createController(stage, function () {
      return viewerImg;
    }, {
      overlay: overlay,
      swipeClose: true,
      onClose: function () {
        closeViewer(false);
      },
      onPage: function (dir) {
        showItem(index + dir);
      },
      onTap: function () {
        overlay.classList.toggle('pngm-nv-chrome-hidden');
      }
    });

    overlay.querySelector('.pngm-nv-close').addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeViewer(false);
    });

    window.addEventListener('popstate', function () {
      if (open) {
        closeViewer(true);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (!open) {
        return;
      }
      if (e.key === 'Escape' || e.keyCode === 27) {
        closeViewer(false);
      } else if (e.key === 'ArrowRight') {
        showItem(index + 1);
      } else if (e.key === 'ArrowLeft') {
        showItem(index - 1);
      }
    });

    function killLightGallery() {
      try {
        if (container.data('lightGallery')) {
          container.data('lightGallery').destroy(true);
        }
      } catch (err) {}
      container.off('onBeforeOpen.lg onAfterOpen.lg onAfterSlide.lg');
    }

    function currentIndex() {
      return window.pngmItemSwiper ? window.pngmItemSwiper.activeIndex : 0;
    }

    function openFromGallery() {
      openedByTap = true;
      openViewer(currentIndex());
      window.setTimeout(function () {
        openedByTap = false;
      }, 400);
    }

    container[0].addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('li.swiper-slide > a') : null;
      if (!link || !container[0].contains(link)) {
        return;
      }
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
      }
      if (openedByTap) {
        return;
      }
      if (window.pngmGalleryPinch && window.pngmGalleryPinch.isZoomed()) {
        return;
      }
      openViewer(currentIndex());
    }, true);

    root.find('.pngm-gallery-fullscreen').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      openViewer(currentIndex());
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

    killLightGallery();
    [0, 50, 200, 400, 800].forEach(function (ms) {
      window.setTimeout(function () {
        killLightGallery();
        if (!window.pngmItemSwiper && ms >= 200) {
          window.pngmItemSwiper = initSwiper();
        }
      }, ms);
    });

    window.setTimeout(function () {
      window.pngmItemSwiper = initSwiper();
      window.pngmGalleryPinch = createController(container[0], function () {
        var slide = container.find('.swiper-slide-active .swiper-zoom-container img');
        if (!slide.length) {
          slide = container.find('.swiper-slide .swiper-zoom-container img').first();
        }
        return slide[0] || null;
      }, {
        swipeClose: false,
        immediateTap: true,
        onZoomChange: function (zoomed) {
          if (window.pngmItemSwiper && window.pngmItemSwiper.allowTouchMove !== undefined) {
            window.pngmItemSwiper.allowTouchMove = !zoomed;
          }
        },
        onTap: openFromGallery
      });
    }, 280);
  };
})();
