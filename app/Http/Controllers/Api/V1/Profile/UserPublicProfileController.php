<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UserPublicProfileRequest;
use App\Services\Post\FetchPostService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : renvoie le profil d'un autre utilisateur (followers / following / recherche)
 * et ses posts réguliers publiés, pour le client connecté.
 *
 * Pourquoi : complète les listes follow sans exposer l'e-mail aux tiers ; respecte `is_private`
 * + follow accepté. Les posts en visibilité `followers` ne sont renvoyés qu'aux abonnés (ou au
 * propriétaire).
 */
class UserPublicProfileController extends Controller
{
    /** Posts réguliers renvoyés pour la grille du profil (les plus récents d'abord). */
    private const PROFILE_POSTS_LIMIT = 30;

    public function __invoke(
        UserPublicProfileRequest $request,
        RegisterUserPayloadBuilder $payloadBuilder,
        FetchPostService $postService,
    ): JsonResponse {
        $viewer = $request->user();
        $targetUserId = (int) $request->validated('user');

        // `buildForViewer` applique le contrôle d'accès (404 introuvable / 403 compte privé).
        $user = $payloadBuilder->buildForViewer($viewer, $targetUserId);

        $includeFollowersVisibility = (int) $viewer->id === $targetUserId
            || (bool) ($user['am_i_following'] ?? false);

        $posts = $postService->fetchRegularPostsByAuthor(
            (int) $viewer->id,
            $targetUserId,
            $includeFollowersVisibility,
            self::PROFILE_POSTS_LIMIT,
        );

        return response()->json([
            'user' => $user,
            'posts' => $posts,
        ]);
    }
}
