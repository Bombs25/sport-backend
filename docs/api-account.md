# API Compte (`/api/v1/auth/*`, Sanctum)

Routes définies dans `routes/api/v1/account.php`. Toutes nécessitent `Authorization: Bearer {token}`.

## Utilisateur courant

| Méthode | Route | Description |
|---------|-------|-------------|
| `GET` | `/auth/user` | Profil connecté (même JSON que login) |

## Profil

| Méthode | Route | Body | Réponse |
|---------|-------|------|---------|
| `PATCH` | `/auth/profile` | multipart ou JSON : `given_name`, `family_name`, `handle`, `birth_date`, `bio`, `is_private`, `city`, `latitude`, `longitude`, `address_line`, `avatar_url` (fichier) | `{ user }` |
| `GET` | `/auth/users/{id}/profile` | — | `{ user }` sans `email` ; `am_i_following` ; 403 si privé |

## Follow

| Méthode | Route | Body / query | Réponse |
|---------|-------|--------------|---------|
| `POST` | `/auth/follow` | `{ target_user_id }` | `{ message }` |
| `DELETE` | `/auth/follow` | `{ target_user_id }` | `{ message }` |
| `GET` | `/auth/follows/counts` | — | `{ data: { followers_count, following_count } }` |
| `GET` | `/auth/follows` | `type=followers\|following`, `limit`, `cursor` | `{ data[], meta: { next_cursor, has_more, per_page } }` |

## Recherche & notifications

| Méthode | Route | Query | Réponse |
|---------|-------|-------|---------|
| `GET` | `/auth/users/search` | `q`, `radius_km`, `page`, `per_page` | `{ data[], meta }` (Typesense) |
| `GET` | `/auth/notifications` | `page` | `{ data[], meta }` (10 / page) |
| `GET` | `/auth/upload-progress` | `batch_key` (optionnel) | `{ data }` ou `{ data: null }` |

## Front-end (RTK Query)

Implémentation : `osport-app/src/store/api/accountApi.ts`  
Types : `osport-app/src/types/account.ts`
