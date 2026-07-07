/* =========================================================
   ULMJC Grenoble — JS principal
   1) Apparition au scroll (IntersectionObserver)
   2) Compteurs animés
   3) Toggle menu mobile (déjà inline mais on l'améliore)
   ========================================================= */

/* Helper Matomo — pousse un event vers _paq si dispo, no-op sinon.
   Sécurise contre ad-blocker / Matomo non chargé / connexion offline. */
window.trackEvent = function (category, action, name, value) {
  try {
    if (!window._paq) return;
    var evt = ['trackEvent', category, action];
    if (name !== undefined && name !== null) evt.push(String(name));
    if (value !== undefined && value !== null) evt.push(Number(value));
    window._paq.push(evt);
  } catch (e) { /* silencieux */ }
};

(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ----- 0. Splash d'intro -----
  // Vidéo logo plein écran à chaque ouverture du site.
  // Pas re-affichée lors de la navigation entre pages (sessionStorage).
  const splash = document.getElementById('splash');
  if (splash) {
    const SPLASH_KEY = 'ulmjc-intro-seen';

    let alreadySeen = false;
    try {
      alreadySeen = sessionStorage.getItem(SPLASH_KEY) === '1';
    } catch (e) { /* sessionStorage bloqué */ }

    if (alreadySeen) {
      splash.classList.add('hidden');
    } else {
      document.body.classList.add('splash-active');
      const video = document.getElementById('splash-video');
      const skipBtn = splash.querySelector('.splash-skip');

      function dismissSplash() {
        if (splash.classList.contains('fade-out')) return;
        splash.classList.add('fade-out');
        document.body.classList.remove('splash-active');
        try { sessionStorage.setItem(SPLASH_KEY, '1'); } catch (e) {}
        setTimeout(() => splash.classList.add('hidden'), 750);
        // Stop la vidéo pour libérer le décodeur
        if (video) { try { video.pause(); } catch (e) {} }
      }

      if (video) {
        video.addEventListener('ended', dismissSplash, { once: true });
        // Filet : si la vidéo bloque, on ferme après 12s max
        setTimeout(dismissSplash, 12000);
        // Démarre la lecture (autoplay muted, devrait passer)
        const p = video.play();
        if (p && typeof p.catch === 'function') {
          p.catch(() => {
            // Autoplay refusé → on dégage, le user verra le site direct
            dismissSplash();
          });
        }
      }

      if (skipBtn) {
        skipBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          dismissSplash();
        });
      }
      // Clic sur le fond ou la vidéo elle-même → skip
      splash.addEventListener('click', dismissSplash);
      // Échap pour skipper aussi
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dismissSplash();
      });
    }
  }

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

  // ----- 4a. Boucle vidéo personnalisée (data-loop-start / data-loop-end) -----
  // Permet de boucler entre deux timecodes précis sans ré-encoder la vidéo.
  // Usage dans le HTML : <video data-loop-start="0.5" data-loop-end="10.3" ...>
  document.querySelectorAll('video[data-loop-start], video[data-loop-end]').forEach((vid) => {
    const start = parseFloat(vid.dataset.loopStart || '0');
    const endRaw = parseFloat(vid.dataset.loopEnd || '');
    const end = isNaN(endRaw) ? Infinity : endRaw;
    if (end <= start) return;

    // Au démarrage : sauter à `start` si on a une marge avant
    function jumpToStart() {
      if (start > 0 && vid.currentTime < start) {
        try { vid.currentTime = start; } catch (e) {}
      }
    }
    vid.addEventListener('loadedmetadata', jumpToStart, { once: true });
    if (vid.readyState >= 1) jumpToStart();

    // Pendant la lecture : si on dépasse `end`, on revient à `start`
    vid.addEventListener('timeupdate', () => {
      if (vid.currentTime >= end) {
        try { vid.currentTime = start; } catch (e) {}
      }
    });
    // Backup : si le `loop` natif kick in (fin réelle de vidéo), on rebondit aussi
    vid.addEventListener('seeked', () => {
      if (vid.currentTime < start) {
        try { vid.currentTime = start; } catch (e) {}
      }
    });
  });

  // ----- 4b. Parallaxe au scroll pour le hero photo -----
  // La photo défile plus lentement que le contenu, ce qui donne une
  // sensation de profondeur. Désactivé si prefers-reduced-motion.
  const parallaxEls = document.querySelectorAll('[data-parallax]');
  if (parallaxEls.length && !prefersReducedMotion) {
    let ticking = false;
    function updateParallax() {
      parallaxEls.forEach((el) => {
        const host = el.parentElement; // .hero
        if (!host) return;
        const rect = host.getBoundingClientRect();
        // Visible ?
        if (rect.bottom > 0 && rect.top < window.innerHeight) {
          // Distance scrollée depuis le top du hero (clampée à 0 pour avant)
          const scrolled = Math.max(-rect.top, 0);
          const offset = scrolled * 0.32; // 32% : assez sensible sans devenir distrayant
          el.style.transform = 'translate3d(0,' + offset + 'px,0)';
        }
      });
      ticking = false;
    }
    function requestUpdate() {
      if (!ticking) {
        requestAnimationFrame(updateParallax);
        ticking = true;
      }
    }
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });
    updateParallax();
  }

  // ----- 4c. Lightbox photos (chalet.html) -----
  // Deux déclencheurs possibles :
  //  - vignette dans [data-lightbox-gallery] (galerie classique)
  //  - card cliquable .chalet-card[data-photos="1,5,6,..."] (sélection par catégorie)
  // Navigation : flèches clavier / boutons, Échap pour fermer, wrap circulaire.
  const galleries = document.querySelectorAll('[data-lightbox-gallery]');
  const photoCards = document.querySelectorAll('.chalet-card[data-photos]');
  if (galleries.length || photoCards.length) {
    let lightbox = null;
    let lightboxImg = null;
    let counterEl = null;
    let currentItems = []; // [{src, alt}, ...]
    let currentIndex = 0;

    function buildLightbox() {
      if (lightbox) return;
      lightbox = document.createElement('div');
      lightbox.className = 'lightbox';
      lightbox.setAttribute('role', 'dialog');
      lightbox.setAttribute('aria-label', 'Galerie photo');
      lightbox.innerHTML =
        '<img class="lightbox-img" alt="">' +
        '<button class="lightbox-close" aria-label="Fermer">×</button>' +
        '<button class="lightbox-prev" aria-label="Photo précédente">‹</button>' +
        '<button class="lightbox-next" aria-label="Photo suivante">›</button>' +
        '<div class="lightbox-counter"></div>';
      document.body.appendChild(lightbox);
      lightboxImg = lightbox.querySelector('.lightbox-img');
      counterEl = lightbox.querySelector('.lightbox-counter');

      lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);
      lightbox.querySelector('.lightbox-prev').addEventListener('click', (e) => { e.stopPropagation(); show(currentIndex - 1); });
      lightbox.querySelector('.lightbox-next').addEventListener('click', (e) => { e.stopPropagation(); show(currentIndex + 1); });
      lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });
    }

    function show(idx) {
      if (!currentItems.length) return;
      const n = currentItems.length;
      currentIndex = ((idx % n) + n) % n; // wrap
      const item = currentItems[currentIndex];
      // Préchargement de la suivante pour fluidité
      const next = currentItems[(currentIndex + 1) % n];
      const preload = new Image();
      preload.src = next.src;
      // Fade pendant le swap
      lightboxImg.classList.add('swapping');
      const preloadCurr = new Image();
      preloadCurr.onload = () => {
        lightboxImg.src = item.src;
        lightboxImg.alt = item.alt || '';
        requestAnimationFrame(() => lightboxImg.classList.remove('swapping'));
      };
      preloadCurr.src = item.src;
      counterEl.textContent = (currentIndex + 1) + ' / ' + n;
    }

    function openLightbox(items, idx) {
      buildLightbox();
      currentItems = items;
      show(idx);
      lightbox.classList.add('open');
      document.body.classList.add('lightbox-active');
    }

    function closeLightbox() {
      if (!lightbox) return;
      lightbox.classList.remove('open');
      document.body.classList.remove('lightbox-active');
    }

    // Galeries classiques
    galleries.forEach((gal) => {
      const anchors = Array.from(gal.querySelectorAll('a'));
      const galName = gal.getAttribute('data-lightbox-gallery') || 'Galerie';
      const items = anchors.map((a) => ({
        src: a.getAttribute('href'),
        alt: a.querySelector('img') ? a.querySelector('img').alt : ''
      }));
      anchors.forEach((a, idx) => {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          window.trackEvent('Photos', 'Ouverture galerie', galName);
          openLightbox(items, idx);
        });
      });
    });

    // Cards cliquables (page chalet : Extérieur, Couchage, etc.)
    // data-photos contient désormais une liste de CHEMINS d'images directement
    // (ex. "images/chalet/chalet-01.jpg,uploads/ab.jpg"), générée côté serveur
    // depuis chalet.json — plus d'index numériques à reconstruire ici.
    photoCards.forEach((card) => {
      card.addEventListener('click', () => {
        const list = (card.dataset.photos || '').split(',').map((s) => s.trim()).filter(Boolean);
        if (!list.length) return;
        const cardTitle = card.querySelector('h3') ? card.querySelector('h3').textContent.trim() : 'Catégorie';
        const items = list.map((src) => ({ src: src, alt: 'Chalet ULMJC — ' + cardTitle }));
        window.trackEvent('Photos', 'Ouverture catégorie chalet', cardTitle);
        openLightbox(items, 0);
      });
    });

    document.addEventListener('keydown', (e) => {
      if (!lightbox || !lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') closeLightbox();
      else if (e.key === 'ArrowLeft') show(currentIndex - 1);
      else if (e.key === 'ArrowRight') show(currentIndex + 1);
    });
  }

  // ----- 4d. Timeline interactive (asso.html) -----
  // Clic sur une étape → toggle du détail (aria-expanded + show/hide)
  // Scroll → la ligne verticale se "remplit" progressivement
  const timeline = document.querySelector('[data-timeline]');
  if (timeline) {
    // Expand/collapse au clic
    timeline.querySelectorAll('.timeline-content').forEach((btn) => {
      btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        const detail = btn.querySelector('.timeline-detail');
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        if (detail) {
          if (expanded) detail.setAttribute('hidden', '');
          else detail.removeAttribute('hidden');
        }
        if (!expanded) {
          // On ne track QUE l'ouverture, pas la fermeture
          const yearEl = btn.querySelector('.timeline-year');
          const titleEl = btn.querySelector('h3');
          const label = (yearEl ? yearEl.textContent.trim() + ' — ' : '') +
                        (titleEl ? titleEl.textContent.trim() : 'Étape');
          window.trackEvent('Timeline', 'Étape ouverte', label);
        }
      });
    });

    // Ligne progressive au scroll
    if (!prefersReducedMotion) {
      let ticking = false;
      function updateTimelineFill() {
        const rect = timeline.getBoundingClientRect();
        const vh = window.innerHeight;
        // Pourcentage de la timeline traversée : 0 quand le haut atteint le bas du viewport,
        // 100 quand le bas atteint le milieu du viewport
        const startY = rect.top - vh * 0.6;
        const totalDist = rect.height + vh * 0.2;
        const progress = Math.min(Math.max(-startY / totalDist, 0), 1);
        timeline.style.setProperty('--timeline-fill', (progress * 100) + '%');
        ticking = false;
      }
      function requestTimelineUpdate() {
        if (!ticking) {
          requestAnimationFrame(updateTimelineFill);
          ticking = true;
        }
      }
      window.addEventListener('scroll', requestTimelineUpdate, { passive: true });
      window.addEventListener('resize', requestTimelineUpdate, { passive: true });
      updateTimelineFill();
    }
  }

  // ----- 4e. Quizz (asso.html) -----
  // Q à choix multiple, feedback immédiat (correct/incorrect + explication),
  // score affiché à la fin avec un commentaire selon le résultat.
  const quiz = document.querySelector('[data-quiz]');
  if (quiz) {
    const intro = quiz.querySelector('[data-quiz-intro]');
    const startBtn = quiz.querySelector('.quiz-start');
    const questionsList = quiz.querySelector('.quiz-questions');
    const questions = quiz.querySelectorAll('.quiz-question');
    const result = quiz.querySelector('.quiz-result');
    const scoreNum = quiz.querySelector('.quiz-score-num');
    const commentEl = quiz.querySelector('.quiz-comment');
    const restartBtn = quiz.querySelector('.quiz-restart');
    let answered = 0;
    let score = 0;

    function startQuiz() {
      if (intro) intro.setAttribute('hidden', '');
      if (questionsList) questionsList.removeAttribute('hidden');
      window.trackEvent('Quizz', 'Démarré');
      // Scroll vers la première question
      setTimeout(() => {
        if (questions[0]) questions[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 100);
    }
    if (startBtn) startBtn.addEventListener('click', startQuiz);

    function commentFor(n, total) {
      if (n === total) return 'Bravo, sans faute ! Vous connaissez votre éducation populaire sur le bout des doigts.';
      if (n >= total - 1) return 'Excellent score — il vous manque un détail ou deux.';
      if (n >= Math.ceil(total / 2)) return 'Bon score, vous avez les bases. Reparcourez la timeline pour les pépites.';
      if (n >= 2) return 'Le quizz est l\'occasion de creuser la timeline. Ressayez après lecture !';
      return 'Pas grave — la timeline ci-dessus est là pour ça. Bonne lecture.';
    }

    function handleClick(e) {
      const btn = e.currentTarget;
      const question = btn.closest('.quiz-question');
      if (question.classList.contains('answered')) return;

      const correct = question.dataset.correct;
      const chosen = btn.dataset.choice;
      const isRight = chosen === correct;

      question.classList.add('answered');
      btn.classList.add(isRight ? 'correct' : 'incorrect');

      // Désactiver tous les boutons + montrer la bonne réponse si erreur
      question.querySelectorAll('.quiz-answers button').forEach((b) => {
        b.disabled = true;
        if (!isRight && b.dataset.choice === correct) {
          b.classList.add('correct');
        }
      });

      const explanation = question.querySelector('.quiz-explanation');
      if (explanation) explanation.removeAttribute('hidden');

      if (isRight) score++;
      answered++;

      if (answered === questions.length) {
        scoreNum.textContent = score;
        commentEl.textContent = commentFor(score, questions.length);
        result.removeAttribute('hidden');
        window.trackEvent('Quizz', 'Terminé', 'Score ' + score + '/' + questions.length, score);
        // Scroll doux vers le résultat
        setTimeout(() => result.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
      }
    }

    function bind() {
      questions.forEach((q) => {
        q.querySelectorAll('.quiz-answers button').forEach((b) => {
          b.addEventListener('click', handleClick);
        });
      });
    }

    function reset() {
      answered = 0;
      score = 0;
      result.setAttribute('hidden', '');
      questions.forEach((q) => {
        q.classList.remove('answered');
        q.querySelectorAll('.quiz-answers button').forEach((b) => {
          b.classList.remove('correct', 'incorrect');
          b.disabled = false;
        });
        const exp = q.querySelector('.quiz-explanation');
        if (exp) exp.setAttribute('hidden', '');
      });
      // Re-scroll vers la 1ère question
      questions[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    bind();
    if (restartBtn) restartBtn.addEventListener('click', reset);
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

  // ----- 6. Widget météo au chalet (chalet.html) -----
  // Affiche la météo en direct à l'Alpe du Grand Serre (1368 m) via Open-Meteo.
  // Cache localStorage 30 min, fail silencieux avec message d'erreur visible.
  const chaletWeather = document.querySelector('[data-chalet-weather]');
  if (chaletWeather) {
    const CW_KEY = 'ulmjc-chalet-weather-v2';
    const CW_TTL = 30 * 60 * 1000;
    const CW_LAT = 44.97;
    const CW_LON = 5.86;
    const CW_ELEV = 1368;

    function cwMap(code, isDay) {
      // Variantes jour / nuit pour les conditions claires et nuageuses
      if (code === 0)  return isDay ? { emoji: '☀️', label: 'Ciel clair' } : { emoji: '🌙', label: 'Nuit claire' };
      if (code === 1)  return isDay ? { emoji: '🌤️', label: 'Plutôt clair' } : { emoji: '🌙', label: 'Plutôt clair' };
      if (code === 2)  return isDay ? { emoji: '⛅', label: 'Partiellement nuageux' } : { emoji: '☁️', label: 'Partiellement nuageux' };
      if (code === 3)                 return { emoji: '☁️', label: 'Couvert' };
      if (code === 45 || code === 48) return { emoji: '🌫️', label: 'Brouillard' };
      if (code >= 51 && code <= 57)   return { emoji: '🌦️', label: 'Bruine' };
      if (code >= 61 && code <= 67)   return { emoji: '🌧️', label: 'Pluie' };
      if (code >= 71 && code <= 77)   return { emoji: '❄️', label: 'Neige' };
      if (code >= 80 && code <= 82)   return { emoji: '🌧️', label: 'Averses' };
      if (code >= 85 && code <= 86)   return { emoji: '🌨️', label: 'Averses de neige' };
      if (code >= 95)                 return { emoji: '⛈️', label: 'Orage' };
      return isDay ? { emoji: '🌤️', label: '—' } : { emoji: '🌙', label: '—' };
    }

    // Mappe le code météo vers une classe CSS pour l'animation contextuelle
    function cwClass(code) {
      if (code <= 2)                  return 'is-clear';
      if (code === 3)                 return 'is-cloud';
      if (code === 45 || code === 48) return 'is-fog';
      if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) return 'is-rain';
      if ((code >= 71 && code <= 77) || (code >= 85 && code <= 86)) return 'is-snow';
      if (code >= 95)                 return 'is-thunder';
      return 'is-clear';
    }

    // Anime un nombre de 0 vers la valeur cible (easeOutCubic)
    function animateNumber(el, target, duration) {
      if (prefersReducedMotion) { el.textContent = target; return; }
      const start = performance.now();
      const dur = duration || 1400;
      function tick(now) {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased);
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    }

    function cwApply(data) {
      if (!data || !data.current || !data.daily) return false;
      const cur = data.current, day = data.daily;
      const isDay = cur.is_day === 1;
      const w = cwMap(cur.weather_code, isDay);
      const $ = (sel) => chaletWeather.querySelector(sel);
      const card = chaletWeather.querySelector('.weather-card');

      $('[data-emoji]').textContent = w.emoji;
      $('[data-condition]').textContent = w.label;
      // Classe contextuelle pour l'animation emoji + variante nuit
      card.className = 'weather-card ' + cwClass(cur.weather_code) + (isDay ? '' : ' is-night');

      // Affichage du contenu
      const loading = chaletWeather.querySelector('.weather-loading');
      if (loading) loading.setAttribute('hidden', '');
      chaletWeather.querySelector('.weather-content').removeAttribute('hidden');

      // Slide-up entrance + compteurs animés
      requestAnimationFrame(() => card.classList.add('shown'));
      setTimeout(() => {
        animateNumber($('[data-temp]'), Math.round(cur.temperature_2m), 1600);
        if (cur.apparent_temperature != null) {
          animateNumber($('[data-feels]'), Math.round(cur.apparent_temperature), 1400);
        }
        animateNumber($('[data-tmin]'), Math.round(day.temperature_2m_min[0]), 1200);
        animateNumber($('[data-tmax]'), Math.round(day.temperature_2m_max[0]), 1200);
        animateNumber($('[data-wind]'), Math.round(cur.wind_speed_10m), 1400);
      }, 250);

      const snow = (day.snowfall_sum && day.snowfall_sum[0]) || 0;
      if (snow > 0) {
        $('[data-snow]').textContent = snow.toFixed(snow < 1 ? 1 : 0);
        $('[data-snow-block]').removeAttribute('hidden');
      }
      return true;
    }

    // Cache
    try {
      const cached = JSON.parse(localStorage.getItem(CW_KEY) || 'null');
      if (cached && Date.now() - cached.ts < CW_TTL) {
        if (cwApply(cached.data)) return;
      }
    } catch (e) { /* cache corrompu */ }

    // Fetch frais
    const url = 'https://api.open-meteo.com/v1/forecast' +
                '?latitude=' + CW_LAT +
                '&longitude=' + CW_LON +
                '&elevation=' + CW_ELEV +
                '&current=temperature_2m,apparent_temperature,weather_code,wind_speed_10m,snowfall,is_day' +
                '&daily=temperature_2m_max,temperature_2m_min,snowfall_sum' +
                '&timezone=Europe%2FParis';
    const ctrl = new AbortController();
    const timeout = setTimeout(() => ctrl.abort(), 5000);
    fetch(url, { signal: ctrl.signal })
      .then((r) => r.ok ? r.json() : Promise.reject(r.status))
      .then((data) => {
        clearTimeout(timeout);
        if (cwApply(data)) {
          try { localStorage.setItem(CW_KEY, JSON.stringify({ data, ts: Date.now() })); } catch (e) {}
        }
      })
      .catch(() => {
        const loading = chaletWeather.querySelector('.weather-loading');
        if (loading) loading.textContent = 'Météo indisponible pour le moment.';
      });
  }
})();

/* ============================================================
   POPUP INVITATION BÉNÉVOLAT
   - injecte le modal sur toutes les pages (sauf contact.html)
   - apparait après 8 s cumulées de navigation sur le site (1× par session)
   - persiste entre les changements de page tant que l'utilisateur
     n'a pas été dérangé
   - fermable par X, clic backdrop, Escape ou bouton "Pas maintenant"
   ============================================================ */
(function () {
  // Pas de popup sur la page Contact (le CTA y renvoie déjà)
  if (/contact\.html$/.test(location.pathname)) return;

  const STORAGE_KEY = 'ulmjc-volunteer-dismissed';
  const SESSION_START_KEY = 'ulmjc-volunteer-start';
  const DELAY_MS = 8000;
  const MIN_DELAY_AFTER_NAV = 1500; // un petit délai pour ne pas surprendre au chargement d'une nouvelle page

  // Déjà fermé pendant cette session : on n'affiche plus rien
  let dismissed = false;
  try { dismissed = sessionStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}
  if (dismissed) return;

  // Horodatage du premier chargement dans la session
  let sessionStart;
  try {
    sessionStart = parseInt(sessionStorage.getItem(SESSION_START_KEY), 10);
    if (!sessionStart) {
      sessionStart = Date.now();
      sessionStorage.setItem(SESSION_START_KEY, String(sessionStart));
    }
  } catch (e) {
    sessionStart = Date.now();
  }

  const elapsed = Date.now() - sessionStart;
  const remaining = elapsed >= DELAY_MS ? MIN_DELAY_AFTER_NAV : (DELAY_MS - elapsed);

  function injectModal() {
    if (document.getElementById('volunteer-modal')) return document.getElementById('volunteer-modal');
    const div = document.createElement('div');
    div.id = 'volunteer-modal';
    div.className = 'volunteer-modal';
    div.setAttribute('role', 'dialog');
    div.setAttribute('aria-modal', 'true');
    div.setAttribute('aria-labelledby', 'volunteer-title');
    div.hidden = true;
    div.innerHTML =
      '<div class="volunteer-backdrop" data-volunteer-close></div>' +
      '<div class="volunteer-card" role="document">' +
        '<button type="button" class="volunteer-close" aria-label="Fermer" data-volunteer-close>×</button>' +
        '<div class="volunteer-icon" aria-hidden="true">🤝</div>' +
        '<span class="section-eyebrow">Rejoindre l’aventure</span>' +
        '<h2 id="volunteer-title">Devenez bénévole.</h2>' +
        '<p>L’union locale, c’est <strong>100&nbsp;% de bénévoles</strong>&nbsp;: des habitant·es des quartiers qui font vivre les MJC et le chalet. On accueille toutes les énergies — quelques heures par mois, un coup de main ponctuel, ou un engagement plus régulier.</p>' +
        '<p class="muted">Aucun pré-requis, juste l’envie de partager.</p>' +
        '<div class="volunteer-actions">' +
          '<a href="contact.php" class="btn btn-accent">Je veux en savoir plus</a>' +
          '<button type="button" class="btn btn-ghost" data-volunteer-close>Pas maintenant</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(div);
    return div;
  }

  let modal = null;
  let isOpen = false;

  function open() {
    if (isOpen) return;
    // re-check au cas où une autre page de la session aurait fermé entre-temps
    try { if (sessionStorage.getItem(STORAGE_KEY) === '1') return; } catch (e) {}
    modal = modal || injectModal();
    if (!modal) return;
    isOpen = true;
    modal.hidden = false;
    void modal.offsetWidth;
    modal.classList.add('is-open');
    document.addEventListener('keydown', onKey);
    window.trackEvent('Popup bénévolat', 'Affiché');
  }

  function close(reason) {
    if (!isOpen || !modal) return;
    isOpen = false;
    modal.classList.remove('is-open');
    document.removeEventListener('keydown', onKey);
    try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
    window.trackEvent('Popup bénévolat', 'Fermé', reason || 'Inconnu');
    setTimeout(() => {
      if (!isOpen && modal) modal.hidden = true;
    }, 500);
  }

  function onKey(e) {
    if (e.key === 'Escape') close('Échap');
  }

  // Délégation globale : tout clic DANS le modal
  document.addEventListener('click', (e) => {
    if (!modal || !modal.contains(e.target)) return;
    const closeTrigger = e.target.closest('[data-volunteer-close]');
    const cta = e.target.closest('a.btn-accent');
    if (cta && !modal.hidden) {
      // Clic sur "Je veux en savoir plus" — on track puis on laisse la nav se faire
      window.trackEvent('Popup bénévolat', 'Clic CTA', 'Je veux en savoir plus');
      try { sessionStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
    } else if (closeTrigger) {
      e.preventDefault();
      // Distinguer X / "Pas maintenant" / backdrop
      let reason = 'Bouton fermer';
      if (closeTrigger.classList.contains('volunteer-backdrop')) reason = 'Clic backdrop';
      else if (closeTrigger.classList.contains('volunteer-close')) reason = 'Croix';
      else if (closeTrigger.tagName === 'BUTTON') reason = 'Pas maintenant';
      close(reason);
    }
  });

  setTimeout(open, remaining);
})();

/* Compteur de visites du footer : retiré, Matomo le gère désormais (dashboard) */
