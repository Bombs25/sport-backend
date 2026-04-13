---
name: osport-spec-ecran-settings
description: "Spec maquettes O’Sport — paramètres, dossier `ecran de gestion de parametres`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans de gestion de paramètres — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de gestion de parametres` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

- L’**écran principal Paramètres** (compte, préférences, aide, déconnexion, version).
- **Deux niveaux de notifications** : un écran « types d’activité + canaux », et un écran **granulaire** par domaines (social, équipes, matchs, messagerie).
- La **confidentialité** (visibilité, interactions, sécurité, lien vers comptes bloqués).
- La **liste des comptes bloqués** avec action débloquer.
- Le **centre d’aide et support** (recherche, catégories, FAQ, contact).

Style récurrent : fond blanc ou gris très clair, **cartes blanches** groupées, **bleu** pour les icônes et l’état actif, listes avec chevrons ou toggles.

---

## 1. `paramètres_et_réglages` — Paramètres (hub)

**Rôle du design** : **point central** des réglages — navigation vers compte / sécurité / confidentialité, **préférences** (notifications, thème, rayon), aide légale, **déconnexion**, version de l’app.

1. **Retour** : chevron gauche en haut à gauche.
2. **Titre** : « Paramètres » centré, gras, noir.
3. **Section « COMPTE »** (carte blanche arrondie) — trois lignes **navigation** (icône bleue, texte, chevron `>`) :
   - **Informations personnelles** — icône buste utilisateur.
   - **Sécurité et mot de passe** — icône cadenas.
   - **Confidentialité** — icône œil.
4. **Section « PRÉFÉRENCES »** (carte blanche) :
   - **Notifications** — icône cloche + **toggle bleu activé**.
   - **Mode sombre** — icône croissant de lune + **toggle gris désactivé**.
   - **Rayon de recherche** — icône cible / radar ; valeur actuelle **« 50 km »** en bleu à droite ; **slider** horizontal sous le libellé avec bornes **« 1 km »** (gauche) et **« 100 km »** (droite).
5. **Section « AIDE & SUPPORT »** (carte blanche) — deux lignes navigation :
   - **Centre d’aide** — icône point d’interrogation bleu, chevron.
   - **Conditions d’utilisation** — icône document bleu, chevron.
6. **Bouton Déconnexion** : bandeau blanc bord fin, **icône sortie** + texte **« Déconnexion »** en **rouge**.
7. **Version** : texte gris discret « Version 2.4.0 (Build 189) ».
8. **Tab bar** (5 éléments) : Accueil, Explorer, **bouton +** circulaire bleu central (relief), Messages, **Profil** (icône + libellé **bleus** — onglet actif).

---

## 2. `préférences_de_notifications` — Notifications (vue synthétique)

**Rôle du design** : régler **quel type d’activité** déclenche une alerte et **par quel canal** (push / email), avec un rappel légal en bas.

1. **Retour** : flèche gauche.
2. **Titre** : « Notifications » centré, gras.
3. **Séparateur** : ligne grise fine sous le header.
4. **Section « ACTIVITÉ »** — chaque ligne : icône dans fond bleu clair circulaire, titre + **description** grise, **toggle** à droite :
   - **Nouveaux messages** — « Alertes de vos conversations » — **ON**.
   - **Demandes de matchs** — « Quelqu’un veut jouer avec vous » — **ON**.
   - **Nouveaux followers** — « Quand on s’abonne à votre profil » — **OFF**.
   - **Likes et commentaires** — « Interactions sur vos publications » — **ON**.
   - **Rappels de matchs** — « 1h avant le début de la rencontre » — **ON**.
5. **Section « MODE DE RÉCEPTION »** :
   - **Push Mobile** — icône cloche — **ON**.
   - **Email** — icône enveloppe — **OFF**.
6. **Pied de page** (texte gris centré) : possibilité de modifier à tout moment ; **certaines notifications critiques** ne peuvent pas être désactivées.
7. **Tab bar** (5 onglets) : Accueil, Explorer, **Matchs** (ballon dans cercle bleu — mis en avant), **Alertes** (cloche **bleue** — actif), Profil.

---

## 3. `réglages_de_notifications_granulaires` — Notifications (réglages fins)

**Rôle du design** : **détail par catégorie** (social, équipes & ligues, matchs & défis, messagerie) pour activer ou couper chaque type de notification — complète ou remplace la vue « préférences » selon le produit.

1. **Retour** : chevron bleu gauche.
2. **Titre** : « Notifications » centré, gras.
3. **Section « SOCIAL »** — lignes avec icône (cercle bleu clair), libellé, toggle :
   - **Mentions** (`@`) — **ON**.
   - **Likes** (cœur) — **ON**.
   - **Commentaires** (bulle) — **ON**.
   - **Nouveaux posts d'amis** (carrés superposés) — **OFF**.
4. **Section « ÉQUIPES & LIGUES »** :
   - **Changements de classement** (graphique) — **ON**.
   - **Trophées remportés** (trophée) — **ON**.
   - **Arrivées / Départs** (personne + valise) — **ON**.
5. **Section « MATCHS & DÉFIS »** :
   - **Nouvelles demandes** (cloche +) — **ON**.
   - **Rappels J-1 / H-2** (réveil) — sous-texte *« Ne ratez plus aucun match »* — **ON**.
   - **Saisie de score** (crayon / feuille) — **ON**.
   - **Fin de match** (drapeau à damier) — **ON**.
6. **Section « MESSAGERIE »** :
   - **Messages directs** (enveloppe) — **ON**.
   - **Photos / Vidéos reçues** (cadre image) — **OFF**.
7. **Tab bar** (4 onglets) : Accueil, Matchs, Équipes, **Réglages** (engrenage **bleu** — actif).

---

## 4. `paramètres_de_confidentialité_o'sport` — Confidentialité

**Rôle du design** : piloter **visibilité du profil**, **qui peut interagir** (tags, messages), options **sécurité / localisation / statut**, et accéder à la **liste des comptes bloqués**.

1. **Retour** : flèche gauche.
2. **Titre** : « Confidentialité » centré, gras.
3. **Section « VISIBILITÉ DU PROFIL »** (titre petites capitales grises) :
   - **Compte privé** — icône cadenas (carré bleu clair arrondi) ; description « Seuls les followers peuvent voir les posts et activités » ; **toggle ON** (bleu).
4. **Section « INTERACTIONS »** :
   - **Qui peut me taguer** — icône `@` ; valeur **« Amis uniquement »** en bleu ; **chevron bas** (liste / bottom sheet).
   - **Messages directs** — icône bulle ; **« Amis uniquement »** en bleu ; chevron bas.
5. **Section « SÉCURITÉ »** :
   - **Localisation précise** — icône épingle barrée ; **toggle OFF** (gris).
   - **Masquer mon statut en ligne** — icône œil barré ; **toggle ON** (bleu).
6. **Section « GESTION »** :
   - **Comptes bloqués** — icône **interdit** rouge sur fond rose clair ; texte « Gérer les utilisateurs que vous avez bloqués » ; **chevron droit** (sous-écran).
7. **Tab bar** (4 onglets) : Accueil, Recherche, Entraînement (haltères), **Réglages** (engrenage **bleu** — actif).

---

## 5. `liste_des_comptes_bloqués` — Comptes bloqués

**Rôle du design** : afficher les **utilisateurs bloqués** et permettre de les **débloquer** un par un.

1. **Retour** : chevron **bleu** gauche.
2. **Titre** : « Comptes bloqués » centré, gras.
3. **Bandeau d’information** (fond gris clair) : « Vous ne verrez plus le contenu de ces personnes et elles ne pourront pas vous contacter pour garantir votre tranquillité. »
4. **Liste** (fond blanc, séparateurs fins entre lignes) — chaque ligne :
   - **Avatar** circulaire à gauche (photo ou initiales sur fond bleu clair, ex. « KB »).
   - **Nom affiché** en gras.
   - **Pseudo** `@handle` en gris plus petit.
   - **Bouton « Débloquer »** à droite — style **contour** pilule (bord bleu, texte bleu).
5. **Exemples de profils** dans la maquette : Marc Lefebvre, Sarah Johnson, Karim Benzekri, Thomas Dubois (avec @ associés).
6. **Home indicator** en bas.

---

## 6. `centre_d'aide_et_support` — Aide & Support

**Rôle du design** : **self-service** (recherche, catégories, liens FAQ / CGU) et **contact direct** le support.

1. **Retour** : chevron gauche.
2. **Titre** : « Aide & Support » centré.
3. **Accroche** : « Bonjour, comment pouvons-nous vous aider ? » en gras.
4. **Barre de recherche** : champ arrondi, **loupe** à gauche, placeholder « Rechercher un sujet... ».
5. **Section « Catégories populaires »** — **grille 2×2** de cartes carrées arrondies, icône bleu clair + libellé :
   - **Mon Compte** (personne dans un cercle).
   - **Paiements & Abonnements** (billet / portefeuille).
   - **Sécurité** (bouclier + cadenas).
   - **Règles du jeu** (ballon de foot).
6. **Liens liste** (icône gauche, texte, chevron droit) :
   - **FAQ complète** (document + point d’interrogation).
   - **Conditions d'utilisation** (document / marteau).
7. **Bouton principal** : « **Contacter le support** » — bandeau bleu pleine largeur, **icône casque** + texte blanc.
8. **Tab bar** (4 onglets) : Accueil, Matchs, **Aide** (point d’interrogation **bleu** — actif), Profil.

---

## Relations entre écrans

| Depuis | Vers (logique) |
|--------|------------------|
| `paramètres_et_réglages` → Confidentialité | `paramètres_de_confidentialité_o'sport` |
| Confidentialité → Comptes bloqués | `liste_des_comptes_bloqués` |
| Paramètres → Notifications (toggle ou ligne) | `préférences_de_notifications` et/ou `réglages_de_notifications_granulaires` |
| Paramètres → Centre d’aide | `centre_d'aide_et_support` |

Les deux écrans **Notifications** ne sont pas identiques : l’un est **synthétique** (activité + push/email), l’autre **granulaire** (sous-types par section). Le produit peut les enchaîner (réglages fins depuis « Notifications ») ou n’en garder qu’un.

---

## Tableau récapitulatif des dossiers

| Dossier | Fonction principale |
|--------|----------------------|
| `paramètres_et_réglages` | Hub compte, préférences, aide, déconnexion, version |
| `préférences_de_notifications` | Types d’activité + push / email + disclaimer |
| `réglages_de_notifications_granulaires` | Social, équipes, matchs, messagerie — toggles détaillés |
| `paramètres_de_confidentialité_o'sport` | Visibilité, tags, MD, localisation, statut, bloqués |
| `liste_des_comptes_bloqués` | Liste + Débloquer |
| `centre_d'aide_et_support` | Recherche, catégories, FAQ, CGU, contacter support |

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de gestion de parametres**.*
