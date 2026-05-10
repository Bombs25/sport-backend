<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Enums\ImageVariantLongEdge;
use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Services\Profile\UpdateProfileService;
use App\Services\Register\RegisterUserPayloadBuilder;
use Illuminate\Http\JsonResponse;

/**
 * Ce qu'il fait : `PATCH` authentifié pour modifier le profil utilisateur (partiel) et renvoyer le payload `user`.
 *
 * Pourquoi : exposer une route dédiée d'édition du profil hors parcours d'inscription.
 */
class UpdateProfileController extends Controller
{
    public function __invoke(
        UpdateProfileRequest $request,
        UpdateProfileService $service,
        RegisterUserPayloadBuilder $payloadBuilder,
    ): JsonResponse {
        $user = $request->user();

        $data = $request->validated();
        if ($request->hasFile('avatar_url')) {
            unset($data['avatar_url']);
        }
        $service->update($user->id, $data);

        ImageProcessingEvent::dispatch(
            $request->user(),
            [
                $request->file('avatar_url'),
            ],
            'profile-'.$user->id,
            contextId: $user->id,
            variant: ImageVariantLongEdge::GridThumb,
            type: 'profile',
        );

        return response()->json([
            'user' => $payloadBuilder->build($user->fresh()),
        ]);
    }
}
