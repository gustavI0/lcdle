/**
 * @file
 * Dark mode toggle behavior.
 *
 * Source of truth: localStorage['lcdle-theme'] ∈ {'dark', 'light'} or absent.
 * Absent = follow prefers-color-scheme. The inline snippet in html.html.twig
 * applies the correct class pre-paint; this behavior keeps the button's
 * aria-pressed and label in sync with the current state and handles clicks.
 */
(function (Drupal, once) {
  'use strict';

  var STORAGE_KEY = 'lcdle-theme';

  function currentMode() {
    var stored = null;
    try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
    if (stored === 'dark' || stored === 'light') { return stored; }
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      return 'dark';
    }
    return 'light';
  }

  function apply(mode, button) {
    var root = document.documentElement;
    root.classList.remove('dark', 'light');
    root.classList.add(mode);
    try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}
    var labelToDark = Drupal.t('Basculer en mode sombre');
    var labelToLight = Drupal.t('Basculer en mode clair');
    button.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
    button.setAttribute('aria-label', mode === 'dark' ? labelToLight : labelToDark);
  }

  Drupal.behaviors.lcdleDarkMode = {
    attach: function (context) {
      once('lcdle-dark-mode', '.dark-mode-toggle', context).forEach(function (button) {
        // Sync button state with actual current mode.
        apply(currentMode(), button);
        button.addEventListener('click', function () {
          var next = currentMode() === 'dark' ? 'light' : 'dark';
          apply(next, button);
        });
      });
    }
  };
})(Drupal, once);
