/* =========================================================
   ULMJC Grenoble — JS principal
   1) Apparition au scroll (IntersectionObserver)
   2) Compteurs animés
   3) Toggle menu mobile (déjà inline mais on l'améliore)
   ========================================================= */

(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ----- 1. Reveal au scroll -----
  const revealEls = document.querySelectorAll('.reveal, .reveal-stagger');

  if (prefersReducedMotion) {
    revealEls.forEach((el) => el.classList.add('in'));
  } else if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
    );
    revealEls.forEach((el) => io.observe(el));
  } else {
    // Fallback : on affiche tout sans animation
    revealEls.forEach((el) => el.classList.add('in'));
  }

  // ----- 2. Compteurs animés -----
  function formatNumber(n, format) {
    if (format === 'plain') return String(n);
    return n.toLocaleString('fr-FR');
  }

  function animateCounter(el) {
    const target = parseInt(el.dataset.count, 10);
    if (isNaN(target)) return;
    const format = el.dataset.format || (target >= 1000 && target < 9999 && target > 1900 ? 'plain' : 'auto');

    if (prefersReducedMotion) {
      el.textContent = formatNumber(target, format);
      return;
    }

    const duration = 1400;
    const start = performance.now();
    const startVal = 0;
    function tick(now) {
      const progress = Math.min((now - start) / duration, 1);
      // easeOutCubic
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.round(startVal + (target - startVal) * eased);
      el.textContent = formatNumber(current, format);
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  const counters = document.querySelectorAll('[data-count]');
  if ('IntersectionObserver' in window) {
    const counterIO = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.5 }
    );
    counters.forEach((el) => counterIO.observe(el));
  } else {
    counters.forEach(animateCounter);
  }

  // ----- 3. Menu mobile : fermeture au clic d'un lien -----
  const navLinks = document.getElementById('nav-links');
  if (navLinks) {
    navLinks.querySelectorAll('a').forEach((a) => {
      a.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }

  // ----- 4. Vidéo hero : fade-in une fois prête -----
  const heroVideo = document.querySelector('.hero-video');
  if (heroVideo) {
    const showVideo = () => heroVideo.classList.add('loaded');
    if (heroVideo.readyState >= 3) {
      showVideo();
    } else {
      heroVideo.addEventListener('canplay', showVideo, { once: true });
      // Filet de sécurité : on affiche après 2s même si l'event ne tombe pas
      setTimeout(showVideo, 2000);
    }
  }

  // ----- 5. Météo Grenoble (Open-Meteo) -----
  // Applique une classe sur le hero photo pour adapter filtres + particules
  // à la météo réelle. Cache localStorage 30 min, fail silencieux.
  const heroTarget = document.querySelector('[data-weather-target]');
  if (heroTarget) {
    const CACHE_KEY = 'ulmjc-weather-v1';
    const CACHE_TTL = 30 * 60 * 1000;
    const GRENOBLE = { lat: 45.1885, lon: 5.7245 };

    // Codes WMO → catégorie d'effet visuel + libellé court
    function mapWeather(code) {
      if (code === 0)               return { cls: 'weather-clear',  emoji: '☀️', label: 'Ciel clair' };
      if (code <= 2)                return { cls: 'weather-clear',  emoji: '🌤️', label: 'Quelques nuages' };
      if (code === 3)               return { cls: 'weather-cloud',  emoji: '☁️', label: 'Couvert' };
      if (code === 45 || code === 48) return { cls: 'weather-fog',  emoji: '🌫️', label: 'Brouillard' };
      if (code >= 51 && code <= 57) return { cls: 'weather-rain',   emoji: '🌦️', label: 'Bruine' };
      if (code >= 61 && code <= 67) return { cls: 'weather-rain',   emoji: '🌧️', label: 'Pluie' };
      if (code >= 71 && code <= 77) return { cls: 'weather-snow',   emoji: '❄️', label: 'Neige' };
      if (code >= 80 && code <= 82) return { cls: 'weather-rain',   emoji: '🌧️', label: 'Averses' };
      if (code >= 85 && code <= 86) return { cls: 'weather-snow',   emoji: '🌨️', label: 'Averses de neige' };
      if (code >= 95)               return { cls: 'weather-thunder',emoji: '⛈️', label: 'Orage' };
      return { cls: 'weather-clear', emoji: '🌤️', label: '' };
    }

    function applyWeather(code, isDay) {
      const w = mapWeather(code);
      heroTarget.classList.add(w.cls, isDay ? 'is-day' : 'is-night');

      if (w.label) {
        const badge = document.createElement('div');
        badge.className = 'weather-badge';
        badge.innerHTML =
          '<span class="weather-emoji">' + w.emoji + '</span>' +
          '<span>' + w.label + (isDay ? '' : ' · nuit') + ' à Grenoble</span>';
        heroTarget.appendChild(badge);
        // Petite latence pour déclencher la transition
        requestAnimationFrame(() => requestAnimationFrame(() => badge.classList.add('shown')));
      }
    }

    // Cache
    try {
      const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
      if (cached && Date.now() - cached.ts < CACHE_TTL) {
        applyWeather(cached.code, cached.isDay);
        return;
      }
    } catch (e) { /* cache corrompu, on ignore */ }

    // Fetch frais
    const url = 'https://api.open-meteo.com/v1/forecast?latitude=' + GRENOBLE.lat +
                '&longitude=' + GRENOBLE.lon +
                '&current=weather_code,is_day&timezone=Europe%2FParis';
    const ctrl = new AbortController();
    const timeout = setTimeout(() => ctrl.abort(), 4000);

    fetch(url, { signal: ctrl.signal })
      .then((r) => r.ok ? r.json() : Promise.reject(r.status))
      .then((data) => {
        clearTimeout(timeout);
        const code = data && data.current && data.current.weather_code;
        const isDay = data && data.current && data.current.is_day === 1;
        if (typeof code === 'number') {
          try {
            localStorage.setItem(CACHE_KEY, JSON.stringify({ code, isDay, ts: Date.now() }));
          } catch (e) { /* localStorage plein ou bloqué */ }
          applyWeather(code, isDay);
        }
      })
      .catch(() => { /* fail silencieux : le hero garde son rendu par défaut */ });
  }
})();
