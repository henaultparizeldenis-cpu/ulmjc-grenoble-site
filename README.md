# ulmjc-grenoble-site

Site internet public de l'**Union Locale des MJC de Grenoble** (asso d'éducation populaire,
fondée le 27 septembre 1961). Présente le chalet de l'Alpe du Grand Serre, acquis en 1963.

## Stack

- HTML + CSS + un peu de JS, sans framework.
- Fonts via Google Fonts (Fraunces, Inter).
- Météo dynamique du hero via [Open-Meteo](https://open-meteo.com).
- Déployé sur Hostinger.

## Structure

```
.
├── index.html              # Accueil (hero photo Bastille + météo réactive)
├── asso.html               # L'asso (histoire, valeurs, fonctionnement)
├── activites.html          # Activités hiver/été autour du chalet
├── chalet.html             # Détail du chalet : capacité, tarifs, résa
├── actus.html              # Actualités / événements
├── galerie.html            # Galerie photos
├── contact.html            # Contact + formulaire (Formspree)
├── mentions-legales.html   # Mentions légales (éditeur, RGPD, crédits)
├── css/style.css           # Tous les styles
├── js/main.js              # Animations, météo, parallaxe
├── images/                 # Photos (bastille.jpg en hero)
└── videos/                 # hero.mp4 (legacy, plus utilisé)
```

## Cache busting

Les liens vers `css/style.css` et `js/main.js` portent un paramètre `?v=YYYYMMDD-N`
pour forcer le navigateur à re-télécharger après une modification.

**À chaque modification de `style.css` ou `main.js`** : bump le numéro dans les
balises `<link>` et `<script>` des 8 fichiers HTML.

Recherche / remplace tout (avec PowerShell par exemple) :

```powershell
Get-ChildItem *.html | ForEach-Object {
  (Get-Content $_.FullName -Raw) -replace 'v=20260519-2', 'v=20260520-1' | Set-Content $_.FullName
}
```

## Déploiement

Upload manuel du contenu du dossier dans `public_html/site/` du site
**`site.ulmjcgrenoble.org`** via le File Manager de Hostinger
(zip + extract + déplacement si Hostinger ajoute un wrapper).

L'auto-deploy Git n'est **pas** configuré côté Hostinger (à activer plus tard si besoin
— attention à viser le bon site dans hPanel, pas le domaine principal qui héberge l'app
de gestion adhérent).

## À faire pour la version définitive

- [ ] Vraies photos dans `images/` + maj `galerie.html`
- [ ] Compte Formspree.io + coller l'ID dans `contact.html`
- [ ] Lien Facebook + Instagram quand ils existeront
- [ ] Page Statuts à créer
- [ ] Favicon (icône `<head>`)
- [ ] Bascule de `ulmjcgrenoble.org` (app) vers `app.ulmjcgrenoble.org`, puis du site
  vitrine de `site.ulmjcgrenoble.org` vers `ulmjcgrenoble.org`
