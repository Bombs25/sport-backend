---
name: osport-spec-ecran-auth
description: "Spec maquettes O’Sport — connexion, inscription, OTP, dossier `ecran de connection et d'inscription`."
license: MIT
metadata:
  author: documentation-markdown
---

# Écrans de connexion et d’inscription — O’Sport

## Orientation

- Décrire le dossier maquettes `ecran de connection et d'inscription` : rôle du flux, UI, interactions ; listes numérotées par zone ou écran.
- Captures attendues : `screen.png` par sous-dossier.

## Cohérence d’abord

- Croiser avec `osport-design-complet-google-stitch.md` et `osport-guide-agents-synthese-design.md` avant d’inférer seul règles métier ou API.
- Signaler les **doublons** entre dossiers maquettes dans le texte ou une issue produit.

## Référence rapide

1. **Vue d’ensemble du parcours** — périmètre.
2. **Sections numérotées** — une maquette ou groupe d’éléments par section.

## Comment appliquer

1. Lire **Vue d’ensemble du parcours**.
2. Parcourir les sections **numérotées** pour spec UI, recette test ou brief intégration.

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
