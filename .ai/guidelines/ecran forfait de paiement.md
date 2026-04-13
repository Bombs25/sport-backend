---
name: osport-spec-ecran-paywall-pro
description: "Spec maquettes O’Sport — forfait Pro / paywall, dossier `ecran forfait de paiement`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans forfait de paiement — O’Sport Pro

## Orientation

- Décrire le dossier maquettes `ecran forfait de paiement` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

Le dossier contient **deux variantes** du **paywall / abonnement O’Sport Pro** (thème **clair** et thème **sombre**) avec les mêmes **avantages**, **basculer Annuel / Mensuel**, **prix**, **essai gratuit 7 jours** et **mentions légales**.

Une troisième maquette — `onboarding_-_organize_matches` — est un écran d’**onboarding** (« Organisez des matchs ») et **n’est pas** un écran de tarification au sens strict ; elle est sans doute regroupée ici pour le **parcours** global (découverte → valeur → monétisation) ou par commodité de classement des assets.

---

## 1. `o'sport_pro_subscription_screen_1` — Abonnement Pro (thème clair)

**Rôle du design** : convertir l’utilisateur vers **O’Sport Pro** avec liste d’avantages, choix **Annuel** (mis en avant) vs **Mensuel**, carte tarifaire, **essai gratuit**, CTA et liens légaux.

### Barre supérieure

1. **Fermer** : icône **×** sombre en haut à gauche (fermer le paywall).
2. **Titre** : **« O'Sport Pro »** centré, bleu moyen.
3. **Aide** : **?** dans un cercle en haut à droite (FAQ / support abonnement).

### Accroche

4. **Icône centrale** : carré bleu arrondi avec **badge étoile / ruban** blanc, ombre légère.
5. **Titre principal** : « **Passez au niveau supérieur** » — grand, gras, bleu marine foncé.
6. **Sous-titre** : « Rejoignez la communauté d’élite et optimisez vos performances sportives. » en gris.

### Avantages

7. **Liste** de **4** lignes avec **coche** verte circulaire à gauche :
   - Statistiques avancées  
   - Zéro publicité  
   - Badges de profil exclusifs  
   - Priorité sur les réservations  

### Choix de forfait

8. **Segmented control** (pilule) : **Annuel** (sélectionné — fond blanc) avec petit badge **« -20 % »** vert ; **Mensuel** (fond gris clair, inactif).
9. **Carte abonnement** (blanc, **bord bleu clair** arrondi) :
   - En-tête : **« ABONNEMENT PRO »** en petites capitales bleues ; badge **« MEILLEURE OFFRE »** bleu clair à droite.
   - **Prix** en très grand : **« 4,99€ / mois »** (équivalent annuel présenté au mois).
   - **Séparateur** fin.
   - Ligne **« 7 jours d'essai gratuit »** avec petite icône **bouclier + coche** verte.
   - Ligne **« Annulable à tout moment »** avec icône **calendrier** gris clair.

### Pied

10. **Bouton principal** : « **Commencer l'essai gratuit** » — bleu vif, texte blanc, coins arrondis, ombre.
11. **Disclaimer** (petit gris) : après l’essai de 7 jours, renouvellement automatique à **59,88 €/an** (équivalent **4,99 €/mois**) sauf résiliation **24 h** avant la fin de la période.
12. **Liens** soulignés gris : **Conditions** | **Confidentialité** | **Restaurer** (restaurer les achats App Store / Play).

### Style

13. Fond **blanc**, bleu primaire (~`#4A9FFF`), textes marine / gris, coches vertes pour la valeur perçue.

---

## 2. `o'sport_pro_subscription_screen_2` — Abonnement Pro (thème sombre)

**Rôle du design** : **même proposition commerciale** que `_1` avec une **esthétique premium sombre** et des **accents or / jaune** (alternative A/B test ou mode sombre).

### Barre supérieure

1. **×** fermer (gauche) ; titre **« O'Sport Pro »** centré ; **?** aide (droite).

### Héros

2. **Grand cercle doré** centré avec **médaille** noire et **étoile** (statut Pro / élite).
3. **Titre** : « **Passez au niveau supérieur** » en blanc gras.
4. **Sous-titre** : même phrase sur la communauté d’élite — gris clair.

### Avantages

5. **Quatre** lignes avec **coches dorées** circulaires (même libellé que l’écran clair).

### Forfait et prix

6. **Toggle** : **« Annuel -20 % »** actif (fond **jaune / or**) ; **« Mensuel »** inactif (fond sombre).
7. **Carte** sombre avec **bordure dorée fine** :
   - **« ABONNEMENT PRO »** en jaune.
   - Badge **« MEILLEURE OFFRE »**.
   - Prix **« 4,99€ / mois »** en grand **blanc**.
   - **7 jours d'essai gratuit** — icône **bouclier** dorée.
   - **Annulable à tout moment sur l'App Store** — icône calendrier (libellé explicite App Store dans cette variante).

### Pied

8. **Bouton** : « **Commencer l'essai gratuit** » — bandeau **jaune / or** pleine largeur, **texte noir** gras.
9. **Même disclaimer** légal (59,88 €/an, 4,99 €/mois, 24 h avant fin).
10. **Liens** : Conditions, Confidentialité, Restaurer — soulignés, gris.

### Style

11. Fond **bleu nuit / anthracite** ; contraste **jaune-or** pour CTA et hiérarchie « premium ».

---

## 3. `onboarding_-_organize_matches` — Onboarding : organiser des matchs

**Remarque** : ce n’est **pas** un écran de **tarif** ; c’est une **étape d’introduction** (pagination = 3ᵉ écran) qui annonce la **réservation de terrains** et les **défis** entre équipes.

**Rôle du design** : expliquer une **valeur produit** avant ou après connexion, puis enchaîner avec **« Commencer »**.

1. **Fond** : gris très clair / off-white (~`#F8F9FB`).
2. **Illustration** centrale :
   - Grand **halo** circulaire bleu très pâle.
   - **Cercle blanc** (haut-gauche) avec **icône calendrier** bleue.
   - **Cercle bleu vif** (bas-droite, chevauchement) avec **épingle de carte** blanche.
   - Ombres douces sur les cercles.
3. **Titre** : « **Organisez des matchs** » — centré, gras, noir / gris foncé.
4. **Description** : « Réservez des terrains et lancez des défis aux autres équipes de votre ville. » — gris moyen, centré, interligne confortable.
5. **Indicateur de pagination** : **deux** petits points gris + **barre bleue allongée** (écran **3** sur une série).
6. **Bouton** : « **Commencer** » — bleu vif, texte blanc gras, pilule, ombre bleue légère.

---

## Comparaison des deux paywalls

| Élément | `_1` (clair) | `_2` (sombre) |
|--------|----------------|----------------|
| Fond | Blanc | Bleu nuit |
| Accents secondaires | Vert (-20 %, coches) | Or (coches, bordure carte) |
| CTA essai | Bleu, texte blanc | Jaune, texte noir |
| Mention annulation | « Annulable à tout moment » (générique) | « … sur l'App Store » explicite |
| Icône héros | Carré bleu + badge étoile | Cercle or + médaille |

Le **contenu marketing** (4 avantages, 4,99 €/mois présenté, 7 jours gratuits, 59,88 €/an, liens) est **aligné** entre les deux.

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `o'sport_pro_subscription_screen_1` | Paywall Pro — thème clair |
| `o'sport_pro_subscription_screen_2` | Paywall Pro — thème sombre / or |
| `onboarding_-_organize_matches` | Onboarding étape 3 — organiser matchs, Commencer |

---

## Liens avec d’autres dossiers design

- Le flux **paiement après souscription** (carte, Apple Pay, confirmation) est décrit dans **`ecran de gestion de paiement`** (`gestion_de_l'abonnement_premium_*`, etc.).
- Ce dossier se concentre sur la **vente du forfait Pro** et une **slide onboarding** connexe.

---

## Notes pour l’implémentation

- **Un seul écran logique** « Paywall Pro » avec **thème** `light` | `dark` ou deux assets marketing.
- Brancher **Restaurer** sur les API **StoreKit** / **Google Play Billing**.
- Le **toggle Mensuel** inactif sur les maquettes prévoit quand même un **état sélectionné** avec prix mensuel distinct en prod si l’offre existe.
- Déplacer ou dupliquer `onboarding_-_organize_matches` vers un dossier **onboarding** en repo si vous séparez **marketing** et **feature onboarding**.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran forfait de paiement**.*
