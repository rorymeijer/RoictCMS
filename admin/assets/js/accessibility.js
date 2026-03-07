/**
 * ROICT CMS – Accessibility Module
 * Functies: dark mode, lettergrootte, tekst-naar-spraak
 */
(function () {
  'use strict';

  /* ── Constanten ──────────────────────────────────────────── */
  var STORAGE_KEY_THEME    = 'a11y_theme';
  var STORAGE_KEY_FONTSIZE = 'a11y_fontsize';
  var STORAGE_KEY_TTS      = 'a11y_tts';
  var FONT_SIZES           = ['small', 'normal', 'large', 'xlarge'];
  var DARK_STYLESHEET_ID   = 'sl-dark-theme';
  var DARK_STYLESHEET_HREF =
    'https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/themes/dark.css';

  /* ── State ───────────────────────────────────────────────── */
  var isDark      = localStorage.getItem(STORAGE_KEY_THEME)    === 'dark';
  var fontSize    = localStorage.getItem(STORAGE_KEY_FONTSIZE) || 'normal';
  var ttsEnabled  = localStorage.getItem(STORAGE_KEY_TTS)      === 'on';
  var ttsReading  = false;
  var synth       = window.speechSynthesis || null;

  /* ── Helper: maak element ────────────────────────────────── */
  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) {
      if (k === 'className') { e.className = attrs[k]; }
      else if (k === 'innerHTML') { e.innerHTML = attrs[k]; }
      else if (k === 'textContent') { e.textContent = attrs[k]; }
      else { e.setAttribute(k, attrs[k]); }
    });
    if (children) children.forEach(function (c) { if (c) e.appendChild(c); });
    return e;
  }

  /* ── Dark mode ───────────────────────────────────────────── */
  function applyDarkMode(enable) {
    var html = document.documentElement;
    if (enable) {
      html.setAttribute('data-a11y-theme', 'dark');
      html.setAttribute('data-bs-theme', 'dark');
      ensureSlDarkStylesheet(true);
    } else {
      html.setAttribute('data-a11y-theme', 'light');
      html.setAttribute('data-bs-theme', 'light');
      ensureSlDarkStylesheet(false);
    }
    localStorage.setItem(STORAGE_KEY_THEME, enable ? 'dark' : 'light');
  }

  function ensureSlDarkStylesheet(enable) {
    var existing = document.getElementById(DARK_STYLESHEET_ID);
    if (enable && !existing) {
      var link = document.createElement('link');
      link.id   = DARK_STYLESHEET_ID;
      link.rel  = 'stylesheet';
      link.href = DARK_STYLESHEET_HREF;
      document.head.appendChild(link);
    } else if (!enable && existing) {
      existing.remove();
    }
  }

  /* ── Lettergrootte ───────────────────────────────────────── */
  function applyFontSize(size) {
    FONT_SIZES.forEach(function (s) {
      document.body.classList.remove('a11y-fs-' + s);
    });
    document.body.classList.add('a11y-fs-' + size);
    localStorage.setItem(STORAGE_KEY_FONTSIZE, size);
  }

  /* ── Tekst-naar-spraak ───────────────────────────────────── */
  function speakText(text) {
    if (!synth || !ttsEnabled) return;
    synth.cancel();
    var utt = new SpeechSynthesisUtterance(text.trim());
    utt.lang = document.documentElement.lang || 'nl-NL';
    utt.rate = 0.95;
    utt.pitch = 1;
    synth.speak(utt);
    ttsReading = true;
    utt.onend = function () { ttsReading = false; };
    utt.onerror = function () { ttsReading = false; };
    return utt;
  }

  function getReadableText(node) {
    // Aria-label heeft prioriteit
    var aria = node.getAttribute('aria-label') || node.getAttribute('title');
    if (aria) return aria;
    // Knoptekst of linktekst
    var clone = node.cloneNode(true);
    // Verwijder iconen (bi-*) uit kloon
    clone.querySelectorAll('i, svg, img').forEach(function (n) { n.remove(); });
    return clone.textContent || '';
  }

  /* TTS hover handler */
  function onTTSMouseOver(e) {
    if (!ttsEnabled) return;
    var target = e.target.closest(
      'button, a, label, h1, h2, h3, h4, h5, p, td, th, .form-label, [aria-label]'
    );
    if (!target || target.closest('#a11y-widget')) return;
    var text = getReadableText(target);
    if (text && text.trim().length > 1) {
      target.classList.add('a11y-tts-reading');
      speakText(text);
      target.addEventListener('mouseleave', function onLeave() {
        target.classList.remove('a11y-tts-reading');
        target.removeEventListener('mouseleave', onLeave);
      }, { once: true });
    }
  }

  /* TTS klik handler (voor touch-schermen) */
  function onTTSClick(e) {
    if (!ttsEnabled) return;
    var target = e.target.closest(
      'button, a, label, h1, h2, h3, h4, h5, p, td, th, .form-label, [aria-label]'
    );
    if (!target || target.closest('#a11y-widget') || target.tagName === 'A' || target.tagName === 'BUTTON') return;
    e.preventDefault();
    speakText(getReadableText(target));
  }

  function enableTTS() {
    document.addEventListener('mouseover', onTTSMouseOver, true);
    document.addEventListener('click', onTTSClick, true);
    localStorage.setItem(STORAGE_KEY_TTS, 'on');
    ttsEnabled = true;
  }

  function disableTTS() {
    document.removeEventListener('mouseover', onTTSMouseOver, true);
    document.removeEventListener('click', onTTSClick, true);
    if (synth) synth.cancel();
    localStorage.setItem(STORAGE_KEY_TTS, 'off');
    ttsEnabled = false;
  }

  /* ── Bouw widget ─────────────────────────────────────────── */
  function buildWidget() {
    /* Toggle-knop */
    var toggleBtn = el('button', {
      id: 'a11y-toggle',
      'aria-label': 'Toegankelijkheidsopties openen',
      'aria-expanded': 'false',
      'aria-controls': 'a11y-panel',
      innerHTML: '<i class="bi bi-universal-access"></i>'
    });

    /* Paneel header */
    var panelHeader = el('div', {
      className: 'a11y-panel-header',
      innerHTML: '<i class="bi bi-universal-access"></i> Toegankelijkheid'
    });

    /* ── Dark mode sectie ── */
    var darkSwitch = el('label', { className: 'a11y-switch', 'aria-label': 'Donker thema' });
    var darkInput  = el('input', { type: 'checkbox', 'aria-label': 'Donker thema' });
    darkInput.checked = isDark;
    var darkTrack  = el('span', { className: 'a11y-switch-track' });
    darkSwitch.appendChild(darkInput);
    darkSwitch.appendChild(darkTrack);

    darkInput.addEventListener('change', function () {
      isDark = darkInput.checked;
      applyDarkMode(isDark);
    });

    var darkRow = el('div', { className: 'a11y-toggle-row' }, [
      el('div', {
        className: 'a11y-toggle-label',
        innerHTML: '<i class="bi bi-moon-stars-fill"></i> Donker thema'
      }),
      darkSwitch
    ]);

    var darkSection = el('div', { className: 'a11y-section' }, [
      el('div', { className: 'a11y-section-label', textContent: 'Weergave' }),
      darkRow
    ]);

    /* ── Lettergrootte sectie ── */
    var fontSizeLabels = { small: 'A-', normal: 'A', large: 'A+', xlarge: 'A++' };
    var fontSizeNames  = { small: 'Klein', normal: 'Normaal', large: 'Groot', xlarge: 'X-Groot' };
    var fontBtns = [];

    FONT_SIZES.forEach(function (size) {
      var btn = el('button', {
        className: 'a11y-fontsize-btn' + (fontSize === size ? ' active' : ''),
        'data-size': size,
        'aria-label': 'Lettergrootte: ' + fontSizeNames[size],
        innerHTML:
          fontSizeLabels[size] +
          '<span class="fs-label">' + fontSizeNames[size] + '</span>'
      });
      btn.addEventListener('click', function () {
        fontSize = size;
        applyFontSize(size);
        fontBtns.forEach(function (b) { b.classList.toggle('active', b.dataset.size === size); });
      });
      fontBtns.push(btn);
    });

    var fontRow = el('div', { className: 'a11y-fontsize-btns' }, fontBtns);
    var fontSection = el('div', { className: 'a11y-section' }, [
      el('div', { className: 'a11y-section-label', textContent: 'Lettergrootte' }),
      fontRow
    ]);

    /* ── TTS sectie ── */
    var ttsSwitch = el('label', { className: 'a11y-switch', 'aria-label': 'Voorlezen' });
    var ttsInput  = el('input', { type: 'checkbox', 'aria-label': 'Voorlezen inschakelen' });
    ttsInput.checked = ttsEnabled;
    var ttsTrack  = el('span', { className: 'a11y-switch-track' });
    ttsSwitch.appendChild(ttsInput);
    ttsSwitch.appendChild(ttsTrack);

    var ttsStatus = el('div', {
      id: 'a11y-tts-status',
      textContent: ttsEnabled ? 'Wijs over tekst om voor te laten lezen' : ''
    });

    ttsInput.addEventListener('change', function () {
      if (ttsInput.checked) {
        enableTTS();
        ttsStatus.textContent = 'Wijs over tekst om voor te laten lezen';
        speakText('Voorlezen ingeschakeld');
      } else {
        disableTTS();
        ttsStatus.textContent = '';
      }
    });

    var ttsRow = el('div', { className: 'a11y-toggle-row' }, [
      el('div', {
        className: 'a11y-toggle-label',
        innerHTML: '<i class="bi bi-volume-up-fill"></i> Voorlezen'
      }),
      ttsSwitch
    ]);

    // Laat spraakmogelijkheid zien als niet beschikbaar
    if (!synth) {
      ttsInput.disabled = true;
      ttsStatus.textContent = 'Niet beschikbaar in deze browser';
    }

    var ttsSection = el('div', { className: 'a11y-section' }, [
      el('div', { className: 'a11y-section-label', textContent: 'Tekst-naar-spraak' }),
      ttsRow,
      ttsStatus
    ]);

    /* ── Reset sectie ── */
    var resetBtn = el('button', {
      className: 'btn btn-sm btn-outline-secondary w-100',
      style: 'font-size:.8rem;',
      innerHTML: '<i class="bi bi-arrow-counterclockwise"></i> Instellingen resetten'
    });
    resetBtn.addEventListener('click', function () {
      isDark     = false;
      fontSize   = 'normal';
      ttsEnabled = false;
      localStorage.removeItem(STORAGE_KEY_THEME);
      localStorage.removeItem(STORAGE_KEY_FONTSIZE);
      localStorage.removeItem(STORAGE_KEY_TTS);
      applyDarkMode(false);
      applyFontSize('normal');
      disableTTS();
      darkInput.checked = false;
      ttsInput.checked  = false;
      ttsStatus.textContent = '';
      fontBtns.forEach(function (b) { b.classList.toggle('active', b.dataset.size === 'normal'); });
    });

    var resetSection = el('div', {
      className: 'a11y-section',
      style: 'padding-bottom:.9rem;'
    }, [resetBtn]);

    /* ── Paneel samenstellen ── */
    var panel = el('div', {
      id: 'a11y-panel',
      role: 'dialog',
      'aria-label': 'Toegankelijkheidsopties',
      'aria-modal': 'false'
    }, [
      panelHeader,
      darkSection,
      fontSection,
      ttsSection,
      resetSection
    ]);

    /* ── Widget container ── */
    var widget = el('div', { id: 'a11y-widget' }, [panel, toggleBtn]);
    document.body.appendChild(widget);

    /* ── Toggle paneel open/dicht ── */
    var panelOpen = false;
    function openPanel() {
      panel.classList.add('open');
      requestAnimationFrame(function () { panel.classList.add('visible'); });
      toggleBtn.setAttribute('aria-expanded', 'true');
      panelOpen = true;
    }
    function closePanel() {
      panel.classList.remove('visible');
      panel.addEventListener('transitionend', function handler() {
        panel.classList.remove('open');
        panel.removeEventListener('transitionend', handler);
      });
      toggleBtn.setAttribute('aria-expanded', 'false');
      panelOpen = false;
    }

    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (panelOpen) { closePanel(); } else { openPanel(); }
    });

    document.addEventListener('click', function (e) {
      if (panelOpen && !widget.contains(e.target)) { closePanel(); }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panelOpen) { closePanel(); toggleBtn.focus(); }
    });
  }

  /* ── Initialiseer bij laden ───────────────────────────────── */
  function init() {
    // Herstel opgeslagen instellingen
    applyDarkMode(isDark);
    applyFontSize(fontSize);
    if (ttsEnabled) enableTTS();

    // Bouw widget als DOM klaar is
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', buildWidget);
    } else {
      buildWidget();
    }
  }

  init();

})();
