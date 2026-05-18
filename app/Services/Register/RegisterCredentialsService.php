<?php

namespace App\Services\Register;

use App\Models\User;
use App\Support\UserProfileLocation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

/**
 * Ce qu’il fait : crée le compte (`users` + première ligne `user_profiles` avec ville + position GPS obligatoires),
 * nom d’état civil obligatoire (prénom + nom), pseudo temporaire unique, déclenche l’email de vérification,
 * émet un **token Sanctum** pour la suite du wizard.
 *
 * Pourquoi : première étape inscription ; `users.name` est NOT NULL : le front envoie toujours le nom civil ici ;
 * transaction pour cohérence ; `Registered` pour la vérification email Laravel.
 */
class RegisterCredentialsService
{
    /**
     * @return array{user: User, token: NewAccessToken}
     */
    public function register(
        string $email,
        string $password,
        string $city,
        float $latitude,
        float $longitude,
        string $givenName,
        string $familyName,
        ?string $fcmToken = null,
    ): array {
        return DB::transaction(function () use ($email, $password, $city, $latitude, $longitude, $givenName, $familyName, $fcmToken) {
            $civilName = trim($givenName.' '.$familyName);

            $user = User::query()->create([
                'name' => $civilName,
                'email' => $email,
                'password' => $password,
                'fcm_token' => $fcmToken,
            ]);

            $handle = $this->resolveUniqueHandle($givenName, $familyName);

            /*
             * Ville + position WGS-84 fournies par React Native dès l’inscription (sélecteur / carte).
             * Peuvent être affinées ensuite via `register/location` (adresse, nouveau point).
             */
            DB::table('user_profiles')->insert(array_merge([
                'user_id' => $user->id,
                'display_name' => Str::limit($civilName, 64, ''),
                'handle' => $handle,
                'bio' => null,
                'avatar_url' => null,
                'is_private' => false,
                'city' => $city,
                'address_line' => null,
                'birth_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], UserProfileLocation::columnsFromLatLng($latitude, $longitude)));

            /*
             * Événement Laravel standard après création de compte. Avec MustVerifyEmail sur User,
             * l’écouteur SendEmailVerificationNotification appelle `User::sendEmailVerificationNotification()` :
             * e-mail **code OTP 6 chiffres** (pas de lien signé dans le mail ; vérification côté API RN).
             * Autres écouteurs (welcome, intégrations) peuvent s’y brancher sans modifier ce service.
             */
            event(new Registered($user));

            return [
                'user' => $user,
                'token' => $user->createToken('auth'),
            ];
        });
    }

    private function resolveUniqueHandle(string $givenName, string $familyName): string
    {
        $prefix = 'osport_';
        $baseCore = Str::slug(trim($givenName.'_'.$familyName), '_');
        $baseCore = $baseCore !== '' ? $baseCore : 'player';
        $baseCore = Str::limit($baseCore, 20, '');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = '_'.Str::lower(Str::random(4));
            $handle = $prefix.$baseCore.$suffix;

            if (! DB::table('user_profiles')->where('handle', $handle)->exists()) {
                return $handle;
            }
        }

        return $prefix.Str::lower(Str::random(25));
    }
}
