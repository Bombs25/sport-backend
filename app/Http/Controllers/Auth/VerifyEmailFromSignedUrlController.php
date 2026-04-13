<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ce qu’il fait : route **web** signée `GET /email/verify/{id}/{hash}` ; valide le hash, marque l’email comme vérifié, redirige vers l’app.
 *
 * Pourquoi : les liens dans les mails Laravel sont des URLs web (pas Bearer) ; pas d’auth session requise car la
 * signature garantit l’intégrité ; indispensable avec `MustVerifyEmail` sur `User`.
 */
class VerifyEmailFromSignedUrlController extends Controller
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(config('app.url'))->with('verified', true);
    }
}
