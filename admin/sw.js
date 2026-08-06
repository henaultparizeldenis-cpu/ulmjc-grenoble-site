/* Service worker du back-office ULMJC.

   PARTI PRIS : on ne met JAMAIS en cache les pages d'administration.
   Elles dépendent de la session et changent à chaque publication : servir une
   version en cache ferait travailler un bénévole sur des données périmées, ou
   afficherait le contenu d'une session précédente. On ne met donc en cache que
   les ressources statiques (feuille de style, icônes) et une page « hors
   connexion » de secours. Tout le reste passe par le réseau.

   Ce fichier vit dans /admin/ : sa portée est donc limitée au back-office,
   il n'interfère jamais avec le site public. */

const VERSION = 'ulmjc-admin-v1';
const SHELL = [
  './offline.html',
  './admin.css',
  './icons/icon-192.png',
  './icons/icon-512.png',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(VERSION)
      .then((c) => c.addAll(SHELL))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())   // une ressource manquante ne doit pas bloquer l'installation
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((noms) => Promise.all(noms.filter((n) => n !== VERSION).map((n) => caches.delete(n))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;

  // On ne touche qu'aux lectures : jamais aux envois de formulaire ni aux uploads.
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // Navigation (ouverture d'une page) : réseau d'abord, page de secours si hors ligne.
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).catch(() => caches.match('./offline.html'))
    );
    return;
  }

  // Ressources statiques : cache d'abord (rapide), puis réseau en repli.
  if (/\.(css|png|jpg|jpeg|svg|webp|woff2?)$/i.test(url.pathname)) {
    e.respondWith(
      caches.match(req).then((hit) => hit || fetch(req).then((res) => {
        if (res && res.ok) {
          const copie = res.clone();
          caches.open(VERSION).then((c) => c.put(req, copie));
        }
        return res;
      }).catch(() => hit))
    );
    return;
  }

  // Tout le reste (pages PHP, API) : réseau uniquement, jamais de cache.
});
