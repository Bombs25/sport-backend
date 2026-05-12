<?php

namespace App\Services\Register;

use App\Models\User;
use App\Repositories\RegisterUserReader;
use App\Support\PublicImageUrl;

/**
 * Ce qu’il fait : assemble le **JSON « user »** renvoyé au client après chaque étape d’inscription,
 * au login et sur `GET /auth/user` (même forme de réponse pour React Native).
 *
 * Pourquoi : une seule structure de payload évite des DTO dupliqués ; il délègue les lectures à
 * `RegisterUserReader` pour rester cohérent avec la règle « agrégation via Query Builder ».
 */
class RegisterUserPayloadBuilder
{
    public function __construct(
        private RegisterUserReader $reader,
    ) {}

    /**
     * Profil d'un utilisateur cible pour un observateur connecté (e-mail masqué sauf si c'est soi-même).
     * Comptes privés : accès refusé sauf lien de follow accepté (dans un sens ou l'autre) ou propriétaire.
     *
     * @return array<string, mixed>
     */
    public function buildForViewer(User $viewer, int $targetUserId): array
    {
        if ($viewer->id === $targetUserId) {
            $payload = $this->build($viewer);
            $payload['am_i_following'] = false;

            return $payload;
        }

        $row = $this->reader->userWithProfile($targetUserId);

        if ($row === null) {
            abort(404);
        }

        $isPrivate = (bool) $row->is_private;

        if ($isPrivate && ! $this->reader->hasAnyAcceptedFollowBetween($viewer->id, $targetUserId)) {
            abort(403, __('Ce profil est privé.'));
        }

        $target = User::query()->findOrFail($targetUserId);

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'am_i_following' => $this->reader->viewerFollowsTargetAccepted($viewer->id, $targetUserId),
            'email_verified_at' => $target->email_verified_at?->toIso8601String(),
            'created_at' => $target->created_at?->toIso8601String(),
            'profile' => [
                'display_name' => $row->display_name,
                'handle' => $row->handle,
                'bio' => $row->bio,
                'avatar_url' => PublicImageUrl::from($row->avatar_url),
                'is_private' => (bool) $row->is_private,
                'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
                'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
                'city' => $row->city,
                'address_line' => $row->address_line,
                'birth_date' => $row->birth_date,
            ],
            'sports' => $this->reader->sportsForUser($targetUserId)->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'practice_type' => $s->practice_type,
                'avatar' => PublicImageUrl::from($s->avatar),
                'is_favorite' => (bool) $s->is_favorite,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $row = $this->reader->userWithProfile($user->id);

        if ($row === null) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'profile' => null,
                'sports' => [],
            ];
        }

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'profile' => [
                'display_name' => $row->display_name,
                'handle' => $row->handle,
                'bio' => $row->bio,
                'avatar_url' => PublicImageUrl::from($row->avatar_url),
                'is_private' => (bool) $row->is_private,
                'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
                'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
                'city' => $row->city,
                'address_line' => $row->address_line,
                'birth_date' => $row->birth_date,
            ],
            'sports' => $this->reader->sportsForUser($user->id)->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'practice_type' => $s->practice_type,
                'avatar' => PublicImageUrl::from($s->avatar),
                'is_favorite' => (bool) $s->is_favorite,
            ])->values()->all(),
        ];
    }
}
