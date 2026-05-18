# Compte, profil et social

Préfixe : `/api/v1/auth`

Routes dans [`routes/api/v1/account.php`](../../routes/api/v1/account.php). Toutes exigent **Bearer Sanctum**.

---

## GET /user

Retourne l'utilisateur connecté (même format que login / register).

| | |
|---|---|
| **Auth** | Bearer |

### Réponse 200

```json
{
  "user": { }
}
```

Voir [README — Objet user](./README.md#objet-user-canonique).

### Notes RN

Appeler au démarrage de l'app (cold start) pour restaurer la session si un token est stocké.

---

## GET /upload-progress

Dernière progression d'upload / traitement d'images connue pour l'utilisateur connecté (cache Redis / fichier). **Secours** si Laravel Echo (RN) rate un événement Reverb — voir [realtime.md](./realtime.md).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-read` — 60/min (user id + IP) |

### Query

| Paramètre | Type | Description |
|-----------|------|-------------|
| `batch_key` | string, optionnel | Filtre sur le lot métier (`post-{id}`, `team-{id}`, etc.). Si le cache courant ne correspond pas → `data: null`. |

### Réponse 200

Aucune progression en cache :

```json
{
  "data": null
}
```

Progression disponible :

```json
{
  "data": {
    "user_id": 1,
    "batch_key": "post-510",
    "status": "progress",
    "percent": 67,
    "processed_jobs": 2,
    "total_jobs": 3,
    "pending_jobs": 1,
    "failed_jobs": 0,
    "progress_bar": "[████████████████░░░░░░░░] 67%",
    "batch_id": "9f3c2a1b-..."
  }
}
```

| Champ `status` | Signification |
|----------------|---------------|
| `progress` | Lot en cours |
| `completed` | Terminé (`percent` = 100) |
| `failed` | Échec du lot |

### Notes RN

- Intégration principale : **Laravel Echo** + `pusher-js`, canal `file.upload.progress.{userId}`, événement `.file.upload.progress` — [realtime.md](./realtime.md).
- Polling optionnel : cet endpoint toutes les 400–500 ms avec le même `batch_key` que `ImageProcessingEvent::$uniqueKey`.
- Si la route renvoie **404** après déploiement : `php artisan route:clear` côté serveur.

Voir aussi : [realtime.md — pipeline et dépannage](./realtime.md).

---

## PATCH /profile

Mise à jour partielle du profil (hors wizard inscription).

| | |
|---|---|
| **Auth** | Bearer |
| **Content-Type** | `application/json` ou `multipart/form-data` (si avatar) |

### Corps (tous optionnels, `sometimes`)

| Champ | Règles |
|-------|--------|
| `given_name` | string, max 120 ; requis si `family_name` envoyé |
| `family_name` | string, max 120 ; requis si `given_name` envoyé |
| `handle` | 3–32 car., `^[a-zA-Z0-9_]+$`, unique (sauf compte courant) |
| `birth_date` | date passée ou null |
| `bio` | string, max 2000 ou null |
| `is_private` | boolean |
| `avatar_url` | fichier image (jpeg/png/gif/webp) |
| `latitude` | -90 à 90 ou null |
| `longitude` | -180 à 180 ou null |
| `city` | string, max 120 ou null |
| `address_line` | string, max 255 ou null |

### Réponse 200

```json
{
  "user": { }
}
```

### Notes RN

L'avatar est traité de façon **asynchrone** : `avatar_url` dans la réponse peut ne pas refléter immédiatement le fichier uploadé.

---

## POST /follow

S'abonner à un utilisateur (follow accepté immédiatement).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-write` — 20/min |

### Corps

| Champ | Règles |
|-------|--------|
| `target_user_id` | Requis, entier, existe dans `users`, ≠ utilisateur courant |

### Réponse 200

```json
{
  "message": "Abonnement enregistré."
}
```

Opération idempotente (upsert).

---

## DELETE /follow

Se désabonner.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-write` — 20/min |

### Corps

| Champ | Règles |
|-------|--------|
| `target_user_id` | Requis, entier, existe |

### Réponse 200

```json
{
  "message": "Abonnement supprimé."
}
```

Silencieux si aucune relation n'existait.

---

## GET /follows/counts

Totaux followers / following (compte connecté).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-read` — 60/min |

### Réponse 200

```json
{
  "data": {
    "followers_count": 42,
    "following_count": 18
  }
}
```

Seuls les follows avec `status = accepted` sont comptés.

---

## GET /follows

Liste paginée par curseur (followers ou following).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-read` — 60/min |

### Query

| Paramètre | Règles |
|-----------|--------|
| `type` | Requis : `followers` ou `following` |
| `limit` | Optionnel, 1–100 (défaut 10) |
| `cursor` | Optionnel, chaîne base64 JSON `{ "fid": <follows.id> }` |

### Réponse 200

```json
{
  "data": [
    {
      "id": 5,
      "name": "Marie Martin",
      "email": "marie@example.com",
      "handle": "marie_m",
      "display_name": "Marie",
      "avatar_url": "https://...",
      "followed_at": "2026-04-01T10:00:00.000000Z",
      "am_i_following": true
    }
  ],
  "meta": {
    "next_cursor": "eyJmaWQiOjEyM30=",
    "has_more": true,
    "per_page": 10
  }
}
```

| `type` | `am_i_following` |
|--------|------------------|
| `following` | toujours `true` |
| `followers` | `true` si le compte connecté suit cette personne |

### Erreurs

| Code | Condition |
|------|-----------|
| **422** | Curseur invalide → `errors.cursor` |

---

## GET /users/search

Recherche de profils publics autour de la position du profil connecté (Typesense).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-read` — 60/min |

### Query

| Paramètre | Règles |
|-----------|--------|
| `q` | Optionnel, string, max 100 (défaut `*` = tous) |
| `radius_km` | Optionnel, 0.1–200 (défaut 100) |
| `per_page` | Optionnel, 1–50 (défaut 10) |
| `page` | Optionnel, 1–10000 (défaut 1) |

### Réponse 200

```json
{
  "data": [ ],
  "meta": {
    "found": 12,
    "out_of": 12,
    "page": 1,
    "next_page": 2,
    "per_page": 10,
    "search_time_ms": 3,
    "center": {
      "latitude": 45.75,
      "longitude": 4.85,
      "radius_km": 100
    }
  }
}
```

Les objets dans `data` proviennent de Typesense (champs indexés : id utilisateur, handle, display_name, avatar, distance, etc.).

### Erreurs

| Code | Condition |
|------|-----------|
| **422** | Profil sans latitude/longitude |
| **502** | Typesense indisponible |

---

## GET /users/{user}/profile

Profil public d'un autre utilisateur (`{user}` = id numérique).

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-read` — 60/min |

### Réponse 200

Même structure que `user`, avec :

- `am_i_following` (bool)
- pas de `email` sauf si `{user}` = compte connecté

### Erreurs

| Code | Condition |
|------|-----------|
| **403** | Compte privé sans relation de follow acceptée |
| **404** | Utilisateur inconnu |

---

## GET /notifications

Notifications en base (table `notifications`), 10 par page.

| | |
|---|---|
| **Auth** | Bearer |
| **Throttle** | `auth-follow-read` — 60/min |

### Query

| Paramètre | Règles |
|-----------|--------|
| `page` | Optionnel, entier ≥ 1 (défaut 1) |

### Réponse 200

```json
{
  "data": [
    {
      "id": "9b2c3d4e-...",
      "type": "App\\Notifications\\Comments",
      "data": { },
      "read_at": null,
      "created_at": "2026-05-10T14:00:00+00:00",
      "updated_at": "2026-05-10T14:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25
  }
}
```

### Notes RN

Le champ `data` est **spécifique à chaque type** de notification (like, commentaire, classement équipe, etc.). Parser selon `type` ou des clés connues dans `data`.

Types possibles (exemples) : `Comments`, `CommentLikeNotification`, `MatchResultLikeNotification`, `TeamTopRankChangeNotification`, etc.
