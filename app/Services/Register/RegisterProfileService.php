<?php

namespace App\Services\Register;

use App\Services\Search\Concerns\SyncsUserToTypesense;
use App\Services\Search\TypesenseUserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ce qu’il fait : enregistre prénom, nom, pseudo public (`handle`), date de naissance ; aligne `users.name`
 * et `user_profiles.display_name` sur le nom d’état civil ; refuse un handle déjà pris par un autre compte.
 *
 * Pourquoi : correspond à l’écran « Parlez-nous de vous » ; le nom légal ne doit pas être déduit de l’email
 * ; unicité du pseudo vérifiée en base avant mise à jour.
 */
class RegisterProfileService
{
    use SyncsUserToTypesense;

    public function __construct(
        private readonly TypesenseUserService $typesenseUsers,
    ) {}

    /**
     * Met à jour le nom d’état civil (`users.name`) et l’affichage public (`user_profiles.display_name`).
     */
    public function update(
        int $userId,
        string $givenName,
        string $familyName,
        string $handle,
        string $birthDate,
    ): void {
        if (DB::table('user_profiles')->where('handle', $handle)->where('user_id', '!=', $userId)->exists()) {
            throw ValidationException::withMessages([
                'handle' => [__('Ce pseudo est déjà utilisé.')],
            ]);
        }

        $displayName = trim($givenName.' '.$familyName);

        DB::table('users')->where('id', $userId)->update([
            'name' => $displayName,
            'updated_at' => now(),
        ]);

        DB::table('user_profiles')->where('user_id', $userId)->update([
            'display_name' => Str::limit($displayName, 64, ''),
            'handle' => $handle,
            'birth_date' => $birthDate,
            'updated_at' => now(),
        ]);

        $this->syncUserToTypesense($this->typesenseUsers, $userId);
    }
}
