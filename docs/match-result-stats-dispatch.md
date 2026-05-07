# Documentation — Dispatch des stats après validation de score

## Contexte

Dans `MatchResultService`, après validation d'un score (`decision = validate`), le service déclenche le job de statistiques via :

- `ApplyValidatedMatchResultStatsJob::dispatch(...)->afterCommit()`

Cette logique est appelée à la fin de `respondToMatchResult()`, une fois :

- `match_results.status = validated`
- `match_events.status = finished`

## Pourquoi `afterCommit()`

`afterCommit()` garantit que le job n'est poussé dans la queue **qu'après le commit SQL** de la transaction courante.

Cela évite les incohérences du type :

- job exécuté alors que `match_results` n'est pas encore persisté,
- job basé sur un état intermédiaire.

## Données envoyées au job

Le service transmet au job :

- `homeTeamId`
- `awayTeamId`
- `homeScore`
- `awayScore`

Le job reconstitue ensuite le `sport_id` et applique la logique de points/statistiques.

## Responsabilités du job `ApplyValidatedMatchResultStatsJob`

1. Charger l'état "avant" des équipes concernées (cache-first sur `app_main_cache`).
2. Mettre à jour les stats (`stats`) des 2 équipes dans une transaction.
3. Recalculer l'état "après" (rang + moyenne).
4. Mettre à jour le cache.
5. Déclencher les notifications (DB + push) selon les règles métier :
   - entrée/sortie top 3,
   - progression moyenne,
   - notification sport via `NotifySportTopRankChangeJob`.

## Process détaillé de bout en bout

### 1) Validation du score côté service

Dans `MatchResultService::respondToMatchResult()` :

- la réponse adverse est validée,
- `match_results.status` passe à `validated`,
- `match_events.status` passe à `finished`,
- puis le job stats est dispatché avec `afterCommit()`.

Tant que la transaction n'est pas commitée, le job ne part pas.

### 2) Entrée dans `ApplyValidatedMatchResultStatsJob`

Le job reçoit :

- `homeTeamId`,
- `awayTeamId`,
- `homeScore`,
- `awayScore`.

Ensuite il charge le sport associé (`sport_id`, `slug`) pour récupérer la règle de points.

### 3) Snapshot "before" (cache-first)

Avant toute mise à jour :

- le job tente de lire le snapshot de chaque équipe dans `app_main_cache`,
- clé utilisée : `team:stats:snapshot:sport:{sportId}:team:{teamId}`,
- si le cache est vide pour une équipe, il recharge la donnée depuis la base puis la stocke.

Le snapshot contient :

- `point_count`,
- `rank` (classement dans le sport),
- `average` (moyenne sur 20).

### 4) Mise à jour transactionnelle des stats

Les updates des deux équipes (home + away) sont exécutés dans **une seule transaction**.

Selon le score :

- nul : incrément `draw_count` des deux équipes,
- victoire home : `victory_count` home + `defeat_count` away,
- victoire away : `defeat_count` home + `victory_count` away.

Pour chaque équipe :

- `insertOrIgnore` crée la ligne `stats` si absente,
- `lockForUpdate()` sécurise la ligne,
- update atomique du compteur + `point_count`.

### 5) Snapshot "after" et refresh cache

Après commit transaction :

- le job recalcule le snapshot des 2 équipes,
- réécrit le cache de chaque équipe avec les nouvelles valeurs.

### 6) Détection des changements de classement

Le job compare `before` vs `after` :

- entrée/sortie top 3,
- franchissement de seuils de moyenne (>= 5, >= 10, >= 15).

#### Règles top 3

- **Équipe qui entre top 3** : notification membres de l'équipe.
- **Entrée top 3 uniquement** : dispatch `NotifySportTopRankChangeJob` pour informer toutes les équipes du même sport.
- **Équipe qui sort top 3** : notification membres de l'équipe avec la nouvelle position.

### 7) Notifications de progression moyenne

Quand un seuil est franchi :

- notification aux membres de l'équipe,
- message basé sur la **nouvelle position dans le classement** (et non la note).

### 8) Push notifications (FCM / Expo)

En plus des notifications database :

- récupération des tokens via `User::routeNotificationForFcm()`,
- filtrage des tokens valides,
- envoi via `ExpoPushService`,
- payload JSON aligné avec la notification concernée.

### 9) Job secondaire sport

`NotifySportTopRankChangeJob` :

- récupère les membres actifs de toutes les équipes du `sport_id` + créateurs,
- envoie notification database,
- envoie push Expo,
- tourne sur la connection `app_main_cache`, queue `sport-rank-notifications`.

### 10) Seeder de reconstruction

`StatsFromMatchResultsSeeder` permet de recalculer `stats` depuis `match_results` (`status = validated`) :

- agrégation home/away,
- application du même barème de points,
- `upsert` sur (`team_id`, `sport_id`).

## Queues concernées

- `ApplyValidatedMatchResultStatsJob` : queue par défaut (`default`) sauf override explicite.
- `NotifySportTopRankChangeJob` : connection `app_main_cache` + queue `sport-rank-notifications`.

## Règle de points (actuelle)

Mapping utilisé :

- victoire = 3
- défaite = 0
- nul = 1 pour `football` / `basketball`, sinon 0

Le seeder `StatsFromMatchResultsSeeder` applique la même logique pour reconstruire `stats` à partir de `match_results`.

## Resume non technique

Quand un score de match est confirme par les deux equipes, l'application met a jour automatiquement les statistiques des equipes concernees.

Concretement, cela permet de :

- recalculer les points et le classement,
- mettre a jour la moyenne de performance,
- prevenir les membres quand leur equipe entre/sort du top 3,
- prevenir aussi quand la progression moyenne franchit un cap important.

Tout est fait de maniere fiable : les mises a jour ne partent qu'une fois la validation du score bien enregistree en base.

