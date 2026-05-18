# Authentification et sécurité compte

Préfixe : `/api/v1/auth`

Routes définies dans [`routes/api/v1/auth.php`](../../routes/api/v1/auth.php).

---

## POST /login

Connexion e-mail / mot de passe. Émet un token Sanctum.

| | |
|---|---|
| **Auth** | Aucune |
| **Throttle** | `auth-login` — 5/min (email + IP) |
| **Content-Type** | `application/json` |

### Corps

| Champ | Type | Règles |
|-------|------|--------|
| `email` | string | Requis, e-mail valide, max 255 (normalisé minuscules) |
| `password` | string | Requis |
| `accept_terms` | bool | Optionnel ; si présent, doit être accepté |

### Réponse 200

```json
{
  "message": "Connexion réussie.",
  "token": "1|abcdef...",
  "token_type": "Bearer",
  "user": { }
}
```

`user` : voir [README — Objet user](./README.md#objet-user-canonique).

### Erreurs métier

| Code | Condition |
|------|-----------|
| **422** | Identifiants incorrects → `errors.email` |
| **422** | E-mail non vérifié → message dédié sur `email` |
| **429** | Trop de tentatives |

### Notes RN

- Refuser la connexion si `email_verified_at` est `null` côté UX : rediriger vers l'écran OTP.
- Stocker `token` de façon sécurisée (ex. `expo-secure-store`).

---

## POST /logout

Révoque le token Bearer courant (cet appareil uniquement).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | 60/min |

### Réponse 200

```json
{
  "message": "Déconnexion réussie."
}
```

---

## POST /forgot-password

Demande un code OTP par e-mail (étape 1 mot de passe oublié).

| | |
|---|---|
| **Auth** | Aucune |
| **Throttle** | `auth-forgot-password` — 3/min |

### Corps

| Champ | Type | Règles |
|-------|------|--------|
| `email` | string | Requis, e-mail valide (minuscules) |

### Réponse 200

```json
{
  "message": "Si un compte est associé à cette adresse, un code de réinitialisation vient d'y être envoyé."
}
```

Message **identique** que l'e-mail existe ou non (anti-énumération).

---

## POST /forgot-password/reset

Valide le code OTP et définit le nouveau mot de passe.

| | |
|---|---|
| **Auth** | Aucune |
| **Throttle** | `auth-password-reset-otp` — 6/min |

Alias identique : `POST /forgot-password/update`.

### Corps

| Champ | Type | Règles |
|-------|------|--------|
| `email` | string | Requis, e-mail |
| `code` | string | Requis, exactement 6 chiffres |
| `password` | string | Requis, confirmé, min 8, majuscule + minuscule + chiffre + symbole |
| `password_confirmation` | string | Requis (alias accepté : `passwordconfirmation`) |

### Réponse 200

```json
{
  "message": "Votre mot de passe a été mis à jour. Vous pouvez vous connecter."
}
```

### Erreurs

| Code | Condition |
|------|-----------|
| **422** | Code invalide ou expiré → `errors.code` |
| **422** | Nouveau mot de passe identique à l'ancien |

Les sessions Sanctum existantes sont invalidées côté serveur.

---

## POST /register/credentials

Étape 1 inscription : création du compte + token.

| | |
|---|---|
| **Auth** | Aucune |
| **Throttle** | `register-credentials` — 5/min |

### Corps

| Champ | Type | Règles |
|-------|------|--------|
| `email` | string | Requis, unique |
| `password` | string | Requis, confirmé, règles de complexité |
| `password_confirmation` | string | Requis |
| `accept_terms` | bool | Requis, doit être accepté |
| `given_name` | string | Requis, max 120 |
| `family_name` | string | Requis, max 120 |
| `city` | string | Requis, max 120 |
| `latitude` | number | Requis, -90 à 90 |
| `longitude` | number | Requis, -180 à 180 |

### Réponse 201

```json
{
  "message": "Compte créé. Vérifiez votre email pour activer le compte.",
  "token": "2|...",
  "token_type": "Bearer",
  "user": { }
}
```

### Notes RN

Enchaîner le wizard : [register.md](./register.md). Un OTP de vérification e-mail est envoyé automatiquement.

---

## GET /register/handle-availability

Vérifie si un pseudo est disponible.

| | |
|---|---|
| **Auth** | Optionnelle (Bearer exclut le compte courant du test) |
| **Throttle** | 60/min |

### Query

| Paramètre | Règles |
|-----------|--------|
| `handle` | Requis, 3–32 car., `^[a-zA-Z0-9_]+$` |

### Réponse 200

```json
{
  "handle": "jean_dupont",
  "available": true
}
```

---

## GET /sports

Liste publique des sports (grille onboarding).

| | |
|---|---|
| **Auth** | Aucune |
| **Throttle** | 120/min |

### Réponse 200

```json
{
  "data": [
    {
      "id": 3,
      "name": "Football",
      "slug": "football",
      "practice_type": "collective",
      "avatar": "https://cdn.example.com/sports/football.png"
    }
  ]
}
```

---

## POST /email/verify

Vérifie l'e-mail du compte connecté avec un code OTP à 6 chiffres.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-email-verify` — 6/min |

### Corps

| Champ | Règles |
|-------|--------|
| `code` | Requis, 6 chiffres |

### Réponse 200

Si déjà vérifié :

```json
{
  "user": { }
}
```

Après vérification réussie :

```json
{
  "user": { }
}
```

(`email_verified_at` renseigné.)

### Erreurs

| Code | Condition |
|------|-----------|
| **422** | Code incorrect ou expiré → `errors.code` |

---

## POST /email/resend

Renvoie un OTP de vérification si l'e-mail n'est pas encore vérifié.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-email-resend` — 3/min |

### Corps

Aucun.

### Réponses

| Code | Condition |
|------|-----------|
| **200** | Déjà vérifié → `{ "message": "Votre adresse e-mail est déjà vérifiée." }` |
| **204** | Code renvoyé (corps vide) |

---

## POST /email/change/request

Demande un OTP sur la **nouvelle** adresse e-mail.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-email-change-request` — 3/min |

### Corps

| Champ | Règles |
|-------|--------|
| `email` | Requis, e-mail valide, unique, différent de l'actuel |

### Réponse 200

```json
{
  "message": "Un code de confirmation a été envoyé à votre nouvelle adresse e-mail."
}
```

---

## POST /email/change/verify

Confirme le changement d'e-mail avec le code reçu sur la nouvelle adresse.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-email-change-verify` — 6/min |

### Corps

| Champ | Règles |
|-------|--------|
| `email` | Requis (nouvelle adresse) |
| `code` | Requis, 6 chiffres |

### Réponse 200

```json
{
  "message": "Votre adresse e-mail a été mise à jour.",
  "user": { }
}
```

### Erreurs

| Code | Condition |
|------|-----------|
| **422** | Code invalide, expiré, ou e-mail déjà pris |

---

## POST /password/change

Change le mot de passe du compte connecté (écran paramètres, sans OTP).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-password-change` — 3/min |

### Corps

| Champ | Règles |
|-------|--------|
| `current_password` | Requis, doit correspondre au mot de passe actuel |
| `password` | Requis, confirmé, différent de `current_password`, règles de complexité |
| `password_confirmation` | Requis |

### Réponse 200

```json
{
  "message": "Votre mot de passe a été mis à jour. Veuillez vous reconnecter."
}
```

### Notes RN

Déconnecter l'utilisateur et supprimer le token local après succès.
