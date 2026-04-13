---
name: osport-spec-ecran-teams-create
description: "Spec maquettes O’Sport — création d’équipe, dossier `ecran de creation d'equipe`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans de création d’équipe — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de creation d'equipe` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

Les designs couvrent :

- La **liste des équipes** (créées / rejointes) et le **point d’entrée** pour créer une équipe.
- Un **assistant de création en 3 étapes** : visuels (couverture + logo) → détails (nom, sport, niveau, description) → logistique (lieu + type d’équipe) puis **Créer mon équipe**.
- La **modification** d’une équipe existante (formulaire + enregistrement + suppression).
- Des **retours utilisateur** : succès après mise à jour, **confirmation de suppression** (action destructive).

Style récurrent : fond gris très clair, **bleu** accent, cartes arrondies avec ombres légères, typographie sans-serif.

---

## 1. `liste_de_mes_équipes` — Mes équipes

**Rôle du design** : afficher toutes les équipes de l’utilisateur (celles qu’il a créées et celles qu’il a rejointes) et permettre d’**ajouter** une nouvelle équipe.

1. **Titre** : « Mes équipes » en grand, gras, en haut à gauche.
2. **Bouton création** : cercle **bleu clair** en haut à droite avec **« + »** blanc — accès probable au flux « Créer une équipe ».
3. **Section « Mes équipes créées »** :
   - Sous-titre + **pastille grise** avec le **nombre** d’équipes créées (ex. **2** dans la maquette).
   - **Grille 2 colonnes** de cartes équipe.
4. **Carte équipe (modèle)** :
   - **Image de fond** rectangulaire, coins supérieurs arrondis.
   - **Logo** circulaire qui **chevauche** le bas de l’image (bord blanc épais pour le détacher du visuel).
   - **Nom d’équipe** en gras sous le visuel (ex. « Paris FC », « Les Lions »).
   - **Badge** pilule bleu clair en bas : icône **personne(s)** + **nombre de membres** (ex. 24, 12).
5. **Section « Équipes rejointes »** :
   - Sous-titre + pastille avec le **nombre** (ex. **4**).
   - Même grille 2 colonnes.
   - Exemples dans la maquette : Sunday Runners, Yoga Club, Basket City, Fit Fam — chacune avec image, picto/logo et effectif.
6. **Barre de navigation basse** (4 onglets) :
   - **Accueil** (icône maison, gris).
   - **Équipes** (icône groupe, **bleu** — onglet actif).
   - **Agenda** (calendrier, gris).
   - **Profil** (silhouette, gris).

**Notes d’implémentation** : cartes avec ombre douce et rayons ~15–20 px ; accent bleu type « sky » ; marges homogènes entre cartes.

---

## 2. `team_creation_-_visuals_1` — Création, étape 1 sur 3 (visuels)

**Rôle du design** : collecter la **photo de couverture** et le **logo** de l’équipe avant les informations textuelles.

1. **Retour** : chevron gauche en haut à gauche.
2. **Titre barre** : « Créer une équipe » centré, gras.
3. **Annuler** : lien texte **bleu** en haut à droite.
4. **Progression** :
   - À gauche : « **Étape 1 de 3** » en bleu.
   - À droite : « **33 %** » en gris clair.
   - **Barre** en 3 segments : premier segment **bleu épais**, les deux autres gris clair.
5. **Titre de section** : « Visuels de l’équipe » en grand, gras.
6. **Sous-texte** : « Ajoutez des visuels pour rendre votre équipe unique et reconnaissable sur le terrain. » en gris.
7. **Zone photo de couverture** :
   - Grand rectangle très arrondi, **bordure en pointillés** gris, fond avec **hachures** diagonales légères.
   - Au centre : cercle blanc + **icône appareil photo** bleue avec petit **« + »**.
   - Libellé sous l’icône : « **Photo de couverture** » en gras.
8. **Zone logo** :
   - **Cercle** plus petit, chevauchant le **coin bas gauche** de la couverture.
   - Fond blanc, motif pointillé gris, picto **ballon** stylisé + petit texte « LOGO » en capitales grises.
   - **Bouton rond bleu** avec « + » blanc sur le bord du cercle (ajout / remplacement du logo).
9. **Encart « Conseil de pro »** :
   - Fond bleu très pâle, coins arrondis.
   - **Ampoule** bleue à gauche, titre « Conseil de pro » en gras.
   - Texte : conseil d’utiliser une **photo d’action** de qualité pour la couverture et un **logo simple sur fond transparent**.
10. **Bouton** : « Suivant » — bandeau bleu, texte blanc + **flèche droite** blanche, ombre légère.

---

## 3. `team_creation_-_details` — Création, étape 2 sur 3 (détails)

**Rôle du design** : saisir l’identité et la description de l’équipe (nom, sport, niveau, texte libre limité à 200 caractères).

1. **Retour** : flèche gauche.
2. **Titre barre** : « Création d'équipe » centré, gras.
3. **Ligne d’étape** :
   - À gauche : « **Étape 2 sur 3** » en bleu.
   - À droite : libellé d’étape « **Détails** » en gris.
4. **Barre de progression** : environ **2/3** remplis en bleu, reste gris clair.
5. **Titre** : « Détails de l'équipe ».
6. **Sous-titre** : « Dites-nous en plus sur votre futur groupe. » en gris.
7. **Champ « Nom de l'équipe »** :
   - Libellé gras.
   - Champ arrondi fond gris clair, placeholder « Ex: Les Lions de Paris ».
8. **Champ « Sport »** :
   - Libellé gras.
   - Sélecteur type liste : **ballon** à gauche, texte « Sélectionner un sport », **double chevron** à droite.
9. **Champ « Niveau »** :
   - Libellé gras.
   - **Segmented control** dans un fond gris clair : « Débutant », « **Intermédiaire** » (sélectionné — fond bleu, texte blanc), « Expert ».
10. **Champ « Description »** :
    - Libellé gras.
    - Grande zone multilignes, fond gris clair, placeholder sur les objectifs / jours d’entraînement.
    - **Compteur** en bas à droite : « **0/200** ».
11. **Bouton** : « Suivant » — bleu, texte blanc + flèche droite, ombre.

---

## 4. `team_creation_-_logistics` — Création, étape 3 sur 3 (lieu & type)

**Rôle du design** : définir le **lieu de jeu / QG** de l’équipe et le **profil** compétitif vs loisir, puis valider la création.

1. **Retour** : chevron gauche.
2. **Titre barre** : « Création d'équipe ».
3. **Indicateur d’étapes** : deux **pastilles** bleu clair + **barre bleue** allongée (fin du parcours).
4. **Texte d’étape** : « **Étape 3 sur 3** » en petit gris sous l’indicateur.
5. **Bloc « Où jouez-vous ? »** :
   - Titre en gras.
   - **Champ de recherche** blanc, coins arrondis, ombre douce : **épingle** bleue à gauche, placeholder « Rechercher un stade, une ville... ».
   - **Aide** sous le champ : « Ce sera le QG de votre équipe. » en gris.
6. **Bloc « Type d'équipe »** :
   - Titre en gras.
   - **Deux cartes** côte à côte :
     - **Compétitif** (non sélectionné dans l’exemple) : fond blanc, ombre, **trophée** orange sur fond orange clair, titre « Compétitif », sous-texte gris « Pour la gagne ».
     - **Loisir** (sélectionné) : fond bleu clair, **bordure bleue**, **pastille** bleue avec **coche** blanche en haut à droite, icône **ballon** dans un cercle, titre et sous-texte en bleu (« Pour le fun »).
7. **Bouton final** : « **Créer mon équipe** » — bleu pilule, texte blanc + **coche** dans un petit cercle blanc à droite, ombre.

---

## 5. `succès_des_modifications_équipe` — Succès après enregistrement

**Rôle du design** : confirmer que les **modifications** sur une fiche équipe ont bien été **sauvegardées** (bottom sheet / modale).

1. **Arrière-plan** : écran précédent **assombri et flouté** (on devine un header « Equipe Alpha », retour, menu **⋯**).
2. **Contenant** : **feuille du bas** (bottom sheet) fond **gris très foncé / noir**, **coins supérieurs arrondis**.
3. **Poignée** : petite **barre horizontale** grise en haut du sheet (indicateur de glissement).
4. **Icône succès** : cercle **vert** avec **coche** blanche, léger halo lumineux.
5. **Titre** : « **Modifications enregistrées !** » en blanc, gras.
6. **Message** : « Les informations de votre équipe ont été mises à jour avec succès. » en gris clair.
7. **Bouton** : « **Super !** » — pleine largeur, **bleu**, texte blanc gras, forme pilule.

**Comportement attendu** : fermeture du sheet au tap sur « Super ! » et retour à l’écran équipe / liste.

---

## 6. `team_creation_-_visuals_3` — Modifier l’équipe

**Rôle du design** : **éditer** une équipe existante (visuels + champs principaux), **enregistrer** ou lancer la **suppression**.

1. **Retour** : flèche **bleue** à gauche.
2. **Titre** : « Modifier l'équipe » centré, gras.
3. **Action droite** : lien texte **« Enregistrer »** en bleu.
4. **Bannière équipe** : grande zone arrondie avec visuel placeholder (forme 3D grise) + **icône caméra** au centre pour changer la bannière.
5. **Logo** : cercle chevauchant le bas de la bannière, fond bleu avec **caméra** (upload logo).
6. **Formulaire** (labels en **petites capitales** grises) :
   - **NOM DE L'ÉQUIPE** : champ gris clair, valeur exemple « FC Titans Paris ».
   - **Ligne deux colonnes** :
     - **SPORT** : liste déroulante, valeur « Football », chevrons à droite.
     - **NIVEAU** : liste, valeur « Intermédiaire », chevrons à droite.
   - **DESCRIPTION** : zone multilignes avec texte descriptif (ex. équipe parisienne, créneaux le mardi soir).
7. **Zone destructive** :
   - Bouton texte **rouge** centré : « **Supprimer l'équipe** ».
   - Légende grise : « Cette action supprimera définitivement toutes les données de l'équipe. »
8. **Barre de navigation basse** (5 éléments) :
   - Boussole (gris).
   - **Groupe** (bleu — actif).
   - **Bouton « + »** circulaire bleu central (création rapide).
   - Bulle message (gris).
   - Profil (gris).

---

## 7. `team_creation_-_visuals_2` — Confirmation de suppression d’équipe

**Remarque** : le nom du dossier contient « visuals_2 », mais la maquette correspond à une **modale de confirmation** avant **suppression définitive** d’une équipe (souvent ouverte depuis « Supprimer l'équipe » sur l’écran d’édition).

**Rôle du design** : forcer une **double confirmation** pour une action **irréversible**.

1. **Overlay** : fond d’écran (header type « Équipe » avec retour) **assombri** semi-transparent.
2. **Modale** : carte **blanche** centrée, coins très arrondis (~30 px).
3. **Icône d’alerte** : triangle **rouge** avec point d’exclamation, sur fond **rose pâle** circulaire.
4. **Titre** : « **Supprimer l’équipe ?** » en gras.
5. **Corps du message** : « Cette action est irréversible. Toutes les données, matchs et statistiques seront définitivement perdus. » en bleu-gris.
6. **Bouton destructif** : « **Supprimer définitivement** » — fond **rouge**, texte blanc gras, forme pilule.
7. **Bouton secondaire** : « **Annuler** » — fond gris très clair, texte bleu-gris, pilule — ferme la modale sans supprimer.

---

## Ordre logique du parcours « création »

1. `liste_de_mes_équipes` → tap sur **+**  
2. `team_creation_-_visuals_1` → **Suivant**  
3. `team_creation_-_details` → **Suivant**  
4. `team_creation_-_logistics` → **Créer mon équipe**  

Ensuite, pour la **gestion** d’une équipe existante :

- `team_creation_-_visuals_3` (**Modifier l'équipe** → **Enregistrer**) → `succès_des_modifications_équipe`  
- Depuis la même fiche, **Supprimer l'équipe** → `team_creation_-_visuals_2` (confirmation).

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `liste_de_mes_équipes` | Liste créées / rejointes, FAB +, tab bar |
| `team_creation_-_visuals_1` | Étape 1/3 — couverture + logo + conseil |
| `team_creation_-_details` | Étape 2/3 — nom, sport, niveau, description 200 |
| `team_creation_-_logistics` | Étape 3/3 — lieu, type compétitif/loisir, création |
| `succès_des_modifications_équipe` | Bottom sheet succès après mise à jour |
| `team_creation_-_visuals_3` | Écran édition équipe + enregistrer + lien supprimer |
| `team_creation_-_visuals_2` | Modale confirmation suppression (nom de dossier trompeur) |

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de creation d'equipe**.*
