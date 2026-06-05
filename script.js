/**
 * script.js — Golden Stone Hotel
 * Landing page: auto image detection + particles + language selection
 */

'use strict';


/* ================================================================
   FLOATING GOLD PARTICLES
   ================================================================ */
(function spawnParticles() {
  const container = document.getElementById('particles');
  if (!container) return;

  const COUNT = 28;
  for (let i = 0; i < COUNT; i++) {
    const p = document.createElement('div');
    p.className = 'particle';

    const size = 1.5 + Math.random() * 2.5;
    p.style.setProperty('--dur',   `${9 + Math.random() * 10}s`);
    p.style.setProperty('--delay', `${Math.random() * 14}s`);
    p.style.left   = `${Math.random() * 100}%`;
    p.style.bottom = `${-8 + Math.random() * 20}%`;
    p.style.width  = `${size}px`;
    p.style.height = `${size}px`;

    container.appendChild(p);
  }
})();

/* ================================================================
   LANGUAGE SELECTION
   ================================================================ */
(function initLanguageButtons() {
  const buttons        = document.querySelectorAll('.lang-btn');
  const pageTransition = document.getElementById('pageTransition');

  buttons.forEach((btn) => {
    btn.addEventListener('click', handleLanguageSelect);

    // Touch haptic-feel micro-animation
    btn.addEventListener('touchstart', () => {
      btn.style.transform = 'scale(0.97)';
    }, { passive: true });

    btn.addEventListener('touchend', () => {
      btn.style.transform = '';
    }, { passive: true });
  });

  function handleLanguageSelect(e) {
    const btn      = e.currentTarget;
    const lang     = btn.dataset.lang;
    const langName = btn.dataset.langName;

    if (!lang) return;

    // Disable all buttons (prevent double-tap)
    buttons.forEach(b => { b.disabled = true; });
    btn.classList.add('loading');

    // Persist language preference
    try {
      sessionStorage.setItem('hotel_lang',      lang);
      sessionStorage.setItem('hotel_lang_name', langName);
    } catch (_) {
      // sessionStorage may be blocked in private mode
    }

    // Brief visual acknowledgement, then transition
    setTimeout(() => startPageTransition(lang), 260);
  }

  function startPageTransition(lang) {
    if (!pageTransition) {
      navigateTo(lang);
      return;
    }

    pageTransition.classList.add('active');

    pageTransition.addEventListener('transitionend', () => {
      navigateTo(lang);
    }, { once: true });

    // Safety fallback
    setTimeout(() => navigateTo(lang), 900);
  }

  function navigateTo(lang) {
    window.location.href = `table-select.html?lang=${encodeURIComponent(lang)}`;
  }
})();
