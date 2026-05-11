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
})();
