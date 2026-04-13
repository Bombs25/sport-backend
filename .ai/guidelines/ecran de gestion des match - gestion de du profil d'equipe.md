---
name: osport-spec-ecran-matches-team-profile
description: "Spec maquettes O’Sport — matchs et profil équipe, dossier `ecran de gestion des match - gestion de du profil d'equipe`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans — gestion des matchs & profil d’équipe — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de gestion des match - gestion de du profil d'equipe` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

- **Ligue / classement** des équipes (filtres sport, saison, tableau des rangs).
- **Profil d’équipe** (aperçu, statistiques, palmarès, membres, adhésions).
- **Demandes de match** (tableaux de bord FR/EN, envoi de demande, suivi reçu/envoyé).
- **Défis** (liste, filtres, états : en attente, accepté, refusé, score à confirmer).
- **Après match** : saisie **score + évaluation**, **confirmation** du score proposé, **litige** / contestation.
- **Export** du classement avec **partage** (réseaux, lien).

Certaines captures portent un **nom de dossier** qui ne décrit pas à l’identique l’écran (voir remarques dans les sections concernées).

---

## 1. `classement_général_de_la_ligue` — Classement équipes

**Rôle du design** : consulter le **classement** d’une ligue par sport, année et saison ; repérer **son équipe** ; rechercher / partager.

1. **Retour** : chevron gauche.
2. **Titre** : « Classement Équipes » centré, gras.
3. **Actions droite** : **loupe** (recherche), **icône partage** (carré + flèche).
4. **Filtres sport** : pastilles horizontales — **Football** actif (bleu), puis Basketball, Tennis, Rugby (gris).
5. **Menus** : deux sélecteurs **Année** (ex. 2024) et **Saison** (ex. Printemps) avec chevron bas.
6. **En-tête de tableau** (bandeau gris clair) : colonnes **RANG**, **ÉQUIPE**, **V**, **N**, **D**, **PTS** (PTS en bleu).
7. **Lignes du classement** :
   - Rangs **1 à 3** : numéro dans **cercle or / argent / bronze**.
   - **Rang 4 « Votre équipe »** : ligne **surbrillance bleue** (fond bleu clair, barre verticale bleue gauche, textes bleus), logo, sous-libellé **« O'SPORT FC »**, stats et points (ex. 39 pts).
   - Autres équipes : style neutre (noir sur blanc).
8. **Tab bar** (5 onglets) : Accueil, Communauté, **Ligue** (graphique **bleu** — actif), Alertes, Profil.

---

## 2. `team_details_page_3` — Profil équipe (onglet Aperçu)

**Rôle du design** : **page publique / détail** d’une équipe — identité, stats de saison courtes, dernier match, CTA **demander un match** / **rejoindre**.

1. **Photo de couverture** : visuel d’action en haut pleine largeur.
2. **Retour** : flèche haut gauche ; **menu ⋯** haut droite.
3. **Logo** : grand cercle chevauchant couverture / contenu (blason lion).
4. **Nom** : « Lyon Lions FC » en gras.
5. **Badge** : pilule bleue « **Competitive** » (orthographe anglaise dans la maquette) avec coche.
6. **Barre d’infos** (icônes + texte capitales) : **SOCCER** ; **LYON, FR** ; **24 MEMBERS**.
7. **CTA doubles** : bouton bleu **demande de match** (Request Match) ; bouton secondaire bordure **Join** (rejoindre l’équipe).
8. **Onglets** : **Aperçu** (actif), Statistiques, Palmarès.
9. **Bloc « Season Stats »** — 4 cartes : Played (12), Won (8, bleu), Lost (2), Draw (2).
10. **Bloc « Latest Match »** : cartes Lyon Lions vs Paris FC, score **3 - 1**, badge vert **WIN**.

---

## 3. `team_details_page_1` — Profil équipe (onglet Statistiques)

**Rôle du design** : **indicateurs** de performance filtrables + **courbe** de tendance + **aperçu trophées**.

1. **Header** : retour circulaire ; **logo + « Lyon Lions FC »** centré ; **⋯** menu.
2. **Onglets** : Présentation, **Statistiques** (actif bleu), Matchs.
3. **Filtres** : listes **Année** (ex. 2026) et **Saison** (ex. HIVER).
4. **Carte métriques** : Rang (1ère place + médaille or), Nb ex æquo (0), Points cumulés (32 pts bleu), Fair-play (**A+** vert).
5. **« Performance Saisonnière »** : graphique **ligne** Match 1–5, remplissage dégradé bleu, légende « TENDANCE DE POINTS ».
6. **Bouton** dans la carte graphique : « Voir plus de stats » + petite icône tendance.
7. **« Trophées »** : carrousel horizontal — ex. **Champion Régional** Saison 2023 ; carte suivante partielle (médaille / Vainqueur…).

---

## 4. `team_details_page_2` — Profil équipe (onglet Palmarès)

**Rôle du design** : **palmarès**, **performance** graphique, **historique des matchs** filtrable par saison.

1. **Header** : retour ; logo + nom ; **icône partage** à droite.
2. **Onglets** : Infos, Stats, **Palmarès** (actif), Membres.
3. **Trophées** : titre + lien **« VOIR TOUT »** bleu ; cartes horizontales (Champion Régional, Winter Cup… avec saison / division).
4. **Performance Saisonnière** : même logique que page 1 (graphique + « Voir plus de stats »).
5. **Historique des Matchs** : titre + filtre **« SAISON 2023 »** (chevron).
6. **Liste de matchs** : chaque ligne — date/heure, **logos + noms** vs, **score** bleu gras, badge issue **VICTOIRE** (vert) / **NUL** (gris) / **DÉFAITE** (rouge pâle).

---

## 5. `team_details_page_4` — Profil équipe (onglet Membres)

**Rôle du design** : lister les **membres**, **rechercher**, **contacter**, **ajouter** un membre.

1. **Header** : retour ; logo + « Lyon Lions FC » ; **icône personne +** (inviter / ajouter).
2. **Onglets** : Infos, Stats, Palmarès, **Membres** (actif).
3. **Titre** : « Membres » + badge **« 24 membres »** bleu.
4. **Recherche** : champ « Rechercher un membre... » avec loupe.
5. **Cartes membre** : avatar, **nom** gras, **badge rôle** (CAPITAINE jaune ; ATTAQUANT, GARDIEN, etc. bleu/gris), bouton **Message** (bulle) à droite.
6. **Tab bar** : Accueil, **Équipe** (actif bleu), **FAB +** central bleu, Events (calendrier), Profil.

---

## 6. `gestion_des_adhésions_équipe` — Demandes d’adhésion

**Rôle du design** : traiter les **candidatures** de joueurs voulant rejoindre l’équipe.

1. **Retour** + titre **« Demandes d'adhésion »**.
2. **Résumé** : « En attente » + chiffre **3** en grand ; bouton **« Filtrer »** (entonnoir) à droite.
3. **Cartes candidat** (liste) :
   - **Avatar** + **pastille sport** sur le coin de l’avatar.
   - **Nom** gras, ligne sport • poste (ex. Football • Milieu).
   - Lien **« Voir le profil »** bleu.
   - **Badge club** en haut à droite (ex. O'Sport FC, couleurs variables).
   - **Message** du joueur dans encadré gris (bulle / zone texte).
   - **Accepter** (vert plein) et **Refuser** (gris, texte rouge).
4. **Tab bar** : Accueil, **Équipes** (actif), FAB +, Messages (point rouge notif), Profil.

---

## 7. `match_requests_dashboard` — Match Requests (anglais)

**Rôle du design** : liste des **demandes reçues** avec statut type **NEW / PENDING / EXPIRING**, accepter / refuser, **contacter** l’équipe.

1. **Retour** ; titre **« Match Requests »** ; icône **filtre** haut droite.
2. **Onglets** : **Reçu** (actif) / **Envoyé**.
3. **Cartes** : logo, nom équipe, ligne **Sport • format** (ex. Football • 5v5) ; badge statut ; date + lieu avec icônes ; **Refuser** (fond rose) / **Accepter** (vert) ; lien **« Contacter l'équipe »** avec icône chat.

---

## 8. `request_a_match_modal` — Demander un match (modale)

**Rôle du design** : **bottom sheet** pour proposer un match à une autre équipe (formulaire puis envoi).

1. **Poignée** grise (sheet).
2. **Titre** : « **Demander un match** » centré, gras.
3. **Champ équipe** : pastille « vs », placeholder « Rechercher une équipe... », **loupe** à droite.
4. **Date** — placeholder « Date », **icône calendrier**.
5. **Heure** — « Heure », **icône horloge**.
6. **Lieu** — « Lieu / Stade », **épingle**.
7. **Message** multiligne — optionnel (ex. ramener le ballon).
8. **Bouton** : « **Envoyer la demande** » bleu + **avion** blanc.

---

## 9. `gestion_des_défis_reçus_2` — Défis (reçues, liste active)

**Rôle du design** : voir les **défis entrants** à traiter (accepter, refuser, négocier).

1. **Titre** : « Défis » ; bouton **filtre** circulaire haut droite.
2. **Segmented** : **Reçues** (actif, badge **3**) / **Envoyées**.
3. **Cartes défi** : logo équipe + pastille **sport** ; **nom** ; badge type **Amical** / **Tournoi** / **Ligue** ; date • heure ; lieu ; boutons **Refuser** (bord rouge), **Négocier** (bleu clair), **Accepter** (vert).
4. **Tab bar** : Accueil, Recherche, **Défis** (trophée + point bleu — actif), Profil.

---

## 10. `gestion_des_défis_reçus_5` — Défis (reçues, acceptés)

**Rôle du design** : filtrer sur les **demandes acceptées** et passer à l’organisation ou au détail.

1. **Titre** « Défis » ; filtre sliders.
2. **Onglets** : Reçues (actif) / Envoyées.
3. **Chip** : **« Demandes acceptées »** avec coche (sous-filtre).
4. **Cartes** : logos **superposés** ; titre « Match vs … » ; **sport** coloré ; badge vert **« ACCEPTÉ »** ; date/heure ; lieu ; CTA bleu **« S'organiser »** (première carte) ou **« Voir les détails »**.
5. **Tab bar** : Défis actif.

---

## 11. `gestion_des_défis_reçus_3` — Défis (reçues, refusées)

**Rôle du design** : historique des **défis refusés** + retirer de la liste.

1. **Retour** circulaire ; titre « Défis » ; **filtre**.
2. **Chip filtre actif** : « Filtre : **Demandes refusées** » (partie « refusées » en rouge).
3. **Onglets** : Reçues (actif) / Envoyées.
4. **Cartes** : logo + sport ; nom ; date/lieu ; badge **« REFUSÉ »** rouge ; **poubelle** (supprimer l’entrée).

---

## 12. `gestion_des_défis_reçus_4` — Mes défis (scores à confirmer)

**Rôle du design** : liste des matchs **en attente de validation du score** (action requise).

1. **Retour** ; titre **« Mes Défis »** ; bouton **filtre** avec **point bleu** (notif filtres).
2. **Chip** bleu **« En attente de score »** avec **×** pour retirer le filtre.
3. **Cartes** : ligne sport • date ; badge jaune **« ! ACTION REQUISE »** ; deux équipes + **score proposé** au centre ; boutons **« Confirmer le score »** (vert) et **« Contester »** (contour rouge).
4. **Tab bar** : Défis actif (trophée + point).

---

## 13. `gestion_des_défis_reçus_1` — Confirmer le score (détail)

**Remarque** : le dossier s’appelle `gestion_des_défis_reçus_1` mais l’écran est **« Confirmer le score »** (validation du résultat proposé par l’adversaire).

**Rôle du design** : **lire** le score proposé, les **notes** reçues sur son équipe, puis **confirmer** ou **contester**.

1. **Retour** ; titre **« Confirmer le score »**.
2. **Carte match** : sport • date ; logos + noms (ex. FC Lightning vs The Dunkers) ; score **2 - 1** ; badge **« PROPOSÉ »**.
3. **« Évaluation de votre équipe »** : étoiles Fair-play (4/5) et Ponctualité (5/5) ; **citation** de l’adversaire dans encadré gris.
4. **Note informative** (icône i) : la confirmation **fige** le résultat dans les stats des **deux** équipes et **clôture** le défi.
5. **Boutons** : **« Confirmer le score »** (vert) ; **« Contester le score »** (bord rouge, texte rouge).
6. **Tab bar** : **Défis** actif.

---

## 14. `modal_de_filtres_des_défis` — Filtrer les défis (modale)

**Rôle du design** : **bottom sheet** pour filtrer défis par **statut**, **sport**, **période**.

1. **Poignée** ; titre **« Filtrer les défis »** ; lien **« Réinitialiser »** bleu à droite.
2. **STATUT** — chips : **En attente** (sélectionné bleu), Acceptés, Refusés, Scores à confirmer.
3. **SPORT** — choix circulaires : **Football** sélectionné (bord bleu), Tennis, Basket, Volley.
4. **PÉRIODE** — liste **« Tout »** avec chevrons.
5. **Bouton** : « **Appliquer les filtres** » bleu.

*(Arrière-plan : aperçu « Mes Défis » avec badge EN ATTENTE.)*

---

## 15. `envoi_du_score_et_évaluation` — Score & évaluation (saisie)

**Rôle du design** : après un match **terminé**, saisir / confirmer le **score** et **noter** l’adversaire (fair-play, ponctualité) + commentaire optionnel.

1. **Thème sombre** (fond bleu nuit / anthracite).
2. **Retour** ; titre **« Score & Évaluation »** ; badge vert **« MATCH TERMINÉ »**.
3. **Carte résultat** : logos des deux équipes, **vs**, grands chiffres de score (ex. 2 - 1).
4. **« Évaluation de l’adversaire »** : deux blocs avec **étoiles** (Fair-play, Ponctualité).
5. **« Remarques additionnelles (optionnel) »** — zone texte « Comment s’est déroulé le match ? ».
6. **Bouton** : « **Envoyer le score et l’évaluation** » bleu + icône envoi.
7. **Tab bar** claire en bas : **Matchs** (ballon — actif), Accueil, Équipes, Profil.

---

## 16. `formulaire_de_litige_match` — Contester le résultat

**Rôle du design** : **signaler** un litige sur un match (motif, texte, preuve photo) avec avertissement modération.

1. **Retour** ; titre **« Contester le résultat »**.
2. **Tab bar** (5) : centre **ballon** actif.
3. **Carte « Résumé du Match »** : Dragons FC vs Phoenix SC, **score final** rouge **2 - 1**.
4. **« Motif du litige »** — cases à cocher : Score incorrect ; Mauvais fair-play déclaré ; Comportement adverse inapproprié.
5. **« Détails de la contestation »** — grande zone texte (explication pour modérateurs).
6. **« Ajouter une preuve »** — zone pointillée, **caméra +** , texte « Télécharger une image », précision **photo du score ou feuille de match**.
7. **Encart alerte** rouge pâle : traitement sous **24 h** ; **véracité** des infos sous peine de sanctions.
8. **Bouton** : « **Envoyer le litige** » **rouge** plein.

---

## 17. `sent_match_requests` — Gestion des matchs (envoyées)

**Rôle du design** : suivre les **demandes envoyées** (en attente / accepté), modifier, annuler ou voir le détail.

1. **Retour** ; titre **« Gestion des Matchs »**.
2. **Onglets** : Reçu / **Envoyé** (actif).
3. **Cartes** : logo, nom adversaire, badge **En attente** (orange) ou **Accepté** (bleu) ; date, heure, lieu avec icônes.
4. **Si en attente** : **Modifier** | **Annuler** (rouge).
5. **Si accepté** : lien **« Voir les détails »** bleu avec flèche.
6. **FAB** bleu **+** en bas à droite (nouvelle demande).
7. **Tab bar** : **Matchs** (ballon — actif).

---

## 18. `suivi_des_défis_envoyés` — Mes défis (envoyés)

**Rôle du design** : variante **« Mes Défis »** onglet **Envoyés** — suivi **En attente** / **Confirmé** avec actions différentes.

1. **Retour** ; titre **« Mes Défis »**.
2. **Onglets** : Reçus / **Envoyés** (actif bleu).
3. **Cartes** : badge **En attente** (orange + horloge) ou **Confirmé** (bleu + coche) ; nom adversaire ; date/heure ; lieu ; **visuel logo** carré à droite.
4. **En attente** : **Modifier** (gris) + **Annuler** (bord rouge).
5. **Confirmé** : bouton large bleu clair **« Voir le match »** + flèche.
6. **Tab bar** : **Défis** actif.

---

## 19. `succès_de_l'exportation_et_partage` — Export classement réussi

**Rôle du design** : confirmer l’**export** d’un classement (ex. saison hiver) et proposer **partage** ou fermeture.

1. **Overlay** gris sur l’écran sous-jacent.
2. **Modale** blanche coins supérieurs arrondis, **poignée**.
3. **Icône** : cercle **vert** + coche blanche, halo vert clair.
4. **Titre** : « **Exportation réussie !** ».
5. **Texte** : « Votre classement pour la Saison Hiver 2026 est prêt. »
6. **Partage** — trois actions circulaires : **Instagram**, **WhatsApp**, **Copier le lien** (icône chaîne, cercle bleu — mis en avant).
7. **Bouton** secondaire : « **Fermer** » gris clair, texte noir gras.
8. **Status bar** + **home indicator** visibles dans la maquette.

---

## Synthèse des incohérences de nommage

| Dossier | Contenu réel de la maquette |
|--------|---------------------------|
| `gestion_des_défis_reçus_1` | Écran **Confirmer le score** (détail), pas une liste « reçus » |
| `gestion_des_défis_reçus_2` à `_5` | Variantes de liste **Défis** / **Mes défis** (états et filtres différents) |
| `team_details_page_1` à `_4` | Onglets différents du **profil équipe** (Stats, Palmarès, Aperçu, Membres) — l’ordre numérique ≠ ordre d’onglets |

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `classement_général_de_la_ligue` | Tableau de classement, filtres, mise en avant « votre équipe » |
| `team_details_page_3` | Aperçu équipe, CTA match / rejoindre, mini-stats |
| `team_details_page_1` | Statistiques, graphique, trophées |
| `team_details_page_2` | Palmarès, perf., historique matchs |
| `team_details_page_4` | Membres, recherche, message |
| `gestion_des_adhésions_équipe` | Demandes d’adhésion joueurs |
| `match_requests_dashboard` | Demandes match (UI anglaise) |
| `request_a_match_modal` | Formulaire demande de match |
| `gestion_des_défis_reçus_2` | Liste défis reçus à répondre |
| `gestion_des_défis_reçus_5` | Défis acceptés |
| `gestion_des_défis_reçus_3` | Défis refusés |
| `gestion_des_défis_reçus_4` | Mes défis — scores à confirmer |
| `gestion_des_défis_reçus_1` | Détail confirmation de score |
| `modal_de_filtres_des_défis` | Filtres statut / sport / période |
| `envoi_du_score_et_évaluation` | Saisie score + évaluation adversaire |
| `formulaire_de_litige_match` | Contestation résultat + preuve |
| `sent_match_requests` | Demandes envoyées (gestion matchs) |
| `suivi_des_défis_envoyés` | Défis envoyés (Mes défis) |
| `succès_de_l'exportation_et_partage` | Succès export + partage |

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de gestion des match -  gestion de du profil d'equipe**.*
