---
name: osport-spec-ecran-feed-posts
description: "Spec maquettes O’Sport — fil et posts, dossier `ecran des posts`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans des posts — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran des posts` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

- Le **fil d’actualité** en mode **posts sociaux** (cartes auteur, texte, médias, likes / commentaires / partage).
- Le **fil « Matchs »** (résultats, match en direct, événements à venir).
- La **vision plein écran** d’une **photo** du carrousel (télécharger, partager).
- La **fiche publication** type détail (style flux social).
- L’écran **commentaires** (liste, fils, saisie, mentions / réponse).
- La **modale de suppression** d’un post.
- La **liste des personnes** qui ont aimé (**Aimé par**).

**Remarque importante** : le dossier `home_feed_-_social_posts_2` ne montre **pas** un autre fil « posts » mais une **vue média plein écran** (carrousel). Le nom du dossier est **trompeur** pour le développeur.

---

## 1. `home_feed_-_social_posts_1` — Fil d’actu : posts sociaux (variante 1)

**Rôle du design** : afficher le **feed social** avec onglet **Posts** actif.

1. **Header** : à gauche, **logo** bleu (ballon) + marque **« O'Sport »** en gras ; à droite icônes **groupe**, **trophée**, **cloche** (point rouge notif), **avatar** utilisateur.
2. **Segmented control** : **« Posts »** sélectionné (fond bleu, texte blanc) ; **« Matchs »** inactif (gris).
3. **Cartes post** (fond blanc, coins arrondis, ombre) — structure type :
   - **En-tête** : avatar ; **nom** gras ; ligne **temps + lieu** en bleu/gris (ex. 2h ago • London) ; **⋯** menu.
   - **Texte** du post avec **emojis** et **hashtags** cliquables en bleu (ex. #SundayLeague).
   - **Média** : grande image arrondie (foot, tennis, etc.).
   - **Pied** : **cœur** (rouge si liké) + nombre ; **bulle** + nombre de commentaires ; **icône partage**.
4. **Exemples** dans la maquette : Alex Striker (foot, 24 likes / 5 commentaires) ; Sarah Tennis (112 likes, cœur gris).
5. **FAB** : bouton **+** circulaire bleu en bas à droite (création de post).
6. **Tab bar** (4, libellés **anglais**) : **Home** (actif bleu), Search, Events, Inbox.

---

## 2. `home_feed_-_social_posts_3` — Fil d’actu : posts sociaux (variante 3)

**Rôle du design** : **même type d’écran** que `_1` avec **quatrième icône** dans le header et **indicateurs de carrousel** sur les médias.

1. **Header** : logo O'Sport + à droite **communauté**, **trophée**, **cloche** (point rouge), **profil** — **4** actions (vs trois décrites sur `_1` selon la maquette).
2. **Onglets** : **Posts** actif / **Matchs** inactif.
3. **Cartes** : même logique (Alex Striker, Sarah Tennis…) avec **badge « 1/3 »** ou **« 1/2 »** en haut à droite de l’image et **points** sous l’image pour le **carrousel** photos.
4. **FAB +** et tab bar **identiques** à `_1` (Home, Search, Events, Inbox).

**Différence clé vs `_1`** : **carrousel multi-photos** visible sur les cartes ; header avec **icône supplémentaire**.

---

## 3. `home_feed_-_social_posts_2` — Visionneuse média plein écran (⚠️ nom de dossier trompeur)

**Contenu réel** : **lightbox** / **plein écran** pour une image du post (pas un scroll de fil).

1. **Fond** : **noir** plein écran.
2. **Barre du haut** : **×** fermer à gauche ; à droite **télécharger** et **partager** (icônes blanches).
3. **Image** : photo principale centrée (ex. footballeur en action).
4. **Bas** : **pagination par points** (3 points, le milieu actif) ; légende **« SUNDAY LEAGUE • FINAL »** en blanc capitales ; compteur **« 2 of 3 »** (image courante du carrousel).
5. **Usage** : ouverture depuis un tap sur une photo du feed (`_1` / `_3`).

---

## 4. `home_feed_-_match_results` — Fil d’actu : onglet Matchs

**Rôle du design** : fil centré sur les **publications liées aux matchs** (résultat, live, événement à venir).

1. **Header** : **avatar** utilisateur (point vert en ligne) ; titre **« O'Sport »** bleu centré ; **cloche** avec badge rouge.
2. **Segmented** : **« Fil d'actu »** / **« Matchs »** — **Matchs** actif (pilule blanche).
3. **Carte « résultat publié »** (FC Rockets — « A publié un résultat ») :
   - Badge vert **« TERMINÉ »** + trophée.
   - **Deux logos**, score **3 - 1** au centre, noms sous les logos.
   - Barre grise : **Hier**, lieu **Stade Municipal, Lyon** (icônes).
   - Pied : cœur (24), commentaires (5), partage.
4. **Carte « match en direct »** (Thunder 5 — « Match en direct ») :
   - Badge bleu **« EN COURS • 45’ »** avec point live.
   - Score **1 - 1** en bleu ; **Mi-temps** + lieu.
   - Pied : cœur **rempli rouge** (108 likes), 32 commentaires, partage.
5. **Carte « événement à venir »** (Red Stars — « A créé un évènement ») :
   - Badge gris **« À VENIR »** + calendrier.
   - Affichage **vs** entre équipes (pas encore de score).
   - **Demain, 14:00** + **Parc des Princes**.
   - Pied : likes / commentaires + bouton bleu **« Rejoindre »**.
6. **FAB +** bleu.
7. **Tab bar** (4, **français**) : **Accueil** (actif), Équipes, Calendrier, Profil.

---

## 5. `détails_de_publication_unique` — Détail d’une publication

**Rôle du design** : vue **pleine page** d’un post (type Instagram) avec actions like / commentaire / partage / enregistrement.

1. **Barre haut** : retour **<** ; titre **« PUBLICATION »** gris capitales ; **⋮** menu.
2. **Auteur** : avatar ; **John Doe** ; sous-texte **Paris, France** gris.
3. **Média** : grande photo verticale arrondie (ex. running sur piste).
4. **Barre d’actions** sous l’image : **cœur**, **bulle commentaire**, **avion** (partage / DM) à gauche ; **signet** (sauvegarder) à droite — icônes contour noir.
5. **Bloc engagement** : **« 124 J’aime »** gras ; légende **John Doe** + texte + emojis ; **hashtags** bleus ; horodatage **« IL Y A 2 HEURES »** gris capitales.
6. **Titre de section** : **« Commentaires »** (début de zone scrollable sous le pli).
7. **Tab bar** (5) : Home, Search, **FAB +** bleu central, cœur (activité), Profil.

---

## 6. `commentaires_de_publication_1` — Commentaires (vue standard)

**Rôle du design** : lire et écrire les **commentaires** d’un post, avec **fil** et **likes** par commentaire.

1. **Retour** ; titre **« Commentaires (24) »**.
2. **Liste** : chaque commentaire — avatar ; **nom** gras + **durée** (2h, 5h…) gris ; **cœur** + **nombre** à droite (cœur **bleu rempli** si liké par l’utilisateur courant).
3. **Texte** du commentaire ; lien **« Répondre »** bleu-gris.
4. **Réponse imbriquée** (ex. **Lucas** sous **Marie**) : **indentation** + **ligne verticale** / coude gris reliant au parent.
5. **Barre fixe bas** : petit avatar auteur courant ; champ pilule **« Ajouter un commentaire... »** ; bouton rond **bleu** **avion** envoi.

---

## 7. `commentaires_de_publication_2` — Commentaires (réponse + raccourcis)

**Rôle du design** : même liste que `_1` avec **bandeau de mentions rapides** et **mode réponse** explicite à un utilisateur.

1. **Header** identique — **Commentaires (24)**.
2. **Liste** identique (Thomas, Marie + Lucas, Sophie_run…) avec **@Sophie_run** en bleu dans un commentaire.
3. **Au-dessus du champ** : **scroll horizontal de chips** — avatar + prénoms (**Thomas**, **Marie**, **Sophie_run**…) pour **mention / réponse** rapide.
4. **Barre de contexte** : **« En réponse à Lucas »** avec **×** pour annuler la cible de réponse.
5. **Champ** avec bord **bleu clair**, placeholder commençant par **@** ; envoi **avion** bleu.

---

## 8. `commentaires_de_publication_3` — Commentaires (placeholder réponse)

**Rôle du design** : variante proche de `_2` avec **texte de placeholder** différent pour la saisie en mode réponse.

1. **Header** + **séparateur** fin sous le titre.
2. **Liste** (Thomas, Marie, réponse Lucas, Sophie_run) — même structure cœurs / Répondre / fil.
3. **Barre** « **En réponse à Lucas** » (flèche retour + nom en gras bleu) + **×**.
4. **Champ** : placeholder **« Votre réponse... »** au lieu de `@` seul ; bord bleu ; **avion** envoi.

---

## 9. `dialogue_de_confirmation` — Supprimer le post ?

**Rôle du design** : **confirmer** une **suppression définitive** de publication (souvent depuis ⋮ sur un post).

1. **Overlay** gris foncé sur l’écran derrière.
2. **Modale** blanche centrée, coins arrondis.
3. **Icône** : cercle rose pâle + disque rouge avec **poubelle** blanche (symbole suppression).
4. **Titre** : **« Supprimer le post ? »** en **rouge** gras, centré.
5. **Texte** : « Êtes-vous sûr de vouloir supprimer ce contenu ? Cette action est irréversible. » gris bleuté, centré.
6. **Boutons** côte à côte : **« Annuler »** (fond gris très clair, texte gris-bleu) ; **« Supprimer »** (fond **rouge**, texte blanc, ombre).

---

## 10. `liste_des_likes_du_post` — Aimé par

**Rôle du design** : **bottom sheet** listant les **utilisateurs qui ont liké** le post, avec **Suivre** / **Suivi**.

1. **Overlay** semi-transparent sur le contenu sous-jacent.
2. **Sheet** blanc, **coins supérieurs arrondis**, **poignée** grise.
3. **Titre** : **« Aimé par »** centré gras ; **×** fermeture à droite.
4. **Lignes** : avatar ; **nom** gras ; sous-ligne **Sport • Ville** gris ; bouton pilule à droite :
   - **« Suivre »** bleu plein (blanc), ou
   - **« Suivi »** gris clair (texte noir).
5. **Exemples** : Thomas Dupont, Sarah Martin, Lucas Bernard, Julie Petit, Maxime Durant, Elodie Roux (sports et villes variés).

---

## Parcours logiques

| Action utilisateur | Écran cible |
|-------------------|-------------|
| Scroll feed social / bascule Matchs | `home_feed_-_social_posts_1`, `_3` / `home_feed_-_match_results` |
| Tap sur photo carrousel | `home_feed_-_social_posts_2` |
| Tap sur post → détail | `détails_de_publication_unique` |
| Tap commentaires | `commentaires_de_publication_1` → `_2` / `_3` selon état (réponse) |
| Tap nombre de likes | `liste_des_likes_du_post` |
| Menu ⋮ → Supprimer | `dialogue_de_confirmation` |

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `home_feed_-_social_posts_1` | Feed Posts, cartes sans indicateur carrousel explicite (maquette 1) |
| `home_feed_-_social_posts_3` | Feed Posts + badges 1/N + points carrousel |
| `home_feed_-_social_posts_2` | **Visionneuse** image plein écran (pas un feed) |
| `home_feed_-_match_results` | Feed onglet Matchs — terminé, en cours, à venir |
| `détails_de_publication_unique` | Détail post + début zone commentaires |
| `commentaires_de_publication_1` | Liste + fil + saisie simple |
| `commentaires_de_publication_2` | + chips participants + barre « en réponse à » |
| `commentaires_de_publication_3` | + placeholder « Votre réponse... » |
| `dialogue_de_confirmation` | Modale suppression post |
| `liste_des_likes_du_post` | Sheet « Aimé par » + Suivre |

---

## Notes pour l’implémentation

- **Cohérence i18n** : mélange **français** (Matchs, Rejoindre, commentaires) et **anglais** (Posts, Home, 2h ago) selon les maquettes — à unifier en prod.
- **Deux tab bars** différentes (4 items FR vs 4 EN) entre fil matchs et fil social — probablement **variantes produit** ; à trancher côté app unique.
- Réutiliser **un composant carte post** avec props : type `social` | `matchResult` | `live` | `upcoming`, médias, counts, CTA.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran des posts**.*
