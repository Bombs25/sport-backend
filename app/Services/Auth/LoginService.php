<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

/**
 * Ce qu’il fait : vérifie e-mail + mot de passe (recherche **insensible à la casse**), retourne l’`User` si le mot de
 * passe est correct ; émet un **token Sanctum** sur demande.
 *
 * Pourquoi : isoler la logique d’authentification ; la **vérification e-mail obligatoire au login** est appliquée
 * dans le contrôleur après `validatePasswordLogin`, pour ne pas créer de token si le compte n’est pas vérifié.
 */
class LoginService
{
    /**
     * Utilisateur trouvé et mot de passe valide ; ne vérifie pas `email_verified_at` (voir contrôleur).
     */
    public function validatePasswordLogin(string $email, string $password): ?User
    {
        $email = Str::lower($email);

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function createSanctumToken(User $user): NewAccessToken
    {
        return $user->createToken('auth');
    }
}
