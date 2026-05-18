<?php

namespace App\Http\Controllers\Api\V1\Upload;

use App\Http\Controllers\Controller;
use App\Support\ImagePipelineResultCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Dernière progression d'upload connue (cache), pour polling WebView si Reverb rate un événement.
 */
class UploadProgressPollController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $cached = Cache::get(ImagePipelineResultCache::latestForUserKey($userId));

        if (! is_array($cached)) {
            return response()->json(['data' => null]);
        }

        $batchKey = $request->query('batch_key');
        if (is_string($batchKey) && $batchKey !== '' && ($cached['batch_key'] ?? null) !== $batchKey) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $cached]);
    }
}
