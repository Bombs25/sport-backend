<?php

namespace App\Services\Profile;

use App\Services\Search\Concerns\SyncsUserToTypesense;
use App\Services\Search\TypesenseTeamService;
use App\Services\Search\TypesenseUserService;
use App\Support\Search\TypesenseSyncGuard;
use App\Support\UserProfileLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Typesense\Exceptions\TypesenseClientError;

/**
 * Ce qu'il fait : applique une mise à jour partielle du profil (`users` + `user_profiles`) pour l'utilisateur authentifié.
 *
 * Pourquoi : centraliser les règles de persistance du profil pour le endpoint d'édition hors onboarding.
 */
class UpdateProfileService
{
    use SyncsUserToTypesense;

    public function __construct(
        private readonly TypesenseTeamService $typesenseTeams,
        private readonly TypesenseUserService $typesenseUsers,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(int $userId, array $validated): void
    {
        $profileUpdates = [];
        $locationChanged = array_key_exists('latitude', $validated) || array_key_exists('longitude', $validated);

        if (array_key_exists('given_name', $validated) || array_key_exists('family_name', $validated)) {
            $currentUser = DB::table('users')->select(['name'])->where('id', $userId)->first();
            $currentName = $currentUser?->name ?? '';
            [$currentGivenName, $currentFamilyName] = $this->splitCivilName($currentName);

            $givenName = $validated['given_name'] ?? $currentGivenName;
            $familyName = $validated['family_name'] ?? $currentFamilyName;
            $civilName = trim(sprintf('%s %s', $givenName, $familyName));

            DB::table('users')->where('id', $userId)->update([
                'name' => $civilName,
                'updated_at' => now(),
            ]);

            $profileUpdates['display_name'] = Str::limit($civilName, 64, '');
        }

        if (array_key_exists('handle', $validated)) {
            $profileUpdates['handle'] = $validated['handle'];
        }

        if (array_key_exists('birth_date', $validated)) {
            $profileUpdates['birth_date'] = $validated['birth_date'];
        }

        if (array_key_exists('bio', $validated)) {
            $profileUpdates['bio'] = $validated['bio'];
        }

        if (array_key_exists('phone', $validated)) {
            $profileUpdates['phone'] = $validated['phone'];
        }

        if (array_key_exists('is_private', $validated)) {
            $profileUpdates['is_private'] = (bool) $validated['is_private'];
        }

        if (array_key_exists('who_can_tag_me', $validated)) {
            $profileUpdates['who_can_tag_me'] = $validated['who_can_tag_me'];
        }

        if (array_key_exists('who_can_message_me', $validated)) {
            $profileUpdates['who_can_message_me'] = $validated['who_can_message_me'];
        }

        if (array_key_exists('precise_location_enabled', $validated)) {
            $profileUpdates['precise_location_enabled'] = (bool) $validated['precise_location_enabled'];
        }

        if (array_key_exists('hide_online_status', $validated)) {
            $profileUpdates['hide_online_status'] = (bool) $validated['hide_online_status'];
        }

        if (array_key_exists('language', $validated)) {
            $profileUpdates['language'] = $validated['language'];
        }

        if (array_key_exists('theme', $validated)) {
            $profileUpdates['theme'] = $validated['theme'];
        }

        if (array_key_exists('avatar_url', $validated)) {
            $profileUpdates['avatar_url'] = $validated['avatar_url'];
        }

        if (array_key_exists('latitude', $validated) || array_key_exists('longitude', $validated)) {
            $current = UserProfileLocation::currentLatLngForUser($userId);
            $lat = array_key_exists('latitude', $validated) ? $validated['latitude'] : $current['latitude'];
            $lng = array_key_exists('longitude', $validated) ? $validated['longitude'] : $current['longitude'];
            $profileUpdates = array_merge(
                $profileUpdates,
                UserProfileLocation::columnsFromLatLng(
                    $lat !== null ? (float) $lat : null,
                    $lng !== null ? (float) $lng : null,
                ),
            );
        }

        if (array_key_exists('city', $validated)) {
            $profileUpdates['city'] = $validated['city'];
        }

        if (array_key_exists('address_line', $validated)) {
            $profileUpdates['address_line'] = $validated['address_line'];
        }

        if ($profileUpdates === []) {
            return;
        }

        $profileUpdates['updated_at'] = now();

        DB::table('user_profiles')->where('user_id', $userId)->update($profileUpdates);

        $this->syncUserToTypesense($this->typesenseUsers, $userId);

        if ($locationChanged) {
            $this->syncCreatorTeamsToTypesense($userId);
        }
    }

    private function syncCreatorTeamsToTypesense(int $userId): void
    {
        if (! TypesenseSyncGuard::isEnabled()) {
            return;
        }

        try {
            $this->typesenseTeams->syncTeamsForCreatorFromDatabase($userId);
        } catch (TypesenseClientError $e) {
            Log::warning('Typesense creator teams location sync failed.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitCivilName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }
}
