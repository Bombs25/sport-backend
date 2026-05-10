<?php

namespace App\Http\Controllers\Api\V1\Images;

use App\Enums\ImageVariantLongEdge;
use App\Events\ImageProcessingEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Endpoint générique : multipart {@code files[]} → {@see ImageProcessingEvent} avec {@code uniqueKey} éphémère ;
 * la validation raster est dans {@see ImageProcessingListener}.
 */
class ImageProcessingStoreController extends Controller
{
    // public function __invoke(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'variant_long_edge' => ['nullable', Rule::enum(ImageVariantLongEdge::class)],
    //     ]);

    //     $files = $request->file('files', []);
    //     if ($files instanceof UploadedFile) {
    //         $files = [$files];
    //     }

    //     ImageProcessingEvent::dispatch(
    //         $request->user(),
    //         is_array($files) ? $files : [],
    //         'images-'.Str::uuid()->toString(),
    //         contextId: null,
    //         variant: $request->enum('variant_long_edge', ImageVariantLongEdge::class) ?? ImageVariantLongEdge::Feed,
    //         type: 'images',
    //     );

    //     return response()->json([
    //         'message' => __('Traitement des images lancé.'),
    //     ], JsonResponse::HTTP_ACCEPTED);
    // }
}
