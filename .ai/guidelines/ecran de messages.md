---
name: osport-spec-ecran-messages
description: "Spec maquettes O’Sport — messagerie, dossier `ecran de messages`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans de messages — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de messages` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

Le dossier contient **deux écrans** qui forment le cœur de la **messagerie** :

1. **Liste des conversations** — vue d’ensemble, recherche, accès à une discussion, création d’une nouvelle conversation.
2. **Fil de discussion (DM)** — historique des messages, indicateurs de présence, appel vocal / vidéo, saisie et envoi.

Les libellés de la maquette sont en **anglais** (titres, placeholders, onglets).

---

## 1. `messages_list` — Liste des messages

**Rôle du design** : afficher toutes les **conversations** actives, repérer les **non lus**, **rechercher** une discussion et **démarrer** un nouvel échange.

1. **Titre** : « **Messages** » en grand, gras, en haut à gauche (noir).
2. **Bouton nouvelle conversation** : cercle **bleu clair** en haut à droite avec **« + »** bleu foncé — lance probablement une nouvelle discussion ou un choix de destinataire.
3. **Barre de recherche** : champ **pleine largeur**, coins très arrondis ; **loupe** grise à gauche ; placeholder **« Search conversations »**.
4. **Liste des fils** (défilement vertical) — chaque ligne :
   - **Avatar** circulaire à gauche (photo, illustration ou icône de groupe / calendrier).
   - **Nom** du contact ou du groupe en **gras** (noir).
   - **Aperçu** du dernier message en gris / bleu gris, **tronqué** avec « … » si trop long.
   - **Horodatage** à droite (ex. « 10:30 AM », « Yesterday », « Tue », « 2 weeks ago »).
5. **Indicateurs d’état** (selon la ligne) :
   - **Sunday League FC** : **point vert** sur l’avatar (en ligne / actif) ; **pastille rouge** avec le chiffre **« 2 »** (deux messages non lus).
6. **Exemples de conversations** dans la maquette :
   - **Sunday League FC** (groupe, ballon) — extrait du coach sur l’entraînement à 19 h…
   - **Alex Johnson** — équipement supplémentaire.
   - **Match Organizer** (icône calendrier) — invitation à un match vs…
   - **Sarah Davis** — remerciement pour l’invitation.
   - **Strategy Team** (groupe, picto trois silhouettes) — mise à jour du schéma de formation.
   - **David Kim** — début de saison.
7. **Barre de navigation inférieure** (4 onglets + libellés) :
   - **Home** (maison, gris).
   - **Teams** (groupe, gris).
   - **Messages** (bulle, **bleu** — **écran actif**).
   - **Profile** (silhouette, gris).

**Notes d’implémentation** : liste standard type messagerie ; gérer états **lu / non lu**, **en ligne**, formats de **date relatifs** ; le tap sur une ligne ouvre le fil (`direct_message_chat` ou équivalent).

---

## 2. `direct_message_chat` — Discussion directe (chat 1‑à‑1)

**Rôle du design** : afficher l’**historique** avec un interlocuteur, les **accusés de lecture**, l’**activité** (en train d’écrire), et permettre d’**écrire**, **joindre** du contenu, **emoji**, **appel** audio / **vidéo**.

1. **Header** :
   - **Retour** : flèche gauche (retour à la liste).
   - **Avatar** de l’interlocuteur avec **point vert** (en ligne).
   - **Nom** : « **Alex Striker** » en gras.
   - **Statut** : « **Online** » en vert sous le nom.
   - **Actions droite** : icône **téléphone** bleue (appel vocal) ; icône **caméra** bleue (appel vidéo).
2. **Zone de messages** (fond **gris très clair**) :
   - **Séparateur de date** : pilule grise centrée **« Today 9:41 AM »**.
   - **Messages reçus** (alignés **à gauche**) : bulles **blanches**, texte noir ; **petit avatar** à gauche de la bulle ; **heure** sous la bulle (ex. 9:41 AM).
   - **Messages envoyés** (alignés **à droite**) : bulles **bleu vif**, texte blanc ; sous la bulle : heure + **double coche bleue** (lu / vu).
3. **Indicateur de frappe** : avatar + petite bulle blanche avec **« … »** (l’autre personne est en train d’écrire).
4. **Zone de composition** (pied d’écran) :
   - **Bouton pièce jointe** : cercle gris avec **« + »** (photos, fichiers, localisation, etc.).
   - **Champ texte** : large zone blanche arrondie, placeholder **« Write a message... »** ; **icône emoji** (smiley) intégrée à droite dans le champ.
   - **Envoi** : bouton circulaire **bleu** avec **flèche vers le haut** blanche (envoyer le message).
5. **Style** : mode clair, coins arrondis (bulles, champs, boutons), **bleu** comme couleur d’envoi et d’actions principales.

---

## Parcours utilisateur

```
messages_list  →  (tap sur une conversation)  →  direct_message_chat
       ↑                                              |
       └──────────────── retour ←──────────────────┘
```

Le bouton **+** sur la liste mène typiquement vers la sélection d’un contact puis vers le même type d’écran `direct_message_chat`.

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `messages_list` | Liste, recherche, non lus, nouvelle conversation, tab Messages actif |
| `direct_message_chat` | Fil DM, statut, appels, bulles, saisie, pièces jointes, emoji, envoi |

---

## Périmètre non couvert par ce dossier

Seules **deux** maquettes sont présentes : pas d’écran de **sélection de destinataire**, **groupe**, **paramètres de conversation** ou **médias** dans ce dossier — à prévoir en produit / design ultérieur si besoin.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de messages**.*
