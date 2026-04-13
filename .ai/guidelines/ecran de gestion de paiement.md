---
name: osport-spec-ecran-billing-manage
description: "Spec maquettes O’Sport — gestion de paiement / abonnement, dossier `ecran de gestion de paiement`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans de gestion de paiement — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de gestion de paiement` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
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

Les designs couvrent le **parcours Premium** (découverte des offres, souscription, moyen de paiement, carte bancaire, succès), la **page d’abonnement actif** (factures PDF, changement de forfait, résiliation), le **changement de forfait** (mensuel / annuel) et une **modale de prorata** avant paiement du solde.

Style récurrent : fond blanc, **bleu** pour les actions et accents, cartes arrondies avec ombres légères, typographie sans-serif.

**Ordre de lecture conseillé (parcours)** : offre → paiement → (carte) → succès → gestion → changement de forfait → confirmation prorata.

---

## 1. `gestion_de_l'abonnement_premium_4` — Abonnement (offre Premium)

**Rôle du design** : présenter **O’Sport Premium Elite**, lister les **avantages**, comparer **Annuel** vs **Mensuel**, liens de gestion / restauration, puis **Passer au Premium**.

1. **Retour** : flèche gauche en haut à gauche.
2. **Titre barre** : « Abonnement » centré.
3. **Carte héro** (grand bandeau bleu dégradé, coins arrondis) :
   - Icône circulaire blanche avec **étoile** au centre.
   - Titre **« O'Sport Premium Elite »** en blanc gras.
   - Slogan : « L'expérience ultime pour athlètes » en blanc.
4. **Section « VOS AVANTAGES EXCLUSIFS »** (titre en petites capitales) — liste de **4 cartes** blanches arrondies, chacune avec icône bleu clair à gauche :
   - **Statistiques avancées** — « Suivi précis de vos performances » (icône graphique / barres).
   - **Zéro publicité** — « Navigation fluide sans interruptions » (icône type interdit).
   - **Badges exclusifs** — « Démarquez-vous de la communauté » (icône ruban / badge).
   - **Tournois illimités** — « Organisez des compétitions sans frais » (icône trophée).
5. **Section « CHOISIR MON FORFAIT »** :
   - **Forfait Annuel** (carte mise en avant — **bordure bleue épaisse**) :
     - Badge bleu **« ÉCONOMISEZ 30 % »** chevauchant le coin supérieur droit.
     - « 79,99€ par an ».
     - Équivalent **« 6,66€/mois »** en bleu.
   - **Forfait Mensuel** (carte bord gris) :
     - « Sans engagement ».
     - **« 9,99€/mois »** en gris foncé.
6. **Liens secondaires** (texte bleu clair) :
   - « Gérer mon abonnement actuel ».
   - « Restaurer les achats ».
7. **Bouton principal** : « **Passer au Premium** » — bandeau bleu pleine largeur, texte blanc.
8. **Tab bar** (4 onglets) : Accueil (maison), Explorer (boussole), **Abonnement** (badge étoile — **actif**), Profil (silhouette).
9. **Home indicator** iOS en bas.

---

## 2. `gestion_de_l'abonnement_premium_1` — Finaliser le paiement

**Rôle du design** : **checkout** — récapitulatif de commande, choix du **moyen de paiement**, total, puis **Payer maintenant**.

1. **Retour** : chevron gauche.
2. **Titre** : « Finaliser le paiement » centré, gras.
3. **Bloc « RÉSUMÉ DE LA COMMANDE »** (label petites capitales grises) :
   - Carte blanche ombrée :
     - « **Annuel O'Sport Premium** » en gras bleu foncé / noir.
     - Sous-ligne : « Facturation annuelle » en gris.
     - Prix **« 79,99€/an »** en bleu à droite.
4. **Bloc « MÉTHODES DE PAIEMENT »** :
   - Trois cartes blanches arrondies, chacune : **icône** à gauche, libellé au centre, **bouton radio** à droite.
     - **Carte de crédit** — mini-logos cartes, radio **sélectionné** (dégradé vert-bleu).
     - **Apple Pay** — logo Apple Pay, radio vide.
     - **PayPal** — logo PayPal, radio vide.
5. **Séparateur** horizontal fin.
6. **Ligne « Sous-total »** : montant en gris (ex. 79,99€).
7. **Ligne « TOTAL À PAYER »** : montant en **grand gras** (ex. 79,99€).
8. **Bouton** : « **Payer maintenant** » — bleu, texte blanc gras, coins arrondis.
9. **Home indicator** en bas.

---

## 3. `gestion_de_l'abonnement_premium_3` — Ajouter une carte

**Rôle du design** : saisir les **données de carte bancaire** (souvent après choix « Carte de crédit »), prévisualisation type carte, option **mémoriser** la carte, validation.

1. **Retour** : bouton texte **« Retour »** bleu avec chevron à gauche.
2. **Titre** : « Ajouter une carte » centré, gras.
3. **Aperçu carte** (grand rectangle bleu arrondi, style carte physique) :
   - Symbole **sans contact** blanc en haut à gauche.
   - Zone placeholder **logo réseau** (rectangle bleu clair) en haut à droite.
   - **Numéro** représenté par **quatre groupes de quatre points** blancs au centre.
   - Bas gauche : label « NOM DU TITULAIRE », valeur placeholder « VOTRE NOM ICI ».
   - Bas droite : label « EXPIRE », placeholder « MM/AA ».
4. **Formulaire** (champs bord gris clair, coins arrondis) :
   - **NOM SUR LA CARTE** — exemple « Jean Dupont ».
   - **NUMÉRO DE CARTE** — icône carte à gauche, placeholder `0000 0000 0000 0000`.
   - **DATE D’EXPIRATION** — demi-largeur, « MM/AA ».
   - **CVV** — demi-largeur, placeholder « 123 », **icône aide** (point d’interrogation) à droite.
5. **Interrupteur** : libellé « **Enregistrer pour mes futurs achats** » + **toggle bleu** position **activé** dans la maquette.
6. **Bouton** : « **Ajouter la carte** » — bleu pleine largeur, texte blanc.
7. **Home indicator** en bas.

---

## 4. `gestion_de_l'abonnement_premium_2` — Paiement réussi

**Rôle du design** : **confirmation positive** après un paiement réussi (activation Premium).

1. **Icône centrale** : grand cercle bleu pâle avec badge **« vérifié »** (forme étoilée) blanc contenant une **coche** bleue ; halo / ombre douce.
2. **Fond décoratif** : blanc avec **étoiles** et **formes géométriques** bleu très pâle (faible opacité), ambiance « célébration ».
3. **Titre** : « **Paiement réussi !** » — grand, gras, centré, noir / bleu marine.
4. **Message** : « Bienvenue dans l'élite O'Sport. Votre abonnement Premium est désormais actif. » — gris bleuté, centré, sur plusieurs lignes.
5. **Bouton** : « **Découvrir mes avantages** » — pilule bleue, texte blanc gras, ombre légère.
6. **Home indicator** en bas.
7. **Mise en page** : contenu **centré verticalement** avec beaucoup d’espace blanc.

---

## 5. `gestion_de_l'abonnement_premium_5` — Mon abonnement

**Rôle du design** : **tableau de bord** de l’abonnement actif — statut, prochaine facturation, **historique de factures** (PDF), **changer de forfait**, **résilier**.

1. **Retour** : chevron gauche.
2. **Titre** : « Mon Abonnement » centré, gras, bleu foncé.
3. **Carte statut** (bleu, coins arrondis, ombre) :
   - Pastille blanche avec **coche** + texte **« ABONNEMENT ACTIF »** en capitales blanches.
   - Nom du plan : « **Forfait Annuel Elite** » en grand blanc gras.
   - Ligne avec **icône calendrier** : « Prochaine facturation le **12 Mars 2026** » en blanc.
4. **Section « HISTORIQUE DES FACTURES »** :
   - En-tête avec lien **« Tout voir »** en bleu à droite.
   - **Liste de cartes** facture (fond blanc, arrondi) :
     - Gauche : icône **document** grise.
     - Centre : **date** en gras + **montant** en bleu (ex. 12 Mars 2025 — 79,99€).
     - Droite : bouton **« PDF »** (icône PDF + texte bleu sur fond bleu très pâle).
   - La maquette montre **trois** entrées (ex. 79,99€ / 79,99€ / 59,99€ sur années différentes).
5. **Action « Changer de forfait »** : lien bleu avec **icône double flèche** (échange).
6. **Séparateur** horizontal gris fin.
7. **Résiliation** : bouton pilule **fond rouge très pâle**, texte **« Résilier l'abonnement »** en rouge, **icône X** rouge à gauche.
8. **Tab bar** (4 onglets) : Accueil, Explorer, **Portefeuille / paiement** (**actif** en bleu), Profil.
9. **Home indicator** en bas.

---

## 6. `changement_de_forfait_premium` — Changer de forfait

**Rôle du design** : permettre de **passer d’un forfait à un autre** (ex. mensuel ↔ annuel) avec comparaison des options et **confirmation** du changement.

1. **Retour** : flèche gauche.
2. **Titre** : « Changer de forfait » centré, gras.
3. **Badge d’information** : pilule grise claire avec **icône i** + texte « **Votre forfait actuel : Mensuel** » (dans la maquette).
4. **Option « Mensuel »** (carte **non sélectionnée** dans l’exemple) :
   - Prix **« 9.99€ /mois »** en grand noir gras.
   - **Radio vide** en haut à droite.
   - Liste à puces avec **coches bleues** : accès illimité aux vidéos ; sans engagement ; qualité HD.
5. **Option « Annuel »** (carte **sélectionnée**) :
   - **Bordure bleue épaisse** + fond bleu très pâle.
   - Badges **« POPULAIRE »** (bleu) et **« -30 % »** (orange) près du titre.
   - Prix **« 79.99€ /an »** en grand **bleu** gras.
   - Sous-texte : « Équivaut à 6.66€/mois ».
   - **Radio rempli** (cercle bleu + coche blanche) en haut à droite.
   - Liste à coches bleues : accès illimité (libellé avec répétition « illimité » dans la maquette — possible coquille) ; contenu exclusif Premium ; mode hors-ligne ; économie annuelle (« Économisez 30€ par an »).
6. **Texte d’avertissement** (bas de zone, partiellement visible dans la description) : le changement prend effet **immédiatement** ; suite du texte tronquée (« Un… » — probablement prorata ou facturation).
7. **Bouton** : « **Confirmer le changement** » — bleu pleine largeur.
8. **Tab bar** (4 onglets) : Accueil, **Entraîner** (haltères), **Profil** (**actif** en bleu), Réglages (engrenage).
9. **Home indicator** en bas.

---

## 7. `confirmation_de_prorata_paiement` — Détails du changement (prorata)

**Rôle du design** : **modale / bottom sheet** de synthèse **financière** avant de payer le **solde** après changement de forfait (crédit ancien forfait + prix nouveau = **total à payer aujourd’hui**).

1. **Fond** : écran précédent **assombri**.
2. **Contenant** : carte / sheet **blanc**, coins supérieurs arrondis, **poignée** grise horizontale en haut (glissement).
3. **Icône** : cercle **bleu** avec **« i »** blanc (information), centré.
4. **Titre** : « **Détails du changement** » centré, gras.
5. **Paragraphe explicatif** (gris, centré) : nouveau forfait **immédiat** ; montant déjà payé sur l’offre actuelle **déduit** du prix de la nouvelle souscription.
6. **Détail des montants** :
   - Ligne « Crédit restant (ancien forfait) » → **-3,50 €** en **vert** (crédit).
   - Ligne « Prix du nouveau forfait » → **79,99 €** en noir.
   - **Séparateur** fin gris.
   - Ligne **« Total à payer aujourd'hui »** en gras avec montant **76,49 €** en **bleu** (mis en avant).
7. **Bouton principal** : « **Confirmer et payer** » — bleu, texte blanc, ombre légère.
8. **Bouton secondaire** : « **Annuler** » — texte seul gris foncé sous le bouton bleu.

---

## Synthèse du parcours

| Étape | Dossier | Rôle |
|------|---------|------|
| Découverte / souscription | `gestion_de_l'abonnement_premium_4` | Avantages, choix forfait, Passer au Premium |
| Paiement | `gestion_de_l'abonnement_premium_1` | Résumé, CB / Apple Pay / PayPal, Payer maintenant |
| Carte | `gestion_de_l'abonnement_premium_3` | Saisie carte + mémorisation + Ajouter la carte |
| Succès | `gestion_de_l'abonnement_premium_2` | Confirmation activation Premium |
| Gestion | `gestion_de_l'abonnement_premium_5` | Actif, factures PDF, changer, résilier |
| Changement | `changement_de_forfait_premium` | Comparer mensuel / annuel, Confirmer le changement |
| Prorata | `confirmation_de_prorata_paiement` | Détail crédit + total, Confirmer et payer |

---

## Tableau récapitulatif des dossiers

| Dossier | Fonction principale |
|--------|----------------------|
| `gestion_de_l'abonnement_premium_4` | Page Abonnement — offre Elite, forfaits, CTA Premium |
| `gestion_de_l'abonnement_premium_1` | Checkout — résumé, méthodes de paiement, total |
| `gestion_de_l'abonnement_premium_3` | Formulaire ajout carte + toggle enregistrer |
| `gestion_de_l'abonnement_premium_2` | Écran succès après paiement |
| `gestion_de_l'abonnement_premium_5` | Abonnement actif, factures, changer, résilier |
| `changement_de_forfait_premium` | Sélection nouveau forfait + confirmer |
| `confirmation_de_prorata_paiement` | Modale prorata — total à payer + confirmer / annuler |

---

## Notes pour l’implémentation

- Les numéros de dossiers `_1` à `_5` ne suivent pas l’ordre chronologique du parcours ; utiliser la **synthèse** ci-dessus pour le routing.
- Sur `changement_de_forfait_premium`, le libellé « Accès illimité illimité » dans la maquette ressemble à une **coquille** — à valider côté produit / copywriting.
- La modale `confirmation_de_prorata_paiement` suppose une logique **prorata** (crédit + nouveau prix = solde du jour).

---

*Document basé sur les fichiers `screen.png` du dossier **ecran de gestion de paiement**.*
