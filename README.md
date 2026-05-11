# ulmjc-grenoble-site

Site internet public de l'association **ULMJC Grenoble**.

## Stack

- HTML + CSS + un petit peu de JS, sans framework.
- Fonts via Google Fonts (Fraunces, Inter).
- Déployé sur Hostinger via la branche `main`.

## Structure

```
.
├── index.html          # Accueil
├── asso.html           # L'asso
├── activites.html      # Activités
├── chalet.html         # Le chalet
├── actus.html          # Actualités
├── galerie.html        # Galerie photos
├── contact.html        # Contact + formulaire
├── css/style.css       # Tous les styles
├── images/             # Photos (à remplir)
└── icons/              # Favicon et icônes
```

## À faire pour passer en prod réelle

- [ ] Remplacer les textes placeholder par les vrais (relire chaque page).
- [ ] Ajouter les vraies photos dans `images/` et mettre à jour `galerie.html`.
- [ ] Créer un compte Formspree.io et coller l'ID dans `contact.html`.
- [ ] Compléter l'adresse postale, numéro RNA, téléphone, etc.
- [ ] Créer un favicon dans `icons/` et l'ajouter dans chaque `<head>`.
- [ ] Ajouter les liens Facebook / Instagram quand ils existeront.
- [ ] Page Mentions légales + Statuts à créer.

## Déploiement

Push sur la branche `main` → auto-déployé par Hostinger sur `site.ulmjcgrenoble.org`
(et plus tard sur `ulmjcgrenoble.org` quand on bascule l'app vers `app.ulmjcgrenoble.org`).
