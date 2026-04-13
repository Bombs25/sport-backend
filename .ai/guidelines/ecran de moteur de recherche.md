---
name: osport-spec-ecran-search
description: "Spec maquettes O’Sport — recherche, dossier `ecran de moteur de recherche`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans du moteur de recherche — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de moteur de recherche` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

- La **recherche d’amis / sportifs** (mode segmenté, barre de recherche, filtres rapides, résultats avec **Suivre** / états).
- La **recherche d’équipes** (onglets joueur / équipe / terrain, filtres sport + rayon, cartes équipe et **demande d’adhésion**).
- Des **modales / feuilles** : **filtres équipe** (sport, distance, type), **rayon de recherche** seul.
- La **découverte sociale** hors champ texte : **invitation des contacts** (répertoire + contacts déjà sur O’Sport), **partage de profil** et **QR code**.

Les écrans `search_-_find_friends_1` et `search_-_find_friends_2` sont **très proches** ; la différence visible porte surtout sur **le nombre d’exemples** et les **états des boutons** (ex. « Invité » sur `_2`).

---

## 1. `search_-_find_friends_1` — Recherche : trouver un ami (variante 1)

**Rôle du design** : chercher des **personnes** à suivre autour de soi, avec **rayon**, **sport**, **niveau** et raccourcis **Facebook / Contacts / QR**.

1. **Titre** : « **Recherche** » centré, gras.
2. **Segmented control** (pilule) : **« Un ami »** sélectionné (fond bleu, texte blanc) ; **« Une équipe »** inactif (gris).
3. **Barre de recherche** : fond blanc, bord gris ; **loupe** ; placeholder **« Rechercher un sportif... »** ; **icône filtres / réglages** bleue à droite.
4. **Chips filtres** (rangée horizontale) :
   - **« Radius: 20 km »** — pastille bleu clair + **épingle** (le libellé est en anglais « Radius » dans la maquette).
   - **« Sport »** — pastille blanche bordée.
   - **« Niveau »** — idem.
5. **Raccourcis connexion** (3 cercles + légendes capitales bleues) :
   - **FACEBOOK** — cercle bleu, logo **f** blanc.
   - **CONTACTS** — cercle blanc, icône document + personne bleue.
   - **QR CODE** — cercle blanc, picto QR bleu.
6. **Liste de résultats** (cartes blanches arrondies, ombre légère) — par profil :
   - **Avatar** ; **point vert** en bas à droite pour certains (en ligne).
   - **Nom** gras ; **badge vérifié** (coche bleue) pour ex. Thomas R.
   - **Lieu** : épingle grise + ville + **distance** (ex. Paris • 2 km).
   - **Sports** en petit gris (ex. Tennis, Running).
   - **Bouton « Suivre »** — bleu plein (blanc) ou **style secondaire** (texte bleu sur fond bleu très pâle) selon la ligne.
7. **Tab bar** (5) : Accueil, **Recherche** (**loupe bleue** — actif), **FAB +** bleu central surélevé, Équipes, Profil.

---

## 2. `search_-_find_friends_2` — Recherche : trouver un ami (variante 2)

**Rôle du design** : **même écran** que `_1` avec **plus de lignes** dans la liste et un **état supplémentaire** sur le bouton d’action.

1. **Header** identique : titre Recherche ; onglets **Un ami** / **Une équipe** ; barre **Rechercher un sportif...** ; chips **Radius: 20 km**, Sport, Niveau ; raccourcis Facebook / Contacts / QR Code.
2. **Liste enrichie** (5 profils d’exemple) :
   - Thomas R. (vérifié, Paris, Suivre bleu).
   - Sarah L. (Boulogne, bouton Suivre **secondaire**).
   - Marc D. (Vincennes, en ligne, Suivre bleu).
   - **Elodie M.** (Paris) — bouton **« Invité »** sur fond **gris clair** (invitation déjà envoyée / en attente).
   - Lucas P. (Neuilly, Suivre bleu).
3. **Tab bar** identique (Recherche active, FAB +).

**Différence clé vs `_1`** : présence du statut **« Invité »** et liste plus longue pour documenter les **états des CTA**.

---

## 3. `search_-_find_teams` — Recherche : trouver une équipe

**Rôle du design** : chercher des **équipes** (et conceptuellement terrains / joueurs via onglets), appliquer **filtres** (sport, rayon), voir effectifs et **demander à rejoindre**.

1. **Barre de recherche** en tête : loupe + placeholder **« Rechercher une équipe... »**.
2. **Onglets texte** sous la barre : **« Un joueur »**, **« Une équipe »** (actif — texte bleu + **soulignement** épais), **« Un terrain »**.
3. **Rangée filtres** :
   - Bouton circulaire **icône sliders** (ouvrir filtres complets).
   - Chip **« Sport: Football »** bleu clair avec **×** pour retirer.
   - Chip **« Rayon: 20 km »** avec **×**.
4. **Cartes équipe** (liste défilante) — chaque carte :
   - **Logo** circulaire à gauche.
   - **Nom** d’équipe en gras.
   - Ligne 1 : picto **sport** + nom sport + **ville** + **distance** (ex. Football • Lyon • 8 km).
   - Ligne 2 : icône **groupe** + **effectif / capacité** (ex. 14/20 membres).
   - **Bouton** pleine largeur bleu : **« Demander à rejoindre »**.
5. **Exemples** dans la maquette : Les Lions de Lyon, FC Villeurbanne, Tigers Basket, Red Star Futsal.
6. **Tab bar** (4) : Accueil, **Explorer** (**loupe bleue** — actif), Messages (**point rouge** notif), Profil.

---

## 4. `team_search_filters` — Filtres de recherche équipes (modale)

**Rôle du design** : **bottom sheet** pour affiner la recherche **équipes** — **sport**, **distance**, **type** (compétitif / loisir), puis appliquer ou réinitialiser.

1. **Poignée** grise en haut (sheet).
2. **Titre** : « **Filtres** » à gauche ; lien **« Réinitialiser »** à droite (gris / bleu).
3. **Section « Sport »** : grille de **pills** avec icônes :
   - **Football** sélectionné (fond bleu, texte blanc, ballon).
   - Basket, Tennis, Running, Volley, Handball — gris clair, non sélectionnés.
4. **Section « Distance »** : titre + valeur **« 25 km »** en bleu à droite ; **slider** 1 km → 100 km (curseur cercle blanc bord bleu).
5. **Section « Type d'équipe »** — deux grandes lignes sélectionnables :
   - **Compétitif** — trophée bleu sur cercle bleu clair ; **coche** bleue à droite (**sélectionné**).
   - **Loisir** — smiley vert sur cercle vert clair ; **radio vide** à droite.
6. **Bouton** : « **Appliquer les filtres** » — bleu plein, texte blanc gras.

---

## 5. `réglage_du_rayon_de_recherche` — Rayon de recherche (modale)

**Rôle du design** : ajuster uniquement la **distance maximale** de recherche (souvent ouverte depuis le chip « Rayon » ou les filtres).

1. **Fond** : photo floutée (contexte « près de chez vous »).
2. **Sheet blanc** coins supérieurs arrondis + **poignée**.
3. **Titre** : « **Rayon de recherche** » centré, gras ; **×** fermeture en haut à droite.
4. **Valeur** : grand affichage **« 25 km »** en bleu ; sous-texte « **Distance maximale** » en bleu-gris.
5. **Slider** horizontal **1 km** — **100 km** ; piste gauche du curseur en **bleu plein**, droite en **pointillés** gris ; **thumb** bleu avec point blanc et halo.
6. **Bouton** : « **Appliquer** » — bleu, texte blanc + **coche** blanche, ombre.

---

## 6. `invitation_des_contacts_répertoire` — Inviter des contacts

**Rôle du design** : fusionner **annuaire téléphonique** et **utilisateurs déjà sur O’Sport** — suivre, inviter par SMS, navigation **A–Z**.

1. **Retour** ; titre **« Inviter des contacts »**.
2. **Recherche** : « **Rechercher un contact...** » avec loupe.
3. **Bloc « Contacts sur O’Sport »** :
   - Titre gras + badge bleu **« 3 Nouveaux »**.
   - Lignes : avatar, nom, **@pseudo** ; bouton **« Suivre »** (bleu) ou **« Suivi »** (gris — déjà suivi).
4. **Bloc « Inviter sur O’Sport »** :
   - Contacts du répertoire **groupés par lettre** (**A**, **B**, **C**… en bleu).
   - Pastille **initiales** colorées, **nom**, numéro **masqué** (ex. 06 •• •• 45 12).
   - Bouton **« Inviter »** (contour bleu, texte bleu).
5. **Index alphabétique** : colonne **A–Z** à droite pour scroll rapide.
6. **Tab bar** (4) : Accueil, Activités (haltères), **Amis** (**deux personnes bleues** — actif), Profil.

---

## 7. `partage_et_scan_qr_code_profil` — Partager le profil & QR

**Rôle du design** : montrer un **QR / code** pour ajouter l’utilisateur, **partager** le profil ou **enregistrer** l’image — complète la découverte (souvent liée au raccourci QR de la recherche).

1. **Retour** (cercle) ; titre **« Partager Profil »** ; **⋯** menu à droite.
2. **Segmented** : **« Mon Code »** (actif — texte bleu sur pilule blanche) / **« Scanner »** (inactif gris) — bascule vers scan de l’autre côté.
3. **Carte centrale** blanche très arrondie, ombre :
   - Grande zone type **QR** (grille bleue) avec **badge central** bleu (icône **haltère** blanche — identité O’Sport).
   - **Avatar** + **Jean Dupont** + **@jean_osport**.
4. **Bouton principal** : « **Partager mon profil** » — bleu, icône **partage** blanche à gauche, halo.
5. **Bouton secondaire** : « **Enregistrer l'image** » — bord bleu, icône **téléchargement** bleue.
6. **Tab bar** (5) : Accueil, Explorer, **Partager** (icône QR **bleue** + halo — actif), Messages, Profil.

---

## Parcours logiques

| Besoin utilisateur | Écrans impliqués |
|-------------------|------------------|
| Trouver des sportifs à suivre | `search_-_find_friends_1` / `_2` → chips / filtres → `réglage_du_rayon_de_recherche` ou filtres avancés équipe si unifiés côté produit |
| Trouver une équipe | `search_-_find_teams` → `team_search_filters` → résultats → Demander à rejoindre |
| Inviter hors app | `invitation_des_contacts_répertoire` |
| Ajouter via QR | `search_-_find_friends_*` (raccourci) ou `partage_et_scan_qr_code_profil` |

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `search_-_find_friends_1` | Recherche amis, chips, raccourcis FB/Contacts/QR, 3 résultats |
| `search_-_find_friends_2` | Même frame + liste + état « Invité » |
| `search_-_find_teams` | Recherche équipes, onglets joueur/équipe/terrain, filtres, CTA rejoindre |
| `team_search_filters` | Sheet filtres sport, distance, type équipe |
| `réglage_du_rayon_de_recherche` | Sheet rayon 1–100 km, Appliquer |
| `invitation_des_contacts_répertoire` | Contacts app + répertoire, Suivre / Inviter, index A–Z |
| `partage_et_scan_qr_code_profil` | QR profil, partager, enregistrer, onglet Scanner |

---

## Notes pour l’implémentation

- **Cohérence libellés** : mélange **Radius** (anglais) et **Rayon** (français) selon les maquettes — à unifier en prod.
- **Troisième onglet** « **Un terrain »** sur `search_-_find_teams` : pas d’écran dédié dans ce dossier ; prévoir écran ou contenu à concevoir.
- `partage_et_scan_qr_code_profil` n’est pas une « barre de recherche » au sens strict mais complète le **moteur de découverte** ; conservé ici car dans le même dossier design.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de moteur de recherche**.*
