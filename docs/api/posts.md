# Posts, fils et interactions

Préfixe : `/api/v1/auth`

Routes dans [`routes/api/v1/posts.php`](../../routes/api/v1/posts.php). Toutes exigent **Bearer Sanctum**.

## Types de publication (`post_type`)

| Valeur | Table | Usage |
|--------|-------|--------|
| `regular` | `posts` | Posts utilisateur classiques |
| `automatic` | `match_results` | Résultats de match validés (fil « matchs ») |

Utilisé dans les endpoints like, commentaires et réponses.

---

## GET /posts/feed

Fil des **résultats de match** (`match_results` validés) publiés par les personnes suivies, hors contenus déjà vus.

| | |
|---|---|
| **Throttle** | 60/min |
| **Nom de route** | `api.v1.auth.posts.feed` |

### Query

| Paramètre | Règles |
|-----------|--------|
| `viewed_post_ids` | Optionnel ; **chaîne** = `encodeURIComponent(JSON.stringify([12, 34]))` — max 500 ids, chaque id existe dans `match_results` |
| `limit` | Optionnel, 1–10000 (validé mais **non utilisé** par le contrôleur) |

### Réponse 200

```json
{
  "data": [
    {
      "id": 50,
      "match_event_id": 100,
      "status": "validated",
      "home_score": 2,
      "away_score": 1,
      "total_comments": 3,
      "total_likes": 12,
      "submitted_by_user_id": 1,
      "submitted_at": "2026-05-10T18:00:00.000000Z",
      "validated_at": "2026-05-10T20:00:00.000000Z",
      "scheduled_at": "2026-05-10T15:00:00.000000Z",
      "venue": "Stade X",
      "match_event_status": "finished",
      "home_team_id": 1,
      "home_team_name": "Équipe A",
      "home_team_logo_url": "https://...",
      "away_team_id": 2,
      "away_team_name": "Équipe B",
      "away_team_logo_url": "https://...",
      "tag": "amis",
      "viewer_has_liked": false
    }
  ],
  "count": 1
}
```

`tag` : stratégie de feed (`amis`, `centre_interet`, etc.). Les lignes « centre d'intérêt » peuvent inclure `distance_km`.

### Notes RN

```javascript
const viewed = encodeURIComponent(JSON.stringify(viewedIds));
fetch(`/api/v1/auth/posts/feed?viewed_post_ids=${viewed}`, { headers });
```

Ne pas envoyer `viewed_post_ids[]` en tableau query natif.

---

## GET /posts/regular/feed

Fil des **posts réguliers** (`posts`), même logique d'exclusion des vus.

| | |
|---|---|
| **Throttle** | 60/min |
| **Nom de route** | `api.v1.auth.posts.regular.feed` |

### Query

Identique au fil matchs, mais les ids sont validés contre `posts.id`.

### Réponse 200

```json
{
  "data": [
    {
      "id": 12,
      "publication_type": "regular",
      "user_id": 1,
      "body": "Super match !",
      "visibility": "public",
      "status": "published",
      "media_count": 2,
      "total_likes": 5,
      "total_comments": 1,
      "total_shares": 0,
      "published_at": "2026-05-10T12:00:00.000000Z",
      "created_at": "...",
      "updated_at": "...",
      "author_name": "Jean Dupont",
      "author_display_name": "Jean",
      "author_handle": "jean_dupont",
      "author_avatar_url": "https://...",
      "author_avatar_blurhash": null,
      "viewer_has_liked": false,
      "tag": "amis",
      "media": [
        { "id": 1, "position": 0, "path": "https://...", "blurhash": null, "alt_text": null }
      ]
    }
  ],
  "count": 1
}
```

---

## POST /posts

Crée un post régulier.

| | |
|---|---|
| **Throttle** | 30/min |
| **Content-Type** | `multipart/form-data` |

### Corps

| Champ | Règles |
|-------|--------|
| `body` | Optionnel, max 5000 ; requis si pas de `media` |
| `visibility` | Optionnel : `public`, `followers` (défaut `public`) |
| `media` | Optionnel, tableau max 3 fichiers ; requis si pas de `body` |
| `media.*` | Fichier image |

### Réponse 201

```json
{
  "data": {
    "id": 12,
    "user_id": 1,
    "body": "Texte",
    "visibility": "public",
    "status": "published",
    "media_count": 1,
    "total_likes": 0,
    "total_comments": 0,
    "total_shares": 0,
    "published_at": "...",
    "media": []
  },
  "message": "Post publié."
}
```

Les médias peuvent être traités en arrière-plan : `media` vide dans la réponse immédiate.

---

## POST /posts/{post_id}/likes

Like ou dislike d'une publication (match ou post).

### Corps

| Champ | Règles |
|-------|--------|
| `post_type` | Requis : `regular` ou `automatic` (défaut `regular` si omis) |
| `post_id` | Fourni par l'URL ; doit exister dans la table correspondante |
| `action` | Requis : `like` ou `dislike` |

### Réponse 202

```json
{
  "message": "Traitement du like/dislike du résultat en cours."
}
```

### Notes RN

Traitement **asynchrone** : attendre ~1–2 s avant de mettre à jour l'UI ou recharger le fil.

---

## POST /posts/{post_id}/comments

Ajoute un commentaire.

### Corps

| Champ | Règles |
|-------|--------|
| `post_type` | `regular` ou `automatic` (défaut `regular`) |
| `commentaire` | Requis, max 5000 |

### Réponse 202

```json
{
  "message": "Commentaire en cours de traitement."
}
```

---

## GET /posts/comments

Liste paginée des commentaires (endpoint global, pas sous `{post_id}`).

### Query

| Paramètre | Règles |
|-----------|--------|
| `publication_type` | Requis : `regular` ou `automatic` (alias accepté : `post_type`) |
| `publication_id` | Requis, entier ≥ 1 (alias : `post_id`) |
| `page` | Optionnel, ≥ 1 (défaut 1) |

Si `publication_type` et `post_type` sont tous deux envoyés, ils doivent correspondre.

### Réponse 200

```json
{
  "data": [
    {
      "id": 1,
      "comment_id": 1,
      "publication_id": 12,
      "publication_type": "regular",
      "content": "Bravo !",
      "user_id": 2,
      "user_name": "Marie Martin",
      "user_display_name": "Marie",
      "user_handle": "marie_m",
      "user_avatar_url": "https://...",
      "likes_count": 0,
      "responses_count": 1,
      "created_at": "...",
      "viewer_has_liked": false
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 1,
      "last_page": 1
    }
  }
}
```

---

## POST /posts/{post_id}/comments/{comment_id}/likes

Like / dislike sur un commentaire.

### Corps

| Champ | Règles |
|-------|--------|
| `post_type` | Requis |
| `action` | Requis : `like` ou `dislike` |

### Réponse 202

```json
{
  "message": "Traitement du like/dislike en cours."
}
```

---

## POST /posts/{post_id}/comments/{comment_id}/responses

Réponse à un commentaire.

### Corps

| Champ | Règles |
|-------|--------|
| `post_type` | Requis |
| `response` | Requis, max 5000 |
| `responded_to_who` | Optionnel, max 32 ; doit exister comme `handle` dans `user_profiles` |
| `is_reponse_to_main_comment` | Optionnel, boolean (défaut `true`) |

### Réponse 202

```json
{
  "message": "Réponse au commentaire en cours de traitement."
}
```

---

## GET /posts/{post_id}/comments/{comment_id}/responses

Liste des réponses (paginée, 10 par page).

### Query

| Paramètre | Règles |
|-----------|--------|
| `post_type` | Requis |
| `page` | Optionnel (défaut 1) |

### Réponse 200

```json
{
  "data": [
    {
      "id": 1,
      "comment_id": 10,
      "response": "Merci !",
      "is_reponse_to_main_comment": true,
      "responded_to_who": "marie_m",
      "user_id": 1,
      "user_name": "Jean Dupont",
      "user_display_name": "Jean",
      "user_handle": "jean_dupont",
      "user_avatar_url": "https://...",
      "likes_count": 0,
      "created_at": "...",
      "viewer_has_liked": false
    }
  ],
  "meta": {
    "pagination": { "current_page": 1, "per_page": 10, "total": 1, "last_page": 1 }
  }
}
```

---

## POST /posts/{post_id}/comments/{comment_id}/responses/{response_id}/likes

Like / dislike sur une réponse.

### Corps

`post_type`, `action` (`like` | `dislike`).

### Réponse 202

```json
{
  "message": "Traitement du like/dislike de la réponse en cours."
}
```

---

## DELETE /posts/{post_id}/comments/{comment_id}/responses/{response_id}

Supprime une réponse (auteur uniquement).

### Query / corps

`post_type` requis.

### Réponse 200

```json
{
  "message": "Réponse supprimée."
}
```

### Erreurs

| Code | Condition |
|------|-----------|
| **403** | Pas l'auteur |
| **422** | Réponse ou commentaire introuvable |

---

## DELETE /posts/{post_id}/comments/{comment_id}

Supprime un commentaire (auteur uniquement).

### Query / corps

`post_type` requis.

### Réponse 202

```json
{
  "message": "Suppression du commentaire en cours de traitement."
}
```

### Erreurs

| Code | Condition |
|------|-----------|
| **403** | Pas l'auteur |
| **422** | Commentaire introuvable |

---

## Récapitulatif statuts HTTP

| Endpoint | Succès | Async |
|----------|--------|-------|
| GET feeds | 200 | — |
| POST /posts | 201 | médias |
| POST likes | 202 | oui |
| POST commentaires / réponses | 202 | oui |
| GET listes | 200 | — |
| DELETE réponse | 200 | — |
| DELETE commentaire | 202 | oui |
