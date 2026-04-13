---
name: osport-spec-ecran-onboarding-direction
description: "Spec maquettes O’Sport — onboarding direction / paywall intro, dossier `les premier ecran de direction`."
license: MIT
metadata:
  author: documentation-markdown
---

# Les premiers écrans de direction — O’Sport

## Orientation

- Décrire le dossier maquettes `les premier ecran de direction` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
- Captures attendues : `screen.png` par sous-dossier.

## Cohérence d’abord

- Croiser avec `osport-design-complet-google-stitch.md` et `osport-guide-agents-synthese-design.md` avant d’inférer seul règles métier ou API.
- Signaler les **doublons** avec `ecran forfait de paiement` (mêmes intentions de maquette) dans le texte ou une issue produit.

## Référence rapide

1. **Vue d’ensemble** — périmètre du parcours.
2. **Ordre logique du flux** — tableau des étapes.
3. **Sections numérotées** — une maquette ou groupe d’éléments par section.

## Comment appliquer

1. Lire **Vue d’ensemble** et **Ordre logique du flux onboarding**.
2. Parcourir les sections **numérotées** pour spec UI, recette test ou brief intégration.

---

## Vue d’ensemble

Le dossier regroupe :

- **Trois écrans d’onboarding** qui enchaînent une **story produit** (connexion avec des sportifs → gestion d’équipe → organiser des matchs), avec **pagination** sur 3 étapes et boutons **Suivant** / **Commencer** (+ **ignorer** sur l’étape 2).
- Un écran **O’Sport Pro** (paywall / essai gratuit), souvent affiché **après** l’introduction ou en parallèle selon le parcours monétisation.

**Remarque** : le fichier `onboarding_-_organize_matches` est **identique en intention** à la maquette du même nom dans **`ecran forfait de paiement`** (même scène « Organisez des matchs »). L’écran **`o'sport_pro_subscription_screen`** reprend aussi la **même proposition** que **`o'sport_pro_subscription_screen_1`** dans `ecran forfait de paiement` — à traiter comme **assets dupliqués** ou variantes de la même maquette en design.

---

## Ordre logique du flux onboarding

| Étape | Dossier | Pagination (3) |
|------|---------|----------------|
| 1 | `onboarding_-_connect_with_athletes` | 1ᵉʳ point actif (bleu) |
| 2 | `onboarding_-_team_management` | Barre du **milieu** active |
| 3 | `onboarding_-_organize_matches` | **3ᵉ** indicateur actif (barre allongée) |

Ensuite : monétisation optionnelle → `o'sport_pro_subscription_screen`.

---

## 1. `onboarding_-_connect_with_athletes` — Connexion avec des sportifs (étape 1)

**Rôle du design** : **premier** message de valeur — rejoindre la communauté locale et trouver des **partenaires de jeu**.

### Zone supérieure

1. **Barre de statut** visible (heure 9:41, signal, Wi‑Fi, batterie).
2. **Visuel** : photo **demi-écran** — basket en extérieur au crépuscule, silhouettes, ballon en l’air (ambiance **communauté / action**).

### Carte basse (feuille sombre)

3. **Contenant** : panneau **bleu nuit / noir** avec **coins supérieurs très arrondis** (effet bottom sheet).
4. **Poignée** : petite barre grise horizontale centrée.
5. **Titre** : « **Connectez-vous avec des sportifs** » — blanc, gras, centré.
6. **Texte** : « Trouvez des partenaires de jeu et rejoignez la communauté O’Sport locale pour organiser vos prochains matchs. » — gris clair / blanc cassé, centré.
7. **Pagination** : **3 points** — le **premier** est **bleu vif**, les deux autres gris foncé (écran **1/3**).
8. **Bouton** : « **Suivant** » — bleu vif, texte blanc gras, pleine largeur (marges), coins arrondis.
9. **Home indicator** iOS en bas.

---

## 2. `onboarding_-_team_management` — Gérer votre équipe (étape 2)

**Rôle du design** : expliquer la **dimension club / équipe** (création, matchs, stats).

### Fond et illustration

1. **Fond** : gris très clair / off-white uniforme.
2. **Illustration** centrale :
   - Grand **cercle** bleu très pâle en arrière-plan.
   - **Carré blanc** arrondi avec ombre, contenant des **formes géométriques** bleues (hexagone, drapeau, etc.).
   - **Pastille bleue** qui chevauche le coin bas-droit du carré avec **trophée** blanc.

### Texte

3. **Titre** : « **Gérez votre équipe** » — gras, bleu marine foncé, centré.
4. **Description** : « Créez votre club, organisez des matchs et suivez vos statistiques de victoires. » — gris / bleu marine, centré, plusieurs lignes.

### Navigation

5. **Pagination** : **point** gris — **barre bleue allongée** (milieu actif = **2/3**) — **point** gris.
6. **Bouton principal** : « **Suivant** » — bleu vif, texte blanc, pleine largeur.
7. **Lien secondaire** : « **Ignorer pour l'instant** » — texte gris-bleu, sous le bouton (passer le reste du onboarding ou reporter).
8. **Home indicator** en bas.

---

## 3. `onboarding_-_organize_matches` — Organisez des matchs (étape 3)

**Rôle du design** : clôturer l’intro par la **réservation de terrains** et les **défis** entre équipes ; passage à l’app avec **« Commencer »**.

1. **Fond** clair (~`#F8F9FB`).
2. **Illustration** : halo circulaire bleu pâle ; **cercle blanc** (calendrier bleu) + **cercle bleu** (épingle blanche) en chevauchement, ombres légères.
3. **Titre** : « **Organisez des matchs** » — noir gras.
4. **Sous-titre** : « Réservez des terrains et lancez des défis aux autres équipes de votre ville. » — gris, centré.
5. **Pagination** : deux **petits points** clairs + **barre bleue longue** (position **3/3**).
6. **Bouton** : « **Commencer** » — bleu, blanc, pilule, ombre.
7. Pas de lien « Ignorer » sur cette dernière étape dans la maquette.

---

## 4. `o'sport_pro_subscription_screen` — O’Sport Pro (essai / abonnement)

**Rôle du design** : présenter **l’offre Pro** après (ou indépendamment de) l’onboarding — avantages, **Annuel** vs **Mensuel**, prix, essai, légal.

### Header

1. **×** fermer (gauche).
2. **« O'Sport Pro »** centré en bleu.
3. **?** aide dans un cercle sombre (droite).

### Accroche

4. **Icône** carré bleu arrondi + **badge étoile** blanc.
5. **Titre** : « **Passez au niveau supérieur** » — marine gras.
6. **Sous-titre** gris sur la communauté d’élite et les performances.

### Avantages

7. **Quatre** lignes avec **coche verte** ronde : statistiques avancées ; zéro publicité ; badges exclusifs ; priorité réservations.

### Forfait

8. **Toggle** pilule : **Annuel** sélectionné (fond blanc, texte bleu, badge **-20 %** vert) ; **Mensuel** gris inactif.

### Carte tarif

9. Encart blanc bord bleu clair : **« ABONNEMENT PRO »** ; badge **« MEILLEURE OFFRE »** ; prix **« 4,99€ / mois »** en grand.
10. Séparateur ; **7 jours d'essai gratuit** (bouclier vert) ; **Annulable à tout moment** (calendrier gris).

### CTA et pied

11. Bouton **« Commencer l'essai gratuit »** bleu plein.
12. **Disclaimer** petit gris : renouvellement **59,88 €/an** (soit 4,99 €/mois) sauf annulation **24 h** avant fin d’essai.
13. Liens soulignés : **Conditions**, **Confidentialité**, **Restaurer**.

---

## Liens avec d’autres dossiers

| Ce dossier | Dossier proche |
|-----------|----------------|
| `onboarding_-_organize_matches` | `ecran forfait de paiement/onboarding_-_organize_matches` |
| `o'sport_pro_subscription_screen` | `ecran forfait de paiement/o'sport_pro_subscription_screen_1` (thème clair identique) |

En base de code, préférer **une seule source** de vérité pour éviter les divergences de copy ou de prix.

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `onboarding_-_connect_with_athletes` | Étape 1/3 — communauté & partenaires, Suivant |
| `onboarding_-_team_management` | Étape 2/3 — club & stats, Suivant + Ignorer |
| `onboarding_-_organize_matches` | Étape 3/3 — terrains & défis, Commencer |
| `o'sport_pro_subscription_screen` | Paywall Pro — essai 7 j, Annuel/Mensuel, légal |

---

## Notes pour l’implémentation

- **Navigation** : `Suivant` avance `pageIndex` ; `Ignorer` peut appeler `completeOnboarding()` ou ouvrir l’accueil sans enregistrer les étapes intermédiaires (définition produit).
- **Paywall** : le **×** doit décider si l’utilisateur peut **revenir** à l’app sans souscrire (soft paywall) ou seulement fermer après achat (rare).
- Le nom du dossier parent utilise **« premier »** au singulier — cohérent avec le dossier sur disque ; en français correct on écrirait souvent **« premiers »**.

---

*Document basé sur les fichiers `screen.png` du dossier **les premier ecran de direction**.*
