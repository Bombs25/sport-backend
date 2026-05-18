# Temps réel — progression d'upload

Lorsqu'un upload déclenche `ImageProcessingEvent` (posts, profil, équipes), le backend traite les images en queue `image-processing` et publie la progression sur :

1. **Reverb** — canal privé `file.upload.progress.{userId}` (temps réel, **intégration React Native**)
2. **Cache HTTP** — `GET /api/v1/auth/upload-progress` (secours si un événement est manqué)

Fichiers principaux :

| Rôle | Fichier |
|------|---------|
| Événement métier | [`app/Events/ImageProcessingEvent.php`](../../app/Events/ImageProcessingEvent.php) |
| Pipeline + broadcast | [`app/Listeners/ImageProcessingListener.php`](../../app/Listeners/ImageProcessingListener.php) |
| Diffusion Reverb | [`app/Events/FileUploadBroadcast.php`](../../app/Events/FileUploadBroadcast.php) (`ShouldBroadcastNow`) |
| Polling API | [`app/Http/Controllers/Api/V1/Upload/UploadProgressPollController.php`](../../app/Http/Controllers/Api/V1/Upload/UploadProgressPollController.php) |
| Référence web (debug) | [`resources/views/mobile/upload-progress.blade.php`](../../resources/views/mobile/upload-progress.blade.php) |
| Autorisation canal | [`routes/channels.php`](../../routes/channels.php) |
| Auth WebSocket | [`routes/api.php`](../../routes/api.php) — `POST /api/broadcasting/auth` |

Référence config web (Vite) : [`resources/js/echo.js`](../../resources/js/echo.js).

---

## React Native — Laravel Echo (recommandé)

L'app mobile s'abonne au canal **avant** le `POST` multipart et met à jour une barre de progression native (`percent`, `status`) depuis les événements Echo. **Pas de WebView** pour ce flux.

### Dépendances

| Contexte | Paquets | Import |
|----------|---------|--------|
| **Web** (Vite, cette app) | `laravel-echo` + `pusher-js` | `import Pusher from 'pusher-js'` — voir [`resources/js/echo.js`](../../resources/js/echo.js) |
| **React Native + Reverb** | `laravel-echo` + `pusher-js` | `import Pusher from 'pusher-js/react-native'` |

```bash
npm install laravel-echo pusher-js
```

> **`@pusher/pusher-websocket-react-native`** existe (SDK natif iOS/Android officiel Pusher), mais il cible **Pusher Channels cloud** (`cluster`) et **ne permet pas** de définir un `wsHost` custom pour un Reverb self-hosted. Pour O'Sport (Reverb sur votre serveur), utiliser **`pusher-js/react-native`** avec Laravel Echo — c'est le build React Native du même client que le web, avec `wsHost` / `wsPort`.

Exposer côté RN les mêmes valeurs que le backend (`REVERB_APP_KEY`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`) via votre config (`.env` / `app.config.js`).

> Sur émulateur Android, `localhost` du Mac ne fonctionne pas : utiliser l'IP LAN du poste ou `10.0.2.2` pour joindre Reverb.

### Exemple (hook / écran d'upload)

```javascript
import { useEffect, useRef, useState } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js/react-native';

const API_URL = 'https://api.osport.example.com'; // sans /api/v1/auth
const REVERB_KEY = process.env.EXPO_PUBLIC_REVERB_KEY;
const REVERB_HOST = process.env.EXPO_PUBLIC_REVERB_HOST;
const REVERB_PORT = Number(process.env.EXPO_PUBLIC_REVERB_PORT ?? 8080);
const REVERB_SCHEME = process.env.EXPO_PUBLIC_REVERB_SCHEME ?? 'https';

function createUploadEcho(token) {
  const pusherClient = new Pusher(REVERB_KEY, {
    wsHost: REVERB_HOST,
    wsPort: REVERB_PORT,
    wssPort: REVERB_PORT,
    forceTLS: REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    cluster: '', // Reverb self-hosted, pas de cluster Pusher cloud
    authorizer: (channel) => ({
      authorize: (socketId, callback) => {
        fetch(`${API_URL}/api/broadcasting/auth`, {
          method: 'POST',
          credentials: 'omit',
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
          }),
        })
          .then((res) => (res.ok ? res.json() : Promise.reject(res)))
          .then((data) => callback(null, data))
          .catch((err) => callback(err, null));
      },
    }),
  });

  return new Echo({
    broadcaster: 'reverb',
    client: pusherClient,
  });
}

export function useUploadProgress(token, userId) {
  const [percent, setPercent] = useState(0);
  const [status, setStatus] = useState('idle'); // idle | progress | completed | failed
  const echoRef = useRef(null);

  useEffect(() => {
    if (!token || !userId) {
      return undefined;
    }

    const echo = createUploadEcho(token);
    echoRef.current = echo;

    const channel = echo.private(`file.upload.progress.${userId}`);

    const onEvent = (payload) => {
      const p = Number(payload.percent ?? 0);
      if (payload.status === 'completed') {
        setPercent(100);
        setStatus('completed');
        return;
      }
      if (payload.status === 'failed') {
        setPercent(p);
        setStatus('failed');
        return;
      }
      setPercent(p);
      setStatus('progress');
    };

    channel.listen('.file.upload.progress', onEvent);
    channel.listen('file.upload.progress', onEvent);

    return () => {
      echo.leave(`file.upload.progress.${userId}`);
      echo.disconnect();
      echoRef.current = null;
    };
  }, [token, userId]);

  return { percent, status };
}

// Flux écran :
// 1. Monter le hook (abonnement actif)
// 2. POST multipart (post, profil, équipe)
// 3. Afficher percent / status dans l’UI native
// 4. Sur status === 'completed' → rafraîchir le fil ou naviguer
```

### Ordre UX (important)

1. Récupérer `user.id` (`GET /api/v1/auth/user`)
2. **S'abonner** au canal privé (Echo connecté)
3. Lancer le `POST` multipart
4. Mettre à jour l'UI à chaque événement `.file.upload.progress`

Si l'abonnement démarre après la fin du traitement, utiliser le **polling** (ci-dessous) avec le même `batch_key`.

### Clés de lot (`batch_key`)

Côté serveur, `ImageProcessingEvent::$uniqueKey` identifie le staging et le cache :

| Contexte | `uniqueKey` typique |
|----------|---------------------|
| Post | `post-{post_id}` |
| Équipe | `team-{team_id}` |
| Profil | selon l'appelant |
| Endpoint générique images | `images-{uuid}` |

Pour le poll HTTP, passer `?batch_key=post-510` si plusieurs uploads peuvent se chevaucher.

---

## GET /upload-progress (polling secours)

Dernière progression en cache. Détail : [account.md — GET /upload-progress](./account.md#get-upload-progress).

| | |
|---|---|
| **URL** | `GET /api/v1/auth/upload-progress` |
| **Auth** | `Authorization: Bearer {token}` |
| **Query** | `batch_key` (optionnel) |

Exemple RN (après le multipart ou si WebSocket coupé) :

```javascript
async function pollUploadProgress(token, batchKey) {
  const qs = batchKey ? `?batch_key=${encodeURIComponent(batchKey)}` : '';
  const res = await fetch(`${API_URL}/api/v1/auth/upload-progress${qs}`, {
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
  });
  if (!res.ok) return null;
  const { data } = await res.json();
  return data; // null | { percent, status, batch_key, ... }
}
```

En cas de **404**, exécuter `php artisan route:clear` (cache de routes obsolète).

---

## Authentification canal privé

| | |
|---|---|
| **Endpoint** | `POST /api/broadcasting/auth` |
| **Auth** | `Authorization: Bearer {token}` |
| **CSRF** | Non requis (exception dans `bootstrap/app.php`) |
| **Cookies** | **Ne pas** envoyer (`credentials: 'omit'`) — sinon Sanctum peut authentifier un autre user → **403** |
| **Corps** | `channel_name`, `socket_id` |

Route sans middleware Sanctum « stateful » : [`routes/api.php`](../../routes/api.php).

---

## Événement broadcast (Reverb)

| | |
|---|---|
| **Canal** | `private-file.upload.progress.{userId}` |
| **Nom événement** | `file.upload.progress` |
| **Écoute Echo** | `.file.upload.progress` (point initial **obligatoire** avec `broadcastAs`) |
| **Secours** | `file.upload.progress` (sans point) |

Diffusion **synchrone** (`ShouldBroadcastNow`) : pas besoin de worker `post_notifications` pour la progression.

### Payload broadcast

```json
{
  "user_id": 1,
  "status": "progress",
  "percent": 45,
  "processed_jobs": 2,
  "total_jobs": 5,
  "pending_jobs": 3,
  "failed_jobs": 0,
  "progress_bar": "[██████████░░░░░░░░░░░░░░] 45%",
  "batch_id": "uuid-du-lot-laravel-bus"
}
```

### Payload cache / poll (`data`)

Même champs, plus `batch_key` :

```json
{
  "batch_key": "post-510",
  "user_id": 1,
  "status": "progress",
  "percent": 45
}
```

| `status` | Signification |
|----------|----------------|
| `progress` | Lot en cours |
| `completed` | Terminé (`percent` = 100) |
| `failed` | Échec du lot |

Le cache `upload-progress:latest:{userId}` reste disponible **30 minutes** après `completed`.

---

## WebView de référence (optionnel)

Page de test / debug navigateur — **non utilisée par l'app RN en production**.

| | |
|---|---|
| **URL** | `GET {APP_URL}/mobile/upload-progress?token={sanctum_token}` |
| **Query** | `theme`, `batch_key`, `debug=1` |

Utile pour valider Reverb dans Chrome DevTools. La page utilise Echo + poll et expose des `postMessage` si elle est chargée dans une WebView tierce ; l'intégration produit reste **Echo natif** ci-dessus.

---

## Prérequis serveur

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_KEY=...
REVERB_HOST=...
REVERB_PORT=8080
REVERB_SCHEME=http
```

```bash
php artisan reverb:start
php artisan queue:work --queue=image-processing
php artisan route:clear   # si GET /upload-progress renvoie 404
```

```bash
php artisan route:list --path=upload-progress
```

---

## Dépannage

| Symptôme | Cause probable | Action |
|----------|----------------|--------|
| Aucun événement RN | Reverb injoignable depuis l'appareil | IP/host corrects ; port ouvert ; `reverb:start` |
| **403** sur `broadcasting/auth` | Cookies session ≠ Bearer | `credentials: 'omit'` ; Bearer uniquement |
| `GET .../upload-progress` **404** | Cache routes obsolète | `php artisan route:clear` |
| Progression dans les logs, rien en app | Abonnement après la fin du lot | S'abonner **avant** le POST ; poll + `batch_key` |
| `EADDRINUSE` :8080 | Deux Reverb | Une seule instance |
