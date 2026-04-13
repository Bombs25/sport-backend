---
name: osport-spec-ecran-post-compose
description: "Spec maquettes O’Sport — publication de post, dossier `ecran post publication`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans post publication — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran post publication` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
- Captures attendues : `screen.png` par sous-dossier.

## Cohérence d’abord

- Croiser avec `osport-design-complet-google-stitch.md` et `osport-guide-agents-synthese-design.md` avant d’inférer seul règles métier ou API.
- Signaler les **doublons** entre dossiers maquettes dans le texte ou une issue produit.

## Référence rapide

1. **Vue d’ensemble** — périmètre du parcours.
2. **Sections numérotées** — une maquette ou groupe d’éléments par section.

## Comment appliquer

1. Lire **Vue d’ensemble**.
2. Parcourir les sections **numérotées** pour spec UI, recette test ou brief intégration.

---

## Vue d’ensemble

Le parcours couvre :

1. **Sélection des médias** dans la photothèque (thème sombre, multi-sélection, ordre, aperçu, recadrage).
2. **Capture photo** (interface caméra type appareil photo).
3. **Composer la publication** (légende, identifier des personnes, lieu, sport, **cross-post** réseaux sociaux).
4. **Confirmation de succès** après publication d’un **carrousel**.

Les titres varient légèrement (**« Nouveau post »** vs **« Nouvelle Publication »**) selon l’écran — à harmoniser côté produit.

---

## 1. `sélection_de_médias_1` — Sélection de médias (variante 1)

**Rôle du design** : choisir **plusieurs** photos / vidéos dans **« Récents »**, voir l’**ordre** de sélection, **éditer** (recadrage, réglages) puis passer à l’étape suivante.

### Header (thème sombre)

1. **×** blanc à gauche — annuler / fermer.
2. **Titre** centré : **« Nouveau post »** en blanc gras.
3. **Bouton** à droite : **« Suivant (3) »** — rectangle bleu vif arrondi, texte blanc (le **3** = nombre d’éléments sélectionnés).

### Aperçu principal

4. Grande zone d’**image / vidéo** courante (coins arrondis) — ex. chaussure sur piste.
5. **Sous-couche d’actions** sur l’aperçu :
   - Bas gauche : bouton sombre **plein écran** (flèches diagonales).
   - Bas droite : **recadrer** + **réglages / filtres** (icône sliders).

### Bandeau des sélections

6. **Carrousel de miniatures** horizontal sous l’aperçu : vignettes des médias choisis ; la vignette active a une **bordure bleue** ; à droite une case **pointillée +** pour **ajouter** un média.

### Galerie

7. **En-tête de section** : **« Récents »** + chevron bas (changement d’album) ; à droite **icône caméra** (ouvrir la prise de vue).
8. **Grille** de la pellicule : sur les vignettes sélectionnées, **pastilles bleues** numérotées **1, 2, 3** (ordre d’insertion) en haut à droite.
9. Une vignette **vidéo** peut afficher une **icône lecture** (triangle).

### Tab bar

10. Cinq icônes (Home, Search, **+** central lumineux, Activity cœur, Profil) — style **sombre** cohérent avec l’écran.

---

## 2. `sélection_de_médias_2` — Sélection de médias (variante 2)

**Rôle du design** : **même flux** de choix de médias avec **légères différences** d’UI (bouton suivant sans compteur explicite, barre d’outils galerie différente, une seule sélection visible en surbrillance sur la grille).

### Header

1. **×** ; **« Nouveau post »** ; bouton bleu **« Suivant »** (sans **(3)** dans le libellé).

### Aperçu + overlays

2. Même logique **aperçu grand** + **agrandir**, **crop**, **sliders** en bas de l’image.

### Contrôles sous l’aperçu

3. **« Récents »** + chevron à gauche.
4. À droite : **caméra** + bouton circulaire **bleu** avec icône **multi-sélection** (deux carrés superposés) — met en avant le mode multi-sélection.

### Grille

5. Grille **3 colonnes** ; la vignette correspondant à l’aperçu a une **bordure bleue épaisse** (sélection courante) ; pas de pastilles **1, 2, 3** visibles sur cette maquette (sélection simple ou étape différente du flux).

### Tab bar

6. Identique en structure (Home, Search, **+** actif, Activity, Profil).

**Différence clé vs `_1`** : compteur dans **« Suivant (n) »** ; pastilles d’**ordre** sur la grille ; rangée de **miniatures sélectionnées** au-dessus de la grille dans `_1` uniquement.

---

## 3. `camera_capture_mode` — Mode caméra

**Rôle du design** : **prendre une photo** (ou basculer vers d’autres modes) pour alimenter un post, sans passer par la galerie.

1. **×** en haut à gauche — quitter la caméra.
2. **« LIVE »** avec point rouge au centre-haut — mode **Live Photo** (ou indicateur live).
3. **Flash** (éclair) en haut à droite.
4. **Viseur** : prise de vue réelle avec **masque circulaire** (coins assombris / vignette).
5. **Guide de cadrage** : rectangle blanc arrondi avec **repères jaunes** au milieu de chaque côté.
6. **Zoom** : pastilles **.5**, **1x** (sélectionné, bord jaune), **3**.
7. **Modes** défilants horizontalement : **VIDEO**, **PHOTO** (actif, texte bleu), **PORTRAIT**, bords **LAPSE** / **SLO-MO** tronqués.
8. **Barre du bas** :
   - **Vignette pellicule** à gauche (dernière photo).
   - **Déclencheur** central (double cercle blanc).
   - **Rotation caméra** avant/arrière à droite.

---

## 4. `nouvelle_publication_1` — Nouvelle publication (variante 1)

**Rôle du design** : **rédiger** le post après choix du média : légende, métadonnées, **partage croisé** vers **Facebook, Instagram, Twitter**.

### Header

1. **Retour** flèche gauche.
2. **Titre** : **« Nouvelle Publication »** gras.
3. **« Publier »** bleu à droite.

### Carte média + légende

4. **Vignette** carrée arrondie à gauche (ex. chaussures sur piste).
5. Zone texte à droite : placeholder **« Écrivez une légende... Dites à vos amis ce que vous faites ! »**.

### Carte options

6. Trois lignes avec icône cercle gris, libellé, chevron **>** :
   - **Identifier des personnes** (icône personne +).
   - **Ajouter un lieu** (épingle).
   - **Associer à un sport** — valeur **« Football »** en bleu à droite du chevron.

### Partage croisé

7. Titre section petites capitales grises : **« PARTAGER AUSSI SUR »**.
8. Carte avec **3 interrupteurs** :
   - **Facebook** — **ON** (bleu).
   - **Instagram** — **OFF** (gris).
   - **Twitter** — **OFF** (gris).

### Tab bar

9. Accueil, Explorer, **+** bleu central, Alertes, Profil.

---

## 5. `nouvelle_publication_2` — Nouvelle publication (carrousel)

**Rôle du design** : même écran de **composition** lorsque le post contient un **carrousel** (plusieurs médias) — badge **1/3**, points de pagination sur la vignette ; **moins** de réseaux dans la section partage.

1. **Header** identique (retour, **Nouvelle Publication**, **Publier**).
2. **Vignette** avec badge **« 1/3 »** en haut à droite et **points** de carrousel en bas (premier point bleu).
3. Même placeholder de **légende**.
4. Même carte **Identifier des personnes**, **Ajouter un lieu**, **Associer à un sport** → **Football**.
5. **« PARTAGER AUSSI SUR »** avec **seulement 2 lignes** : **Facebook** ON, **Instagram** OFF (**Twitter** absent de cette maquette).

6. **Tab bar** identique.

**Différence clé vs `_1`** : **carrousel** visible sur la miniature ; **Twitter** retiré du bloc partage (ou scroll tronqué).

---

## 6. `succès_de_publication_carrousel` — Succès après publication

**Rôle du design** : confirmer que le **carrousel** a bien été **publié** et proposer d’**ouvrir le post** ou de **revenir à l’accueil**.

1. **Fond** : bleu très pâle / dégradé léger.
2. **Carte** blanche large, coins très arrondis, ombre douce.
3. **Icône succès** : cercle bleu + **coche** blanche, halo bleu clair, **deux petits points** décoratifs.
4. **Titre** : **« Superbe ! »** gras noir.
5. **Message** : « Votre carrousel a été publié avec succès sur O’Sport. » gris.
6. **Sous-titre** petit capitales gris : **« APERÇU DU CARROUSEL »**.
7. **Aperçu** image verticale arrondie (ex. coureur) ; overlay **« 1/5 »** + icône **carrousel** (carrés empilés) en haut à droite.
8. **Bouton principal** : **« Voir la publication »** — bleu, **œil** blanc + texte blanc.
9. **Bouton secondaire** : **« Retour à l’accueil »** — blanc, bord gris, **icône maison** + texte noir gras.

---

## Parcours utilisateur suggéré

```
sélection_de_médias_1 ou _2  →  (optionnel) camera_capture_mode
         ↓
nouvelle_publication_1 ou _2  →  Publier
         ↓
succès_de_publication_carrousel
```

La **caméra** peut aussi être l’**entrée** première (FAB → caméra), puis retour édition / composition.

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `sélection_de_médias_1` | Picker sombre, multi-sélection numérotée, Suivant (n), miniatures |
| `sélection_de_médias_2` | Picker sombre, Suivant, multi-select, bordure sélection |
| `camera_capture_mode` | Prise de vue LIVE / modes PHOTO etc. |
| `nouvelle_publication_1` | Légende, options, FB / IG / Twitter |
| `nouvelle_publication_2` | Idem + carrousel 1/3, FB / IG seulement |
| `succès_de_publication_carrousel` | Confirmation carrousel + Voir / Accueil |

---

## Notes pour l’implémentation

- Unifier **« Nouveau post »** (sélection) et **« Nouvelle Publication »** (composer) si une seule app est cible.
- Le succès parle explicitement de **carrousel** ; prévoir un équivalent **« Votre publication a été publiée »** pour un **média unique**.
- **Cross-post** : intégrations réelles Facebook / Instagram / X dépendent des **SDK** et **politiques** des stores ; les toggles sont une intention produit.
- Réutiliser les mêmes **composants carte** entre `nouvelle_publication_1` et `_2` avec props `mediaCount`, `carouselIndex`, `networks[]`.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran post publication**.*
