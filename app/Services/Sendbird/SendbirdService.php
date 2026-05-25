<?php

namespace App\Services\Sendbird;

use App\Models\User;
use App\Support\PublicImageUrl;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Intégration Sendbird (Platform API). O'Sport ne gère PAS le temps réel : ce service
 * se contente de provisionner l'utilisateur Sendbird et de délivrer des tokens de
 * session courts. Les messages / canaux vivent chez Sendbird (schéma §1.6).
 *
 * Le token API (Master) reste exclusivement côté serveur ; l'app ne reçoit qu'un
 * `session_token` à durée de vie limitée.
 */
class SendbirdService
{
    public function __construct(
        private readonly ?string $appId,
        private readonly ?string $apiToken,
        private readonly ?string $baseUrl,
    ) {}

    /** App ID public Sendbird, renvoyé à l'app mobile pour initialiser le SDK. */
    public function appId(): ?string
    {
        return $this->appId;
    }

    /** Identifiant Sendbird stable dérivé de l'id O'Sport. */
    public function sendbirdUserIdFor(int $userId): string
    {
        return "osport_{$userId}";
    }

    /**
     * Garantit que l'utilisateur existe côté Sendbird et que la ligne
     * `sendbird_accounts` est à jour. Renvoie le `sendbird_user_id`.
     *
     * La Platform API distingue création (`POST /v3/users`) et mise à jour
     * (`PUT /v3/users/{id}`, qui échoue en 400201 si l'utilisateur n'existe pas).
     * On tente la mise à jour, et on bascule sur la création si l'utilisateur est
     * introuvable — idempotent dans les deux sens.
     */
    public function ensureUser(User $user): string
    {
        $this->provisionUser($user);

        return $this->sendbirdUserIdFor((int) $user->id);
    }

    /**
     * Provisionne l'utilisateur Sendbird et renvoie la réponse brute de l'API
     * (utile pour récupérer un session token émis inline).
     *
     * @return array<string, mixed>
     */
    private function provisionUser(User $user, bool $issueSessionToken = false): array
    {
        $sendbirdUserId = $this->sendbirdUserIdFor((int) $user->id);
        $profile = DB::table('user_profiles')
            ->where('user_id', $user->id)
            ->first(['display_name', 'avatar_url']);

        $payload = [
            'user_id' => $sendbirdUserId,
            'nickname' => $this->resolveNickname($user, $profile),
            // URL absolue : Sendbird stocke ce `profile_url` tel quel et l'app le
            // charge directement — un chemin relatif `/storage/...` serait inexploitable.
            'profile_url' => PublicImageUrl::from($profile->avatar_url ?? null) ?? '',
            'issue_session_token' => $issueSessionToken,
        ];

        $response = $this->client()->put('/v3/users/'.$sendbirdUserId, $payload);

        // 400201 = "User not found" : l'utilisateur n'existe pas encore -> on le crée.
        if (! $response->successful() && (int) ($response->json('code') ?? 0) === 400201) {
            $response = $this->client()->post('/v3/users', $payload);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Sendbird ensureUser failed: '.$response->body());
        }

        $exists = DB::table('sendbird_accounts')->where('user_id', $user->id)->exists();
        if ($exists) {
            DB::table('sendbird_accounts')
                ->where('user_id', $user->id)
                ->update([
                    'sendbird_user_id' => $sendbirdUserId,
                    'sendbird_synced_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('sendbird_accounts')->insert([
                'user_id' => (int) $user->id,
                'sendbird_user_id' => $sendbirdUserId,
                'sendbird_synced_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (array) $response->json();
    }

    /**
     * Provisionne l'utilisateur et renvoie un token de session frais émis par
     * Sendbird *inline* lors du `POST/PUT /v3/users` (`issue_session_token`).
     *
     * @return array{token: string, expires_at: int}
     */
    public function ensureUserWithSessionToken(User $user): array
    {
        $body = $this->provisionUser($user, issueSessionToken: true);

        $session = $body['session_tokens'][0] ?? null;
        if (! is_array($session) || empty($session['session_token'])) {
            throw new RuntimeException('Sendbird n\'a pas renvoyé de session token.');
        }

        return [
            'token' => (string) $session['session_token'],
            'expires_at' => (int) ($session['expires_at'] ?? 0),
        ];
    }

    /**
     * Crée (ou réutilise, via `is_distinct`) un canal 1-à-1 entre deux utilisateurs.
     * Provisionne les DEUX comptes Sendbird au préalable : le destinataire n'a pas
     * forcément encore ouvert la messagerie. Renvoie l'URL du canal.
     */
    public function createDistinctChannel(User $current, User $target): string
    {
        $currentSendbirdId = $this->ensureUser($current);
        $targetSendbirdId = $this->ensureUser($target);

        $response = $this->client()->post('/v3/group_channels', [
            'user_ids' => [$currentSendbirdId, $targetSendbirdId],
            'is_distinct' => true,
            // Pas de cover de canal : l'app affiche l'avatar du membre distant.
            // Sans ça, Sendbird assigne une image d'exemple `static.sendbird.com/...`.
            'cover_url' => '',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Sendbird createDistinctChannel failed: '.$response->body());
        }

        return (string) ($response->json()['channel_url'] ?? '');
    }

    /**
     * Configure le webhook Sendbird via la Platform API (contourne le dashboard).
     * Active le webhook, fixe l'URL et la liste des événements souscrits.
     *
     * `include_members` est activé : le payload doit inclure les membres du canal
     * avec leur statut `is_online`, indispensable pour cibler les destinataires
     * hors ligne (cf. SendbirdMessagePushService).
     *
     * @param  array<int, string>  $events  Catégories d'événements (ex. `group_channel:message_send`).
     * @return array<string, mixed> Réponse de l'API (état du webhook).
     */
    public function configureWebhook(string $url, array $events): array
    {
        $response = $this->client()->put('/v3/applications/settings/webhook', [
            'enabled' => true,
            'url' => $url,
            'enabled_events' => array_values($events),
            'include_members' => true,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Sendbird configureWebhook failed: '.$response->body());
        }

        return (array) $response->json();
    }

    /**
     * Lit la configuration webhook actuelle de l'application.
     *
     * @return array<string, mixed>
     */
    public function webhookConfiguration(): array
    {
        $response = $this->client()->get('/v3/applications/settings/webhook');

        if (! $response->successful()) {
            throw new RuntimeException('Sendbird webhookConfiguration failed: '.$response->body());
        }

        return (array) $response->json();
    }

    /**
     * Liste paginée des group channels. `$token` est le `next` renvoyé par la page précédente
     * (null pour la première page). `show_empty=true` inclut les canaux sans messages.
     *
     * @return array{channels: array<int, array<string, mixed>>, next: ?string}
     */
    public function listGroupChannels(?string $token = null, int $limit = 100): array
    {
        $response = $this->client()->get('/v3/group_channels', array_filter([
            'limit' => $limit,
            'token' => $token,
            'show_empty' => 'true',
        ]));

        if (! $response->successful()) {
            throw new RuntimeException('Sendbird listGroupChannels failed: '.$response->body());
        }

        return [
            'channels' => (array) ($response->json('channels') ?? []),
            'next' => $response->json('next') ?: null,
        ];
    }

    /**
     * Supprime un group channel (et ses messages). Idempotent : un canal déjà absent
     * (400201 / 400108) n'est pas considéré comme une erreur.
     */
    public function deleteGroupChannel(string $channelUrl): void
    {
        $response = $this->client()->delete('/v3/group_channels/'.$channelUrl);

        if ($response->successful()) {
            return;
        }

        $code = (int) ($response->json('code') ?? 0);
        if (in_array($code, [400201, 400108], true)) {
            return;
        }

        throw new RuntimeException('Sendbird deleteGroupChannel failed: '.$response->body());
    }

    /**
     * Liste paginée des utilisateurs Sendbird.
     *
     * @return array{users: array<int, array<string, mixed>>, next: ?string}
     */
    public function listUsers(?string $token = null, int $limit = 100): array
    {
        $response = $this->client()->get('/v3/users', array_filter([
            'limit' => $limit,
            'token' => $token,
        ]));

        if (! $response->successful()) {
            throw new RuntimeException('Sendbird listUsers failed: '.$response->body());
        }

        return [
            'users' => (array) ($response->json('users') ?? []),
            'next' => $response->json('next') ?: null,
        ];
    }

    /**
     * Supprime un utilisateur Sendbird. Idempotent : utilisateur déjà absent (400201)
     * n'est pas une erreur.
     */
    public function deleteUser(string $sendbirdUserId): void
    {
        $response = $this->client()->delete('/v3/users/'.$sendbirdUserId);

        if ($response->successful()) {
            return;
        }

        if ((int) ($response->json('code') ?? 0) === 400201) {
            return;
        }

        throw new RuntimeException('Sendbird deleteUser failed: '.$response->body());
    }

    private function client(): PendingRequest
    {
        if (empty($this->baseUrl) || empty($this->apiToken)) {
            throw new RuntimeException('Sendbird n\'est pas configuré (SENDBIRD_API_BASE_URL / SENDBIRD_API_TOKEN).');
        }

        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->timeout(10)
            ->acceptJson()
            ->withHeaders(['Api-Token' => $this->apiToken]);
    }

    private function resolveNickname(User $user, ?object $profile): string
    {
        $displayName = $profile->display_name ?? null;

        return trim((string) ($displayName ?: $user->name)) ?: "Membre {$user->id}";
    }
}
