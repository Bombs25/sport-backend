---
name: osport-spec-ecran-notifications
description: "Spec maquettes O’Sport — notifications, dossier `ecran de notifications`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans de notifications — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de notifications` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

Le dossier contient **trois variantes** du **centre de notifications** (`notifications_center_1` à `_3`). Toutes partagent :

- Un **en-tête** « Notifications » avec action **« tout marquer comme lu »** (double coche bleue).
- Une liste en deux blocs chronologiques : **NEW** (non lus, fond bleu très pâle + **barre verticale bleue** à gauche) et **EARLIER** (lus, fond blanc).
- Une **fin de liste** (« No more notifications »).
- Une **barre de navigation basse** identique : Home, Teams, **FAB +** central bleu, **Alerts** (cloche bleue + **point rouge** — onglet actif), Profile.

Les variantes diffèrent surtout par les **types de notifications** et le **mélange français / anglais** dans les textes.

---

## 1. `notifications_center_1` — Centre de notifications (variante 1)

**Rôle du design** : afficher les **nouvelles** alertes actionnables (adhésion équipe, invitation match) puis l’**historique** récent (follow, like, stats, mention).

### En-tête

1. **Titre** : « **Notifications** » en gras, noir, à gauche.
2. **Icône** : **double coche** bleue en haut à droite — **tout marquer comme lu** (comportement attendu).

### Section « NEW »

3. **Libellé de section** : « **NEW** » en capitales, gris bleuté.
4. **Style des lignes** : fond **bleu très pâle** ; **barre verticale bleue épaisse** sur le bord gauche.

**Notification 1 — Demande d’adhésion**

5. **Avatar** circulaire (femme) + **pastille** bleue icône **groupe** (coin bas droit de l’avatar).
6. **Texte** : « **Sarah Jenkins** requested to join **Sunday Football FC** » + « **2m ago** » en gris.
7. **Actions** : **« Accept »** (bouton bleu plein, texte blanc) et **« Decline »** (blanc, bord gris, texte gris foncé).

**Notification 2 — Invitation match**

8. **Avatar** logo équipe (tigre) + **pastille orange** **ballon**.
9. **Texte** : « **FC Tigers** invited you to a match » + « **15m ago** ».
10. **Action** : bouton **« View Details »** à droite (blanc, bord gris).

### Section « EARLIER »

11. **Libellé** : « **EARLIER** ».
12. **Fond** des lignes : blanc (lus).

**Notification 3 — Nouveau follower**

13. Avatar homme + pastille **verte** icône **user+**.
14. Texte : « **Mike Ross** started following you » — « **1h ago** ».
15. Bouton **« Follow back »** (fond bleu très pâle, texte bleu).

**Notification 4 — Like sur publication**

16. Avatar + pastille **rouge** **cœur**.
17. Texte : « **Tom Brady** liked your post: 'First win of the season!' » — « **3h ago** ».
18. **Vignette** carrée à droite (aperçu visuel du post).

**Notification 5 — Système / stats**

19. Icône **engrenage** dans cercle bleu clair.
20. Texte : « Your team stats for last week are ready » — « **1d ago** ».
21. **Point** bleu clair à droite (indicateur secondaire / non ouvert).

**Notification 6 — Mention**

22. Avatar + pastille bleue **@**.
23. Texte : « **Jenny Wilson** mentioned you in a comment » — « **2d ago** ».

### Pied de liste

24. Texte centré gris : « **No more notifications** ».

### Tab bar

25. **Home**, **Teams**, **FAB +** bleu surélevé, **Alerts** (actif bleu + point rouge sur la cloche), **Profile** — libellés en **anglais**.

---

## 2. `notifications_center_2` — Centre de notifications (variante 2)

**Rôle du design** : même structure que la variante 1, avec **une notification de plus** dans « NEW » (mention avec extrait **en français**) et **trois** entrées dans « EARLIER » (liste légèrement raccourcie par rapport à `_1` sur le papier descriptif).

### En-tête

1. **Titre** « Notifications » + **double coche** bleue (marquer tout lu).

### Section « NEW » (3 éléments, barre bleue à gauche)

2. **Mention** (en premier) : avatar femme + badge **@** bleu.
3. Texte **bilingue** : « **Marc** vous a mentionné dans un commentaire : '@pseudo super match hier !' » + « **Just now** ».
4. **Miniature** circulaire à droite (photo d’équipe / célébration).

5. **Demande d’adhésion** (même logique que `_1`) : Sarah Jenkins / Sunday Football FC, **2m ago**, boutons **Accept** / **Decline**.

6. **Invitation match** FC Tigers, **15m ago**, **View Details**.

### Section « EARLIER » (3 éléments, sans barre bleue)

7. **Mike Ross** — started following you — **Follow back** — 1h ago.
8. **Tom Brady** — liked your post — vignette — 3h ago.
9. **Stats équipe** — engrenage — « Your team stats for last week are ready » — point gris — 1d ago.

### Pied + Tab bar

10. « No more notifications » ; tab bar identique (**Alerts** actif).

**Différence clé vs `_1`** : bloc **NEW** enrichi d’une **mention** avec texte **français** et aperçu ; section **EARLIER** ne reprend pas l’entrée « mention Jenny Wilson » de la variante 1 (liste différente pour la maquette).

---

## 3. `notifications_center_3` — Centre de notifications (variante 3)

**Rôle du design** : montrer d’**autres types** d’événements : **proposition de score**, **trophée**, **message de groupe**, **effectif**, **litige score**, **lieu équipe**, **photo de profil**, **rappel match**.

### En-tête

1. « Notifications » + double coche bleue.

### Section « NEW »

2. **Proposition de score final** : logo équipe + badge **drapeau** bleu ; texte du type « Team A proposed a final score of **2-1** for your match. » — **JUST NOW** (caps grises).
3. Actions : **« Validate »** (bleu) et **« Dispute »** (blanc bord gris).

4. **Mention** : avatar + @ ; texte français sur le commentaire de Marc ; miniature à droite — **2m ago**.

5. **Succès / trophée** : cercle **jaune** avec **trophée** blanc (pas d’avatar classique).
6. Texte : « **Congratulations!** Your team **FC Hornets** just won the Summer League Trophy! » — **15m ago**.

7. **Message** (contexte match) : avatar + pastille **verte** **bulle**.
8. Texte : « **Sarah Jenkins** sent a message in *Match Request* 'Are we playing at the central stadium?' » — **22m ago**.

### Section « EARLIER »

9. **Effectif** : icône personne **moins** — « **Mike Ross** was removed from the roster of **Tigers FC** » — 2h ago.

10. **Litige score** : cercle rouge pâle + **triangle alerte** rouge — « **Score Dispute:** Team B disputed the 2-1 result of yesterday's match. » — 4h ago.

11. **Mise à jour lieu** : logo équipe + badge **engrenage** gris — « **FC Tigers** updated their team location to *Downtown Arena* » — 6h ago.

12. **Photo de profil** : avatar homme + badge **violet** type refresh — « **Tom Brady** updated his profile photo. » — **Yesterday**.

13. **Rappel match** : cercle bleu clair + **cloche** bleue — « **Match Reminder:** Your game against Lions FC starts in 2 hours. » — 1d ago.

### Pied + Tab bar

14. « No more notifications » ; même tab bar (**Alerts** actif).

---

## Comparaison des trois variantes

| Aspect | `_1` | `_2` | `_3` |
|--------|------|------|------|
| **NEW — 1re notif** | Demande adhésion Sarah | Mention Marc (FR) + miniature | Proposition score Validate / Dispute |
| **NEW — suite** | Invitation FC Tigers | Puis adhésion, puis invite | Mention, trophée, message Match Request |
| **EARLIER** | Follow, like + thumb, stats, mention Jenny | Follow, like, stats (3 lignes) | Roster, litige, lieu, profil, rappel (5 lignes) |
| **Langue** | Surtout **anglais** | **Mix** FR / EN | Surtout **anglais** + quelques FR |

Les trois maquettes servent de **bibliothèque de composants** pour le feed notifications (badges sur avatar, CTA inline, états lu / non lu).

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `notifications_center_1` | NEW : adhésion + invite match ; EARLIER : follow, like, stats, mention |
| `notifications_center_2` | NEW : mention FR + adhésion + invite ; EARLIER : follow, like, stats |
| `notifications_center_3` | NEW : score à valider, mention, trophée, message ; EARLIER : effectif, litige, lieu, profil, rappel |

---

## Notes pour l’implémentation

- **Modèle de données** : chaque ligne = type (enum), acteurs, texte riche, **payload** (miniature, deep link), **actions** (0 à 2 boutons), `read_at`, `created_at`.
- **Marquer tout lu** : invalider la barre bleue et le fond des sections NEW.
- **i18n** : harmoniser **français / anglais** (les maquettes mélangent les deux).
- Aucun écran **réglages de notifications** dans ce dossier — voir `ecran de gestion de parametres` si besoin.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de notifications**.*
