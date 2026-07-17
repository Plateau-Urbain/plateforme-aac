# Design System — Plateau Urbain

Référence visuelle et UI de la plateforme d’appels à candidatures ([plateforme.plateau-urbain.com](https://plateforme.plateau-urbain.com)).

Base technique : thème **Cozy** (Wisely Themes) + surcouche Plateau Urbain (`assets/css/style.css`, `assets/less/main.less` → `assets/css/main.css`), Bootstrap 3, Font Awesome 6.

---

## Identité

| Élément | Valeur |
|--------|--------|
| Marque | **Plateau Urbain** |
| Positionnement | Coopérative d’urbanisme temporaire / transitoire — espaces de travail abordables dans des tiers-lieux |
| Ton | Direct, accessible, coopératif ; tutoiement / inclusif (« informé·es ») |
| Logo header | `images/logo.png` (440×161) |
| Logo footer | `images/logo-footer.png` |
| Favicon | `bundles/app/images/fav_touch_icons/favicon.ico` |
| OG image | `bundles/app/images/plateau-urbain.jpg` |

Le logo header doit rester le signal de marque principal dans la navigation. Les pages marketing (accueil AAC) s’ouvrent sur une accroche claire + CTA, sans surcharge de widgets.

---

## Couleurs

### Palette principale

| Token | Hex | Usage |
|-------|-----|--------|
| **Rouge marque** | `#E9473C` | CTA pleins (`.btn-fullcolor`), sélection texte, accents forts, cookie banner button |
| **Rouge accent** | `#DF4A43` | Liens hover legacy, highlights `.color`, états actifs |
| **Rouge foncé / erreur** | `#BC1B38` | Erreurs de formulaire, liens footer, accents secondaires |
| **Noir** | `#000000` | Texte, titres, bordures boutons outline |
| **Bleu nuit** | `#1C244B` | Texte cookie consent |
| **Beige fond** | `#F5F4F2` | Sections `.beige`, header strip, zones `.content.pu`, cookie popup |
| **Blanc** | `#FFFFFF` | Fond page, sections `.content.white` |

### Neutres & UI

| Token | Hex | Usage |
|-------|-----|--------|
| Texte secondaire | `#4D4F52` | `.darker-text` |
| Gris UI | `#74777C` | Top bar, labels secondaires |
| Gris bordure | `#ADB2B6` / `#E4E4E4` | Inputs / boutons outline gris |
| Fond clair | `#F1F3F6` / `#F8F9FB` | Barres, fonds secondaires |
| Bordure formulaire | `#14151B` | `.form-control`, addons |
| Fond addon | `#FAFAFA` | `.input-group-addon` |

### Couleurs métier (statuts candidatures / badges)

| Token | Hex | Classe / usage |
|-------|-----|----------------|
| Or / en attente | `#C89640` / `#C89641` | `.btn-yellow`, `.btn-awaiting` |
| Or clair | `#F2BE6A` / `#E6C68D` | Séparateur home (`.separateur`), accents chauds |
| Vert succès | `#8DCA85` / `#8CCB84` | Validation formulaire, `.btn-accepted` |
| Vert Bootstrap | `#5BB75B` | `.btn-success` |
| Beige bouton | `#D4CAC0` | `.btn-grey` |
| Beige arrondi | `#EDE8E2` | `.btn-rounded` |
| Taupe | `#BFB7AC` / `#C0B7AD` | Texte / bordures secondaires |
| Social Facebook | `#3B5999` | `.btn-facebook` |
| Social LinkedIn | `#0177B5` | `.btn-linkedin` |

### Règles d’usage

- **Accent principal** = rouge `#E9473C` (jamais violet / indigo).
- Fonds de section alternent **beige `#F5F4F2`** et **blanc** ; le séparateur home utilise l’**or `#F2BE6A`**.
- Erreurs = `#BC1B38` ; succès = `#8DCA85`.
- Texte courant toujours **noir** sur fond clair (pas de grey-on-grey pour le corps).

---

## Typographie

### Familles

| Famille | Rôle | Fichiers |
|---------|------|----------|
| **Ginka** | Identité : titres, labels, corps | `assets/fonts/ginka/` via `Ginka.css` |
| **Roboto** | Complément (certains blocs footer / UI legacy) | `assets/fonts/Roboto/` via `Roboto.css` |
| **Font Awesome 6** | Icônes | CDN + `font-awesome_*.min.css` |

#### Variantes Ginka (déclarées comme familles séparées)

`ginkalight`, `ginkaregular`, `ginkabook`, `ginkamedium`, `ginkabold` (+ italiques associées).

| Usage | Famille |
|-------|---------|
| Titres `h1`–`h6` | `ginkamedium` |
| Corps (`body`) | `ginkaregular` |
| Champs formulaire | `ginkalight` |
| Accents fort | `ginkabold` |

### Échelle

| Élément | Desktop | ≤ 992px | Notes |
|---------|---------|---------|--------|
| `h1` / `h2` | 56px / lh 56–63 | 40px | Accroche home centrée |
| `h3` | 40px / lh 48 | réduit | |
| `h4` | 30px / lh 36 | | |
| `h5` | 24px | | |
| `h6` | 21px | | |
| `.p-30` | 30px / lh 36 | | Accroche secondaire |
| `.p-24` | 24px | | |
| `p`, `label`, `li` | 19px / lh 24 | | |
| `body` base | 16px | | |
| `.btn-line` | 21px / weight 600 | | CTA outline |

Utilitaires : `.p-56`, `.p-24`, `.p-21`, `.p-21-regular`, `.h3-21`, `.blanc`, `.underline`, `.nowrap`, `.center`.

---

## Layout & structure

### Shell

```
#wrapper
  #header > #nav-section (logo + navbar)
  {% block prebody %}
  {% block body %}
  #footer
```

- Grille : **Bootstrap 3** (`.container`, `.row`, `.col-*`).
- Contenu page : sections `.content` avec variantes `.beige`, `.white`, `.pu`, `.separateur`.
- Breakpoints courants : `480px`, `768px`, `992px`, `1200px`.

### Navigation

- Logo à gauche → site institutionnel `plateau-urbain.com`.
- Menu KnpMenu (`mainMenu`) + bouton mobile `#nav-mobile-btn`.
- Fond nav : beige `#F5F4F2` ; décor bas : `menu_bottom.png`.
- Classes d’icônes menu : `.user-icon`, `.spaces-icon`, `.bulb-icon`, `.add-icon`, `.off-icon`.

### Footer

- Fond texture `back.png`, bordure décorative `footer_top.png`.
- Blocs : logo + pitch coopérative, contact / réseaux, liens institutionnels.
- Liens footer en rouge `#BC1B38`.

---

## Composants

### Boutons

| Classe | Style | Quand l’utiliser |
|--------|-------|------------------|
| `.btn-line` / `.btn-line-2` | Outline noir 2px, flèche `→` au hover | CTA marketing (inscription, actions principales front) |
| `.btn-line-19` / `.btn-line-16` | Variantes taille | CTA secondaires |
| `.btn-line-login` / `.btn-line-form` | Outline adaptés formulaires / login | Auth & submit |
| `.btn-fullcolor` | Fond `#E9473C`, texte blanc | CTA plein rouge |
| `.btn-default` | Outline blanc translucide | Sur fond sombre / hero |
| `.btn-default-color` | Outline gris → rouge au hover | Secondaire sur fond clair |
| `.btn-grey` | `#D4CAC0` | Action neutre |
| `.btn-yellow` | `#C89641` | Attention / attente |
| `.btn-status` + `.btn-draft` / `.btn-awaiting` / `.btn-accepted` / `.btn-refuse` | Pleine largeur, statut | Workflow candidatures |
| `.btn-ancre` | Lien ancre + icône | FAQ / proprio sur home |
| `.btn-social` | Facebook / Google / LinkedIn | Connexion sociale |
| `.btn-rounded` | Pastille beige | Compteurs / badges ronds |

Padding type CTA line : `14px 40px`, hauteur ~54px.

### Formulaires

- Conteneur : `.form-contener` (padding ~35–40px).
- Champs : `.form-control` — bordure `1px solid #14151B`, radius `4px`, police `ginkalight` 16px.
- Validation visuelle (`form.has-crosses.submitted`) : ✓ vert `#8DCA85` / ✕ rouge `#BC1B38`.
- Upload : `.custom-file-input`, `.add-file`, `.required-files`.
- Layouts Twig : `templates/Form/bootstrap_3_layout.html.twig`, `custom_front_fields.html.twig`.

### Cartes & listes d’espaces

- `.space-card` dans `#property-listing` : image + infos + prix.
- Sidebar détail : `.pu-price-zone`, `.pu-apply-zone`, `.pu-label`.
- Blocs contenu : `.pu-col-container.content-block`, tables `.pu-parcels-table`.

### Alertes & feedback

- `.alert-warning` (main.css).
- Profil incomplet : icône warning + texte avec lien `.mailto` en rouge marque.
- Cookie consent : fond `#F5F4F2`, texte `#1C244B`, bouton `#E9473C`.

### Sections home AAC

1. `.content.beige` + `.pu-home-content.accroche` — illustration `.image-home`, `h1`, `.p-30`, CTA `.btn-line`.
2. `.content.separateur` (bande or) — `.btn-ancre` proprio / FAQ.
3. `.content.white` — liste des derniers AAC (`.candidature`).

---

## Iconographie & imagery

- **UI** : Font Awesome 6 (`fa-*`).
- **Thème immobilier** : `cozy-real-estate-font.css`.
- **Illustrations maison** : PNG/SVG dans `assets/images/` (`Groupe_*.svg`, `plateforme_home_1.png`, icônes menu rouge/noir).
- **Partners** : logos dans `public/images/` (`logo-est-ensemble.png`, `macif_logo.png`, etc.).
- Préférer des photos / illustrations de lieux réels pour les espaces ; éviter les gradients décoratifs abstraits comme seul ancrage visuel.

---

## Motion

Mouvements déjà en place à préserver / étendre avec parcimonie :

1. Hover `.btn-line` : translation du label + apparition de `→`.
2. Transitions nav / `#wrapper` (menu mobile).
3. Zoom léger images listing (`background-size` / hover cards).

Éviter glow, ombres multi-couches et micro-interactions décoratives hors de ces patterns.

---

## Stack front & workflow assets

| Chemin | Rôle |
|--------|------|
| `assets/less/main.less` (+ partials `_buttons`, `_forms`, `_navbar`, `_footer`, `_space`…) | Source Less métier |
| `assets/css/style.css` | Thème Cozy + custom PU (typo, boutons line, home…) |
| `assets/css/main.css` | Sortie compilée Less |
| `assets/css/Ginka.css` / `Roboto.css` | Fonts |
| `public/bundles/app/` | Assets exposés (généré) |

```bash
make          # lessc + rebuild bundles
make css      # compile main.less uniquement
make bundles  # reconstruit public/bundles/app/
```

Templates de référence : `templates/base.html.twig`, `templates/Search/index.html.twig`, `templates/Space/show.html.twig`, `templates/SpaceManagement/Partials/edit_spaces.html.twig`.

---

## Checklist pour une nouvelle UI

1. Titres en **Ginka Medium**, corps en **Ginka Regular/Light**.
2. CTA principal marketing → `.btn-line` ; action destructive / primaire plein → rouge `#E9473C`.
3. Fonds de section : beige `#F5F4F2` ou blanc ; pas de fond violet.
4. Formulaires : bordure `#14151B`, feedback vert/rouge métier.
5. Respecter la grille Bootstrap 3 et les breakpoints existants.
6. Après CSS Less : `make css` (ou `make`) puis vérifier en front.
7. Sur surfaces branded, le logo / nom Plateau Urbain reste lisible au premier viewport.

---

## Hors scope

- **Sonata Admin** : UI back-office distincte (bundle Sonata), non couverte par cette charte front.
- Pas de design tokens CSS variables (`--*`) aujourd’hui : les couleurs sont en hex / variables Less dans `main.less`.
)
