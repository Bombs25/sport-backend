---
name: osport-spec-ecran-user-profile
description: "Spec maquettes O’Sport — profil utilisateur, dossier `ecran profil utilisateur`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans profil utilisateur — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran profil utilisateur` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

- Le **profil joueur vu par un visiteur** (stats, bio, sport, actions Suivre / Message / ami, grille de posts).
- Une variante **profil style « @handle »** avec **réglages** en header et libellés **anglais** (Follow).
- La **liste des abonnés** (onglets Followers / Following, recherche, actions Suivre / Suivi / Supprimer).
- Les **demandes de suivi** pour **compte privé** (confirmer ou supprimer, amis en commun).

---

## 1. `profil_joueur_(vue_visiteur)` — Profil joueur (vue visiteur)

**Rôle du design** : afficher le **profil public** d’un autre utilisateur avec identité sportive, **statistiques**, CTA sociaux et **grille** de publications.

### Barre supérieure

1. **Retour** : chevron gauche.
2. **Titre** : pseudo **@Lucas_Str** centré, gras.
3. **Menu** : **⋮** vertical à droite (signaler, partager, etc.).

### En-tête profil

4. **Photo de profil** : grand cercle avec **halo** bleu clair ; badge pilule bleue **« ELITE »** en bas à droite du cercle.
5. **Statistiques** (3 colonnes séparées par fines **barres verticales**) :
   - **124** POSTS  
   - **1.2k** FOLLOWERS  
   - **450** SUIVI  
6. **Nom** : « Lucas Bernard » en très gras.
7. **Tag sport** : pilule bleu clair **« Football ⚽ »**.
8. **Bio** : texte gris (club, passion, défis, emoji **⚡**).

### Actions

9. **« Suivre »** — bouton principal **bleu** plein, texte blanc.
10. **« Message »** — secondaire, fond blanc, **bord bleu**, texte bleu.
11. **Ajout ami** — petit bouton **circulaire bleu** avec icône **personne +**.

### Onglets contenu

12. Trois icônes : **grille** (active — soulignement bleu), **lecture / vidéos**, **épingle** (lieux ou posts géolocalisés).

### Grille de posts

13. Grille **3×3** de miniatures carrées ; certaines avec icône **carrousel** (plusieurs médias), une avec **lecture vidéo** (triangle).

### Tab bar

14. Cinq zones : Accueil, Explorer, **+** bleu central, Communauté, **Profil** (**actif** en bleu).

---

## 2. `user_profile_screen_1` — Profil utilisateur (variante 1)

**Rôle du design** : **même famille** que la vue visiteur avec **autre identité visuelle** (bordure dégradée sur l’avatar, boutons **noirs/gris**, textes **anglais** sur plusieurs CTA).

### Barre supérieure

1. **Retour** ; titre **@alex_sports** ; icône **engrenage** (réglages — peut indiquer **propre profil** ou visiteur selon produit).

### En-tête

2. **Avatar** avec **bordure dégradée** (orange, rose, bleu) ; **point vert** en ligne en bas à droite.
3. **Nom** : « Alex Johnson ».
4. **Bio** : « Marathon enthusiast & Soccer Coach » en **bleu clair**.
5. **Lieu** : épingle grise + « San Francisco, CA ».
6. **Stats** : **124** Posts | **3.5k** Followers | **420** Following (libellés **anglais**).

### Actions

7. **« Follow »** — bandeau **noir**, texte blanc (primaire).
8. **Message** — carré **gris clair**, icône **bulle** noire.
9. **Ajout ami** — carré gris clair, icône **personne +** bleue.

### Onglets contenu

10. **Grille** (active, **noir**), **Reels** (ardoise), **Tagged** (personne dans cadre) — libellés implicites par icônes.

### Grille + Tab bar

11. Grille 3×3 (chaussures, ballon au filet avec icône multi-photos, musculation, etc.).
12. Tab bar : Home, Search, **FAB + noir** central, Notifications, **mini avatar** utilisateur courant (profil actif implicite).

**Différence clé vs `profil_joueur_(vue_visiteur)`** : style **monochrome** (noir), **anglais** sur Follow/stats, pas de badge ELITE ; **engrenage** à la place de ⋮.

---

## 3. `user_profile_screen_2` — Liste abonnés / abonnements

**Rôle du design** : afficher les **followers** ou les **comptes suivis** d’un utilisateur (**Alex Johnson**), avec **recherche** et **actions** par ligne.

### Header

1. **Retour** ; titre **« Alex Johnson »** centré, gras.

### Onglets

2. **« 3.5k Followers »** — actif (texte **bleu** + **soulignement** épais bleu).
3. **« 420 Suivi »** (Following) — inactif, gris.

### Recherche

4. Champ **pleine largeur** gris clair arrondi : **loupe** + placeholder **« Rechercher un follower... »**.

### Liste

Chaque ligne : **avatar** ; **nom** gras ; **@handle** gris ; bouton à droite (états variés) :

5. **Sarah Wilson** — **« Suivi »** (contour bleu, texte bleu).
6. **Marcus Chen** — **« Suivre »** (bleu plein).
7. **Emma Stone** — **« Supprimer »** (retirer un follower — fond gris clair, texte noir).
8. **David Rossi** — Suivre.
9. **Team Phoenix** — Suivi.
10. **Lucas Mayer** — Supprimer.
11. **Clara Sporty** — Suivre.

*(Les trois types de boutons illustrent suivre quelqu’un qui ne vous suit pas, relation déjà suivie, et **suppression** d’un abonné sur **votre** liste followers.)*

### Tab bar

12. Home, Search, **FAB + noir**, Notifications, avatar profil — cohérent avec `user_profile_screen_1`.

---

## 4. `demandes_de_suivi_(compte_privé)` — Demandes de suivi

**Rôle du design** : traiter les **demandes d’abonnement** reçues quand le compte est **privé** (accepter = follower, supprimer = refuser).

### Header

1. **Retour** ; titre **« Demandes de suivi »** ; **séparateur** fin gris sous le header.

### Résumé

2. Texte bleu-gris : **« 4 demandes en attente »**.

### Liste des demandes

Pour chaque demande :

3. **Avatar** circulaire.
4. **Nom** gras + **@pseudo** dessous en bleu-gris.
5. **« X amis en commun »** en bleu clair (nombre variable : 12, 5, 2, 8 dans la maquette).
6. **« Confirmer »** — bouton **bleu** plein, texte blanc.
7. **« Supprimer »** — bouton **gris clair**, texte noir (refus / suppression de la demande).

**Exemples** : Léa Marchand, Thomas Girard, Sarah Williams, Nicolas Petit.

### Tab bar

8. Accueil, Découvrir, **+** bleu central, **Activités** (**cloche bleue** + **point** sous le libellé — actif), Profil.

---

## Parcours logiques

| Depuis | Vers (logique) |
|--------|----------------|
| Profil privé (réglages / notif) | `demandes_de_suivi_(compte_privé)` |
| Tap « Followers » ou « SUIVI » sur profil | `user_profile_screen_2` |
| Recherche utilisateur → profil | `profil_joueur_(vue_visiteur)` ou `user_profile_screen_1` selon variante produit |

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `profil_joueur_(vue_visiteur)` | Profil FR, ELITE, Suivre / Message / ami, grille |
| `user_profile_screen_1` | Profil EN-friendly, Follow noir, grille, engrenage |
| `user_profile_screen_2` | Liste Followers / Suivi + recherche + actions |
| `demandes_de_suivi_(compte_privé)` | Demandes compte privé, Confirmer / Supprimer |

---

## Notes pour l’implémentation

- **`user_profile_screen_1`** et **`profil_joueur_(vue_visiteur)`** sont deux **skins / marchés** du même **écran profil** — à factoriser (composant unique + thème ou variante A/B).
- **`Supprimer`** sur la liste followers = **remove follower** (l’autre ne vous suit plus) — wording à valider (vs « Retirer ») pour éviter la confusion avec « bloquer ».
- Le nom exact du dossier peut afficher **compte_privé** ou **compte_privé** (Unicode) selon le système de fichiers ; vérifier le chemin en clone.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran profil utilisateur**.*
