---
name: osport-design-stitch-complet
description: "Agrégat des specs maquettes O’Sport : un bloc par document source `ecran …` ou équivalent, pour lecture ou contexte agent."
license: MIT
metadata:
  author: documentation-markdown
---

# O'Sport — Spécification design complète de l'app

## Orientation

- Centraliser **toute** la documentation maquettes / flux UX du dépôt.
- Chaque grande section = **intégralité** d’un fichier source (copie intégrée).

## Cohérence d’abord

- Pour une **vue synthétique backend**, utiliser `osport-guide-agents-synthese-design.md` ; pour le **schéma**, `schema-mysql-laravel12-osport.md`.
- Modifier le **fichier source** (`ecran …`, `les premier …`) quand le produit change ; garder ce stitch aligné par régénération ou patch conscient des doublons signalés dans les sources.

## Référence rapide

1. **Sections « Document source : … »** — ordre d’apparition = ordre d’agrégation.
2. **Contenu entre balises** `<!-- début / fin : fichier.md -->` — copie du source au moment de la fusion.

## Comment appliquer

1. Repérer la section **Document source** du flux concerné.
2. Lire la **Vue d’ensemble** de cette section puis les listes numérotées par écran.
3. Ne pas traiter ce fichier comme source unique de vérité si le `.md` d’origine a été corrigé sans mettre à jour l’agrégat.

---


## Document source : `les premier ecran de direction.md`

<!-- début : les premier ecran de direction.md -->

# Les premiers écrans de direction — O’Sport

Ce document décrit **chaque maquette** du dossier `les premier ecran de direction` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

---

## Vue d’ensemble

Le dossier regroupe :

- **Trois écrans d’onboarding** qui enchaînent une **story produit** (connexion avec des sportifs → gestion d’équipe → organiser des matchs), avec **pagination** sur 3 étapes et boutons **Suivant** / **Commencer** (+ **ignorer** sur l’étape 2).
- Un écran **O’Sport Pro** (paywall / essai gratuit), souvent affiché **après** l’introduction ou en parallèle selon le parcours monétisation.

**Remarque** : le fichier `onboarding_-_organize_matches` est **identique en intention** à la maquette du même nom dans **`ecran forfait de paiement`** (même scène « Organisez des matchs »). L’écran **`o'sport_pro_subscription_screen`** reprend aussi la **même proposition** que **`o'sport_pro_subscription_screen_1`** dans `ecran forfait de paiement` — à traiter comme **assets dupliqués** ou variantes de la même maquette en design.

---

## Ordre logique du flux onboarding

| Étape | Dossier | Pagination (3) |
|------|---------|----------------|
| 1 | `onboarding_-_connect_with_athletes` | 1ᵉʳ point actif (bleu) |
| 2 | `onboarding_-_team_management` | Barre du **milieu** active |
| 3 | `onboarding_-_organize_matches` | **3ᵉ** indicateur actif (barre allongée) |

Ensuite : monétisation optionnelle → `o'sport_pro_subscription_screen`.

---

## 1. `onboarding_-_connect_with_athletes` — Connexion avec des sportifs (étape 1)

**Rôle du design** : **premier** message de valeur — rejoindre la communauté locale et trouver des **partenaires de jeu**.

### Zone supérieure

1. **Barre de statut** visible (heure 9:41, signal, Wi‑Fi, batterie).
2. **Visuel** : photo **demi-écran** — basket en extérieur au crépuscule, silhouettes, ballon en l’air (ambiance **communauté / action**).

### Carte basse (feuille sombre)

3. **Contenant** : panneau **bleu nuit / noir** avec **coins supérieurs très arrondis** (effet bottom sheet).
4. **Poignée** : petite barre grise horizontale centrée.
5. **Titre** : « **Connectez-vous avec des sportifs** » — blanc, gras, centré.
6. **Texte** : « Trouvez des partenaires de jeu et rejoignez la communauté O’Sport locale pour organiser vos prochains matchs. » — gris clair / blanc cassé, centré.
7. **Pagination** : **3 points** — le **premier** est **bleu vif**, les deux autres gris foncé (écran **1/3**).
8. **Bouton** : « **Suivant** » — bleu vif, texte blanc gras, pleine largeur (marges), coins arrondis.
9. **Home indicator** iOS en bas.

---

## 2. `onboarding_-_team_management` — Gérer votre équipe (étape 2)

**Rôle du design** : expliquer la **dimension club / équipe** (création, matchs, stats).

### Fond et illustration

1. **Fond** : gris très clair / off-white uniforme.
2. **Illustration** centrale :
   - Grand **cercle** bleu très pâle en arrière-plan.
   - **Carré blanc** arrondi avec ombre, contenant des **formes géométriques** bleues (hexagone, drapeau, etc.).
   - **Pastille bleue** qui chevauche le coin bas-droit du carré avec **trophée** blanc.

### Texte

3. **Titre** : « **Gérez votre équipe** » — gras, bleu marine foncé, centré.
4. **Description** : « Créez votre club, organisez des matchs et suivez vos statistiques de victoires. » — gris / bleu marine, centré, plusieurs lignes.

### Navigation

5. **Pagination** : **point** gris — **barre bleue allongée** (milieu actif = **2/3**) — **point** gris.
6. **Bouton principal** : « **Suivant** » — bleu vif, texte blanc, pleine largeur.
7. **Lien secondaire** : « **Ignorer pour l'instant** » — texte gris-bleu, sous le bouton (passer le reste du onboarding ou reporter).
8. **Home indicator** en bas.

---

## 3. `onboarding_-_organize_matches` — Organisez des matchs (étape 3)

**Rôle du design** : clôturer l’intro par la **réservation de terrains** et les **défis** entre équipes ; passage à l’app avec **« Commencer »**.

1. **Fond** clair (~`#F8F9FB`).
2. **Illustration** : halo circulaire bleu pâle ; **cercle blanc** (calendrier bleu) + **cercle bleu** (épingle blanche) en chevauchement, ombres légères.
3. **Titre** : « **Organisez des matchs** » — noir gras.
4. **Sous-titre** : « Réservez des terrains et lancez des défis aux autres équipes de votre ville. » — gris, centré.
5. **Pagination** : deux **petits points** clairs + **barre bleue longue** (position **3/3**).
6. **Bouton** : « **Commencer** » — bleu, blanc, pilule, ombre.
7. Pas de lien « Ignorer » sur cette dernière étape dans la maquette.

---

## 4. `o'sport_pro_subscription_screen` — O’Sport Pro (essai / abonnement)

**Rôle du design** : présenter **l’offre Pro** après (ou indépendamment de) l’onboarding — avantages, **Annuel** vs **Mensuel**, prix, essai, légal.

### Header

1. **×** fermer (gauche).
2. **« O'Sport Pro »** centré en bleu.
3. **?** aide dans un cercle sombre (droite).

### Accroche

4. **Icône** carré bleu arrondi + **badge étoile** blanc.
5. **Titre** : « **Passez au niveau supérieur** » — marine gras.
6. **Sous-titre** gris sur la communauté d’élite et les performances.

### Avantages

7. **Quatre** lignes avec **coche verte** ronde : statistiques avancées ; zéro publicité ; badges exclusifs ; priorité réservations.

### Forfait

8. **Toggle** pilule : **Annuel** sélectionné (fond blanc, texte bleu, badge **-20 %** vert) ; **Mensuel** gris inactif.

### Carte tarif

9. Encart blanc bord bleu clair : **« ABONNEMENT PRO »** ; badge **« MEILLEURE OFFRE »** ; prix **« 4,99€ / mois »** en grand.
10. Séparateur ; **7 jours d'essai gratuit** (bouclier vert) ; **Annulable à tout moment** (calendrier gris).

### CTA et pied

11. Bouton **« Commencer l'essai gratuit »** bleu plein.
12. **Disclaimer** petit gris : renouvellement **59,88 €/an** (soit 4,99 €/mois) sauf annulation **24 h** avant fin d’essai.
13. Liens soulignés : **Conditions**, **Confidentialité**, **Restaurer**.

---

## Liens avec d’autres dossiers

| Ce dossier | Dossier proche |
|-----------|----------------|
| `onboarding_-_organize_matches` | `ecran forfait de paiement/onboarding_-_organize_matches` |
| `o'sport_pro_subscription_screen` | `ecran forfait de paiement/o'sport_pro_subscription_screen_1` (thème clair identique) |

En base de code, préférer **une seule source** de vérité pour éviter les divergences de copy ou de prix.

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `onboarding_-_connect_with_athletes` | Étape 1/3 — communauté & partenaires, Suivant |
| `onboarding_-_team_management` | Étape 2/3 — club & stats, Suivant + Ignorer |
| `onboarding_-_organize_matches` | Étape 3/3 — terrains & défis, Commencer |
| `o'sport_pro_subscription_screen` | Paywall Pro — essai 7 j, Annuel/Mensuel, légal |

---

## Notes pour l’implémentation

- **Navigation** : `Suivant` avance `pageIndex` ; `Ignorer` peut appeler `completeOnboarding()` ou ouvrir l’accueil sans enregistrer les étapes intermédiaires (définition produit).
- **Paywall** : le **×** doit décider si l’utilisateur peut **revenir** à l’app sans souscrire (soft paywall) ou seulement fermer après achat (rare).
- Le nom du dossier parent utilise **« premier »** au singulier — cohérent avec le dossier sur disque ; en français correct on écrirait souvent **« premiers »**.

---

*Document basé sur les fichiers `screen.png` du dossier **les premier ecran de direction**.*


---

## Document source : `ecran de connection et d'inscription.md`

<!-- début : ecran de connection et d'inscription.md -->

# Écrans de connexion et d’inscription — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de connection et d'inscription` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Les captures sont nommées `screen.png` dans chaque sous-dossier.

---

## Vue d’ensemble du parcours

Les designs couvrent :

- **Connexion** (email / mot de passe, réseaux sociaux, lien vers inscription).
- **Inscription en plusieurs étapes** (identifiants → localisation → infos perso).
- **Onboarding sports** (sélection des sports pratiqués).
- **Vérification d’email** après inscription.
- **Mot de passe oublié** en **3 étapes** (email → code OTP → nouveau mot de passe).
- **Une variante d’écran** de définition / mise à jour du mot de passe (`password_reset_screen`), distincte du dernier pas du flux « oublié ».

Palette et style récurrents : fond clair (blanc / gris très pâle), **bleu vif** pour les actions principales, typographie sans-serif moderne, champs et boutons très arrondis (style « pilule »).

---

## 1. `o’sport_login_screen` — Connexion

**Rôle du design** : permettre à un utilisateur existant de se connecter et d’accéder aux alternatives (création de compte, Google, Apple).

1. **Conteneur** : carte ou zone principale aux coins très arrondis sur fond clair.
2. **Logo** : cercle bleu avec drapeau à damier (symbole sport / course), léger relief (ombre).
3. **Nom de l’app** : « O’Sport » en bleu, sous le logo.
4. **Titre** : « Welcome back » en grand, gras, noir.
5. **Sous-titre** : « Log in to join your teammates. » en gris bleuté.
6. **Champ Email** :
   - Libellé « Email » au-dessus.
   - Champ pilule, bord gris clair, **icône enveloppe** à gauche.
   - Placeholder « Enter your email ».
7. **Champ Mot de passe** :
   - Libellé « Password ».
   - Même style pilule, **cadenas** à gauche, saisie masquée par des points.
   - **Œil** à droite pour afficher / masquer le mot de passe.
8. **Lien** : « Forgot Password? » aligné à droite, en bleu (vers récupération).
9. **Bouton principal** : « Sign In » — fond bleu, texte blanc, ombre bleue légère.
10. **Séparateur** : deux traits gris avec « OR » au centre.
11. **Bouton secondaire** : « Create an account » — fond blanc, bordure bleue, texte bleu.
12. **Connexion sociale** : deux boutons circulaires côte à côte (bord gris léger) — **Google** et **Apple**.
13. **Mentions légales** : « By signing in, you agree to our **Terms of Service**. » en petit gris ; « Terms of Service » souligné (lien).

---

## 2. `sign_up_step_1_credentials` — Inscription, étape 1 (identifiants)

**Rôle du design** : collecter email et mot de passe, informer sur la force du mot de passe et obtenir le consentement CGU avant de passer à l’étape suivante.

1. **Retour** : flèche gauche en haut à gauche.
2. **Titre de l’écran** : « Inscription » centré, gras.
3. **Indicateur d’étapes** : une **barre bleue allongée** (étape active) + **deux pastilles** claires (étapes suivantes).
4. **Titre de section** : « Créez votre compte » en grand, gras.
5. **Sous-texte** : « Rejoignez la communauté O'Sport dès maintenant. » en gris bleuté.
6. **Email** : libellé « Email », placeholder `votre@email.com`, champ arrondi clair.
7. **Mot de passe** :
   - Libellé « Mot de passe », saisie masquée.
   - **Œil** à droite pour la visibilité.
   - Texte « Force du mot de passe : Moyen » sous le champ.
   - **Barre de force** segmentée (4 segments) : une partie remplie en bleu (niveau « moyen »).
8. **Confirmation** : libellé « Confirmer le mot de passe », saisie masquée (points plus clairs dans la maquette).
9. **Case à cocher** : cercle vide + texte « J'accepte les **Conditions Générales d'Utilisation** et la politique de confidentialité. » (lien bleu sur les CGU).
10. **Bouton** : « Suivant » en bleu, texte blanc, **flèche vers la droite** après le libellé.
11. **Pied de page** : « Vous avez déjà un compte ? **Se connecter** » — « Se connecter » en bleu (retour connexion).

---

## 3. `sign_up_step_2_location` — Inscription, étape 2 (localisation)

**Rôle du design** : situer l’utilisateur pour proposer partenaires et matchs à proximité (saisie manuelle ou GPS + carte).

1. **Retour** : flèche gauche en haut à gauche.
2. **Progression** : **trois points** en haut au centre — le **point du milieu** est bleu (étape 2 sur 3).
3. **Titre** : « Où êtes-vous ? » en grand, gras.
4. **Description** : « Trouvez des partenaires et des matchs près de chez vous. »
5. **Illustration** : grand pictogramme **épingle de carte** dans un cercle blanc, halos bleus concentriques (effet « localisation » / pulsation).
6. **Recherche d’adresse** : champ arrondi avec **loupe** à gauche, placeholder « Saisissez votre adresse... ».
7. **Bouton GPS** : style secondaire (bord bleu clair), **icône de visée / position** + texte « Utiliser ma position actuelle ».
8. **Carte** : aperçu type plan (ex. zone parisienne dans la maquette), **marqueur / zone** bleue sur la carte.
9. **Contrôles de zoom** : boutons « + » et « – » en bas à droite de la carte.
10. **Légende** : petite mention en capitales grises « APERÇU DE LA ZONE » sous la carte.
11. **Bouton principal** : « Suivant » — bandeau bleu plein, texte blanc en bas.

---

## 4. `sign_up_step_3_personal_info` — Inscription, étape 3 (profil)

**Rôle du design** : finaliser l’identité affichée (nom, prénom, pseudo, date de naissance) et créer le compte.

1. **Retour** : flèche en haut à gauche.
2. **Indicateur d’étapes** : trois segments — **la barre bleue longue en fin** indique la **3ᵉ étape** (dernière).
3. **Titre** : « Parlez-nous de vous ».
4. **Sous-texte** : « Complétez votre profil pour rejoindre la communauté O'Sport. »
5. **Prénom** : libellé « Prénom », placeholder « Votre prénom ».
6. **Nom** : libellé « Nom », placeholder « Votre nom ».
7. **Nom d’utilisateur** :
   - Exemple saisi « osport_legend ».
   - **Coche verte** dans le champ (validation OK).
   - Message vert sous le champ : « Ce nom d'utilisateur est disponible ».
8. **Date de naissance** : libellé « Date de naissance », format « JJ/MM/AAAA », **icône calendrier** à droite (ouverture date picker attendue).
9. **Bouton final** : « Créer mon compte » — bleu, texte blanc.
10. **Mention légale** : « En créant un compte, vous acceptez nos Conditions Générales d'Utilisation. » en petit en bas.

---

## 5. `discover_sports_onboarding` — Choix des sports (onboarding)

**Rôle du design** : faire choisir à l’utilisateur **un ou plusieurs sports** pour personnaliser le contenu (fil, suggestions, communautés).

1. **Retour** : chevron gauche en haut à gauche.
2. **Titre app** : « O'Sport » centré en haut.
3. **Barre de progression horizontale** : portion gauche remplie en bleu (dans la maquette, environ **50 %** — indique une position dans un parcours plus long, ex. 4 étapes).
4. **Question principale** : « Quels sports pratiquez-vous ? » centré, gras.
5. **Consigne** : « Sélectionnez vos favoris pour personnaliser votre expérience. » en gris, centré.
6. **Grille de cartes** (2 colonnes) : chaque carte = **image du sport** + **libellé** (Football, Basketball, Tennis, Running, Yoga, Padel, etc.).
7. **État non sélectionné** : carte blanche sans bordure marquée.
8. **État sélectionné** : **bordure bleue** + **pastille bleue** avec **coche blanche** en haut à droite de l’image (ex. Football et Tennis sélectionnés dans la maquette).
9. **Pagination par points** : 4 indicateurs sous la grille — **pastille bleue allongée** sur la position courante, les autres en gris.
10. **Bouton** : « Continuer » — pleine largeur, bleu, coins très arrondis, en bas.

---

## 6. `email_verification_screen` — Vérification de l’adresse email

**Rôle du design** : confirmer qu’un mail a été envoyé, guider l’utilisateur vers sa boîte mail et proposer renvoi ou correction d’adresse.

1. **Retour** : chevron gauche.
2. **Titre barre** : « Vérification » centré.
3. **Illustration** : grand cercle bleu pâle avec **icône enveloppe** bleue et **pastille de notification** sur l’enveloppe.
4. **Titre** : « Vérifiez votre email ».
5. **Texte explicatif** (centré) :
   - « Nous avons envoyé un email de confirmation à **lucas.sport@email.com**. » (l’adresse est mise en avant).
   - « Veuillez cliquer sur le lien dans le message pour activer votre compte O'Sport. »
6. **Bouton principal** : « Ouvrir l'application mail » — bleu, blanc, ombre légère.
7. **Lien** : « Renvoyer l'email » en bleu souligné.
8. **Lien secondaire** : « Modifier l'adresse email » en gris plus clair.

---

## 7. `forgot_password_step_1_email` — Mot de passe oublié, étape 1

**Rôle du design** : demander l’email pour envoyer un **code de récupération**.

1. **Retour** : flèche gauche.
2. **Icône** : cercle bleu clair avec **clé** bleue au centre (accès / sécurité).
3. **Titre** : « Mot de passe oublié ».
4. **Message rassurant** : « Pas d’inquiétude ! Saisissez votre adresse e-mail ci-dessous pour recevoir un code de récupération. » en bleu moyen, centré.
5. **Champ** : libellé « E-mail », champ pilule bord bleu clair, **enveloppe** bleue à gauche, placeholder « Votre e-mail ».
6. **Bouton** : « Envoyer le code » — bleu, texte blanc, **icône d’envoi** (avion en papier) à droite du texte, ombre.
7. **Fond** : blanc avec **dégradés circulaires** bleu très pâlé discrets dans les coins (ambiance douce).

---

## 8. `forgot_password_step_2_otp` — Mot de passe oublié, étape 2 (code)

**Rôle du design** : saisir le **code à 6 chiffres** reçu par email (ou SMS selon implémentation) et permettre de renvoyer le code.

1. **Retour** : flèche gauche.
2. **Titre barre** : « Vérification ».
3. **Illustration** : smartphone stylisé dans un cercle bleu pâle + **bulle de message** bleue avec points (message reçu).
4. **Titre** : « Vérification du code ».
5. **Instructions** : « Entrez le code à 6 chiffres envoyé à » + adresse en gras (ex. `contact@sport-enthusiast.com` dans la maquette).
6. **Saisie OTP** : **6 cases** arrondies côte à côte ; dans la maquette, chiffres `4`, `7`, `9` puis tirets dans les cases vides.
7. **Aide** : « Vous n’avez pas reçu de code ? » + lien bleu **« Renvoyer le code »**.
8. **Bouton** : « Vérifier » — bleu, texte blanc, **coche** dans un petit cercle blanc à droite du libellé, ombre.
9. **Indicateur système** : fine barre horizontale en bas (home indicator type iPhone).

---

## 9. `forgot_password_step_3_reset` — Mot de passe oublié, étape 3 (nouveau mot de passe)

**Rôle du design** : définir un **nouveau mot de passe** après validation du code, avec **indicateur de force** et **critères** explicites.

1. **Retour** : chevron gauche.
2. **Titre barre** : « Nouveau mot de passe ».
3. **Titre de page** : « Créez votre nouveau mot de passe ».
4. **Texte d’aide** : « Assurez-vous qu'il soit différent de l'ancien pour une meilleure sécurité de votre compte O'Sport. »
5. **Champ « Nouveau mot de passe »** : cadenas bleu à gauche, saisie masquée, **œil** à droite.
6. **Bloc force du mot de passe** :
   - Libellé « FORCE DU MOT DE PASSE » + pourcentage **65 %** en bleu à droite.
   - Barre horizontale remplie à hauteur du pourcentage.
   - Conseil : « Combinez lettres, chiffres et symboles pour plus de sécurité. »
7. **Champ « Confirmer le mot de passe »** : icône type **rechargement / verrou** à gauche, saisie masquée, **œil barré** (masquage actif dans la maquette).
8. **Liste de critères** :
   - « Au moins 8 caractères » avec **coche verte** (validé).
   - « Un chiffre et un symbole » avec pastille gris bleu (non validé dans l’exemple).
9. **Bouton** : « Réinitialiser » — bleu plein, texte blanc, ombre.
10. **Indicateur d’étape** : « Étape 3 sur 3 » sous le bouton.
11. **Home indicator** en bas.

---

## 10. `password_reset_screen` — Variante « réinitialisation » du mot de passe

**Rôle du design** : écran autonome de **définition ou mise à jour** du mot de passe (libellés et CTA différents de l’étape 3 du flux « oublié ») — utile pour un lien magique, un écran unique, ou une maquette alternative.

1. **Retour** : chevron gauche.
2. **Titre barre** : « Nouveau mot de passe ».
3. **Titre principal** : « Réinitialisation » (et non « Créez votre nouveau… »).
4. **Texte** : « Choisissez un mot de passe robuste pour protéger votre compte O'Sport et reprendre vos activités sportives. »
5. **Champ « Nouveau mot de passe »** : même logique pilule — cadenas bleu, points masqués, œil à droite.
6. **Encart « Force du mot de passe »** (carte bordée) :
   - « Force du mot de passe » + badge **« MOYEN »** (pilule bleu clair).
   - Barre de progression (~60 % bleu).
   - Conseil : « Utilisez au moins 8 caractères avec un mélange de lettres, chiffres et symboles. »
7. **Champ « Confirmer le mot de passe »** : icône circulaire / rechargement à gauche, œil à droite.
8. **Bouton** : « Mettre à jour le mot de passe » — bleu, **coche** dans cercle blanc à droite du texte.
9. **Pied** : « Besoin d'aide ? **Contactez le support** » — partie support en bleu (lien).

---

## Synthèse des dossiers

| Dossier | Fonction principale |
|--------|----------------------|
| `o'sport_login_screen` | Connexion email/mot de passe + social + inscription |
| `sign_up_step_1_credentials` | Email, mot de passe, force, CGU, suivant |
| `sign_up_step_2_location` | Adresse, GPS, carte, zone |
| `sign_up_step_3_personal_info` | Identité, pseudo, date de naissance, création compte |
| `discover_sports_onboarding` | Multi-sélection sports + continuer |
| `email_verification_screen` | Attente activation mail + ouvrir mail + renvoi |
| `forgot_password_step_1_email` | Demande du code par email |
| `forgot_password_step_2_otp` | Saisie OTP 6 cases + renvoi |
| `forgot_password_step_3_reset` | Nouveau MDP avec % force + critères + étape 3/3 |
| `password_reset_screen` | Variante réinitialisation + mise à jour + support |

---

*Document généré à partir des maquettes `screen.png` du dossier **ecran de connection et d'inscription**.*


---

## Document source : `ecran de creation d'equipe.md`

<!-- début : ecran de creation d'equipe.md -->

# Écrans de création d’équipe — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de creation d'equipe` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est dans un sous-dossier sous le nom `screen.png`.

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


---

## Document source : `ecran de gestion de paiement.md`

<!-- début : ecran de gestion de paiement.md -->

# Écrans de gestion de paiement — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de gestion de paiement` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est dans un sous-dossier sous le nom `screen.png`.

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


---

## Document source : `ecran de gestion de parametres.md`

<!-- début : ecran de gestion de parametres.md -->

# Écrans de gestion de paramètres — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de gestion de parametres` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est dans un sous-dossier sous le nom `screen.png`.

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


---

## Document source : `ecran de gestion des match - gestion de du profil d'equipe.md`

<!-- début : ecran de gestion des match - gestion de du profil d'equipe.md -->

# Écrans — gestion des matchs & profil d’équipe — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de gestion des match -  gestion de du profil d'equipe` (tel qu’il apparaît sur le disque). Rôle du flux, éléments visuels et interactions, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

## Document source : `ecran de messages.md`

<!-- début : ecran de messages.md -->

# Écrans de messages — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de messages` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

## Document source : `ecran de moteur de recherche.md`

<!-- début : ecran de moteur de recherche.md -->

# Écrans du moteur de recherche — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de moteur de recherche` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

## Document source : `ecran de notifications.md`

<!-- début : ecran de notifications.md -->

# Écrans de notifications — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran de notifications` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

## Document source : `ecran des posts.md`

<!-- début : ecran des posts.md -->

# Écrans des posts — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran des posts` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

## Document source : `ecran forfait de paiement.md`

<!-- début : ecran forfait de paiement.md -->

# Écrans forfait de paiement — O’Sport Pro

Ce document décrit **chaque maquette** du dossier `ecran forfait de paiement` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

## Document source : `ecran post publication.md`

<!-- début : ecran post publication.md -->

# Écrans post publication — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran post publication` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

---

## Vue d’ensemble

Le parcours couvre :

1. **Sélection des médias** dans la photothèque (thème sombre, multi-sélection, ordre, aperçu, recadrage).
2. **Capture photo** (interface caméra type appareil photo).
3. **Composer la publication** (légende, identifier des personnes, lieu, sport, **cross-post** réseaux sociaux).
4. **Confirmation de succès** après publication d’un **carrousel**.

Les titres varient légèrement (**« Nouveau post »** vs **« Nouvelle Publication »**) selon l’écran — à harmoniser côté produit.

---

## 1. `sélection_de_médias_1` — Sélection de médias (variante 1)

**Rôle du design** : choisir **plusieurs** photos / vidéos dans **« Récents »**, voir l’**ordre** de sélection, **éditer** (recadrage, réglages) puis passer à l’étape suivante.

### Header (thème sombre)

1. **×** blanc à gauche — annuler / fermer.
2. **Titre** centré : **« Nouveau post »** en blanc gras.
3. **Bouton** à droite : **« Suivant (3) »** — rectangle bleu vif arrondi, texte blanc (le **3** = nombre d’éléments sélectionnés).

### Aperçu principal

4. Grande zone d’**image / vidéo** courante (coins arrondis) — ex. chaussure sur piste.
5. **Sous-couche d’actions** sur l’aperçu :
   - Bas gauche : bouton sombre **plein écran** (flèches diagonales).
   - Bas droite : **recadrer** + **réglages / filtres** (icône sliders).

### Bandeau des sélections

6. **Carrousel de miniatures** horizontal sous l’aperçu : vignettes des médias choisis ; la vignette active a une **bordure bleue** ; à droite une case **pointillée +** pour **ajouter** un média.

### Galerie

7. **En-tête de section** : **« Récents »** + chevron bas (changement d’album) ; à droite **icône caméra** (ouvrir la prise de vue).
8. **Grille** de la pellicule : sur les vignettes sélectionnées, **pastilles bleues** numérotées **1, 2, 3** (ordre d’insertion) en haut à droite.
9. Une vignette **vidéo** peut afficher une **icône lecture** (triangle).

### Tab bar

10. Cinq icônes (Home, Search, **+** central lumineux, Activity cœur, Profil) — style **sombre** cohérent avec l’écran.

---

## 2. `sélection_de_médias_2` — Sélection de médias (variante 2)

**Rôle du design** : **même flux** de choix de médias avec **légères différences** d’UI (bouton suivant sans compteur explicite, barre d’outils galerie différente, une seule sélection visible en surbrillance sur la grille).

### Header

1. **×** ; **« Nouveau post »** ; bouton bleu **« Suivant »** (sans **(3)** dans le libellé).

### Aperçu + overlays

2. Même logique **aperçu grand** + **agrandir**, **crop**, **sliders** en bas de l’image.

### Contrôles sous l’aperçu

3. **« Récents »** + chevron à gauche.
4. À droite : **caméra** + bouton circulaire **bleu** avec icône **multi-sélection** (deux carrés superposés) — met en avant le mode multi-sélection.

### Grille

5. Grille **3 colonnes** ; la vignette correspondant à l’aperçu a une **bordure bleue épaisse** (sélection courante) ; pas de pastilles **1, 2, 3** visibles sur cette maquette (sélection simple ou étape différente du flux).

### Tab bar

6. Identique en structure (Home, Search, **+** actif, Activity, Profil).

**Différence clé vs `_1`** : compteur dans **« Suivant (n) »** ; pastilles d’**ordre** sur la grille ; rangée de **miniatures sélectionnées** au-dessus de la grille dans `_1` uniquement.

---

## 3. `camera_capture_mode` — Mode caméra

**Rôle du design** : **prendre une photo** (ou basculer vers d’autres modes) pour alimenter un post, sans passer par la galerie.

1. **×** en haut à gauche — quitter la caméra.
2. **« LIVE »** avec point rouge au centre-haut — mode **Live Photo** (ou indicateur live).
3. **Flash** (éclair) en haut à droite.
4. **Viseur** : prise de vue réelle avec **masque circulaire** (coins assombris / vignette).
5. **Guide de cadrage** : rectangle blanc arrondi avec **repères jaunes** au milieu de chaque côté.
6. **Zoom** : pastilles **.5**, **1x** (sélectionné, bord jaune), **3**.
7. **Modes** défilants horizontalement : **VIDEO**, **PHOTO** (actif, texte bleu), **PORTRAIT**, bords **LAPSE** / **SLO-MO** tronqués.
8. **Barre du bas** :
   - **Vignette pellicule** à gauche (dernière photo).
   - **Déclencheur** central (double cercle blanc).
   - **Rotation caméra** avant/arrière à droite.

---

## 4. `nouvelle_publication_1` — Nouvelle publication (variante 1)

**Rôle du design** : **rédiger** le post après choix du média : légende, métadonnées, **partage croisé** vers **Facebook, Instagram, Twitter**.

### Header

1. **Retour** flèche gauche.
2. **Titre** : **« Nouvelle Publication »** gras.
3. **« Publier »** bleu à droite.

### Carte média + légende

4. **Vignette** carrée arrondie à gauche (ex. chaussures sur piste).
5. Zone texte à droite : placeholder **« Écrivez une légende... Dites à vos amis ce que vous faites ! »**.

### Carte options

6. Trois lignes avec icône cercle gris, libellé, chevron **>** :
   - **Identifier des personnes** (icône personne +).
   - **Ajouter un lieu** (épingle).
   - **Associer à un sport** — valeur **« Football »** en bleu à droite du chevron.

### Partage croisé

7. Titre section petites capitales grises : **« PARTAGER AUSSI SUR »**.
8. Carte avec **3 interrupteurs** :
   - **Facebook** — **ON** (bleu).
   - **Instagram** — **OFF** (gris).
   - **Twitter** — **OFF** (gris).

### Tab bar

9. Accueil, Explorer, **+** bleu central, Alertes, Profil.

---

## 5. `nouvelle_publication_2` — Nouvelle publication (carrousel)

**Rôle du design** : même écran de **composition** lorsque le post contient un **carrousel** (plusieurs médias) — badge **1/3**, points de pagination sur la vignette ; **moins** de réseaux dans la section partage.

1. **Header** identique (retour, **Nouvelle Publication**, **Publier**).
2. **Vignette** avec badge **« 1/3 »** en haut à droite et **points** de carrousel en bas (premier point bleu).
3. Même placeholder de **légende**.
4. Même carte **Identifier des personnes**, **Ajouter un lieu**, **Associer à un sport** → **Football**.
5. **« PARTAGER AUSSI SUR »** avec **seulement 2 lignes** : **Facebook** ON, **Instagram** OFF (**Twitter** absent de cette maquette).

6. **Tab bar** identique.

**Différence clé vs `_1`** : **carrousel** visible sur la miniature ; **Twitter** retiré du bloc partage (ou scroll tronqué).

---

## 6. `succès_de_publication_carrousel` — Succès après publication

**Rôle du design** : confirmer que le **carrousel** a bien été **publié** et proposer d’**ouvrir le post** ou de **revenir à l’accueil**.

1. **Fond** : bleu très pâle / dégradé léger.
2. **Carte** blanche large, coins très arrondis, ombre douce.
3. **Icône succès** : cercle bleu + **coche** blanche, halo bleu clair, **deux petits points** décoratifs.
4. **Titre** : **« Superbe ! »** gras noir.
5. **Message** : « Votre carrousel a été publié avec succès sur O’Sport. » gris.
6. **Sous-titre** petit capitales gris : **« APERÇU DU CARROUSEL »**.
7. **Aperçu** image verticale arrondie (ex. coureur) ; overlay **« 1/5 »** + icône **carrousel** (carrés empilés) en haut à droite.
8. **Bouton principal** : **« Voir la publication »** — bleu, **œil** blanc + texte blanc.
9. **Bouton secondaire** : **« Retour à l’accueil »** — blanc, bord gris, **icône maison** + texte noir gras.

---

## Parcours utilisateur suggéré

```
sélection_de_médias_1 ou _2  →  (optionnel) camera_capture_mode
         ↓
nouvelle_publication_1 ou _2  →  Publier
         ↓
succès_de_publication_carrousel
```

La **caméra** peut aussi être l’**entrée** première (FAB → caméra), puis retour édition / composition.

---

## Tableau récapitulatif

| Dossier | Fonction principale |
|--------|----------------------|
| `sélection_de_médias_1` | Picker sombre, multi-sélection numérotée, Suivant (n), miniatures |
| `sélection_de_médias_2` | Picker sombre, Suivant, multi-select, bordure sélection |
| `camera_capture_mode` | Prise de vue LIVE / modes PHOTO etc. |
| `nouvelle_publication_1` | Légende, options, FB / IG / Twitter |
| `nouvelle_publication_2` | Idem + carrousel 1/3, FB / IG seulement |
| `succès_de_publication_carrousel` | Confirmation carrousel + Voir / Accueil |

---

## Notes pour l’implémentation

- Unifier **« Nouveau post »** (sélection) et **« Nouvelle Publication »** (composer) si une seule app est cible.
- Le succès parle explicitement de **carrousel** ; prévoir un équivalent **« Votre publication a été publiée »** pour un **média unique**.
- **Cross-post** : intégrations réelles Facebook / Instagram / X dépendent des **SDK** et **politiques** des stores ; les toggles sont une intention produit.
- Réutiliser les mêmes **composants carte** entre `nouvelle_publication_1` et `_2` avec props `mediaCount`, `carouselIndex`, `networks[]`.

---

*Document basé sur les fichiers `screen.png` du dossier **ecran post publication**.*


---

## Document source : `ecran profil utilisateur.md`

<!-- début : ecran profil utilisateur.md -->

# Écrans profil utilisateur — O’Sport

Ce document décrit **chaque maquette** du dossier `ecran profil utilisateur` : rôle du flux, éléments visuels et interactions prévues, **point par point**. Chaque capture est un `screen.png` dans un sous-dossier.

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


---

