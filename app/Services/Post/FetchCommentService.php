<?php

namespace App\Services\Post;

use App\Support\PublicImageUrl;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FetchCommentService
{
    /**
     * @return array{
     *   items: list<array{
     *      id:int,
     *      comment_id:int,
     *      publication_id:int,
     *      publication_type:string,
     *      content:string,
     *      user_id:int,
     *      user_name:string,
     *      user_display_name:string|null,
     *      user_handle:string|null,
     *      user_avatar_url:string|null,
     *      likes_count:int,
     *      responses_count:int,
     *      created_at:mixed,
     *      viewer_has_liked:bool
     *   }>,
     *   pagination: array{current_page:int,per_page:int,total:int,last_page:int}
     * }
     */
    public function listForPublicationPaginated(
        int $viewerUserId,
        int $publicationId,
        string $publicationType,
        int $page = 1,
        int $perPage = 10,
    ): array {
        $safePage = max(1, $page);
        $safePerPage = max(1, $perPage);

        $viewerLikesSub = DB::table('comments_likes')
            ->select('comment_id')
            ->where('users_id', $viewerUserId);

        $baseQuery = DB::table('comments')
            ->join('users', 'users.id', '=', 'comments.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoinSub(
                $viewerLikesSub,
                'viewer_comment_likes',
                static function ($join): void {
                    $join->on('viewer_comment_likes.comment_id', '=', 'comments.id');
                },
            )
            ->where('publication_id', $publicationId)
            ->where('publication_type', $publicationType);

        $total = (int) (clone $baseQuery)->count();
        $lastPage = max(1, (int) ceil($total / $safePerPage));

        $rows = $baseQuery
            ->select([
                'comments.id',
                'comments.publication_id',
                'comments.publication_type',
                'comments.content',
                'comments.user_id',
                'users.name as user_name',
                'user_profiles.display_name as user_display_name',
                'user_profiles.handle as user_handle',
                'user_profiles.avatar_url as user_avatar_url',
                'comments.likes_count',
                'comments.responses_count',
                'comments.created_at',
                DB::raw('viewer_comment_likes.comment_id IS NOT NULL AS viewer_has_liked'),
            ])
            ->orderByDesc('comments.created_at')
            ->orderByDesc('comments.id')
            ->forPage($safePage, $safePerPage)
            ->get();

        return [
            'items' => $rows->map(fn (\stdClass $row): array => $this->mapCommentRow($row))->values()->all(),
            'pagination' => [
                'current_page' => $safePage,
                'per_page' => $safePerPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * @return array{
     *   id:int,
     *   comment_id:int,
     *   publication_id:int,
     *   publication_type:string,
     *   content:string,
     *   user_id:int,
     *   user_name:string,
     *   user_display_name:string|null,
     *   user_handle:string|null,
     *   user_avatar_url:string|null,
     *   likes_count:int,
     *   responses_count:int,
     *   created_at:mixed,
     *   viewer_has_liked:bool
     * }|null
     */
    public function findCommentByIdForViewer(int $viewerUserId, int $commentId): ?array
    {
        $viewerLikesSub = DB::table('comments_likes')
            ->select('comment_id')
            ->where('users_id', $viewerUserId);

        $row = $this->commentRowsBaseQuery($viewerUserId, $viewerLikesSub)
            ->where('comments.id', $commentId)
            ->select([
                'comments.id',
                'comments.publication_id',
                'comments.publication_type',
                'comments.content',
                'comments.user_id',
                'users.name as user_name',
                'user_profiles.display_name as user_display_name',
                'user_profiles.handle as user_handle',
                'user_profiles.avatar_url as user_avatar_url',
                'comments.likes_count',
                'comments.responses_count',
                'comments.created_at',
                DB::raw('viewer_comment_likes.comment_id IS NOT NULL AS viewer_has_liked'),
            ])
            ->first();

        if (! $row instanceof \stdClass) {
            return null;
        }

        return $this->mapCommentRow($row);
    }

    /**
     * @return array{
     *   items: list<array{
     *      id:int,
     *      comment_id:int,
     *      response:string,
     *      is_reponse_to_main_comment:bool,
     *      responded_to_who:string|null,
     *      user_id:int,
     *      user_name:string,
     *      user_display_name:string|null,
     *      user_handle:string|null,
     *      user_avatar_url:string|null,
     *      likes_count:int,
     *      created_at:mixed,
     *      viewer_has_liked:bool
     *   }>,
     *   pagination: array{current_page:int,per_page:int,total:int,last_page:int}
     * }
     */
    public function listResponsesForCommentPaginated(
        int $viewerUserId,
        int $commentId,
        int $page = 1,
        int $perPage = 10,
    ): array {
        $safePage = max(1, $page);
        $safePerPage = max(1, $perPage);

        $viewerResponseLikesSub = DB::table('response_comment_like')
            ->select('responses_comment_id')
            ->where('user_id', $viewerUserId);

        $baseQuery = $this->responseRowsBaseQuery($viewerUserId, $viewerResponseLikesSub)
            ->where('response_commentaires.comment_id', $commentId);

        $total = (int) (clone $baseQuery)->count();
        $lastPage = max(1, (int) ceil($total / $safePerPage));

        /** @var Collection<int, \stdClass> $rows */
        $rows = $baseQuery
            ->select([
                'response_commentaires.id',
                'response_commentaires.comment_id',
                'response_commentaires.response',
                'response_commentaires.is_reponse_to_main_comment',
                'response_commentaires.responded_to_who',
                'mentioned_profiles.user_id as responded_to_user_id',
                'response_commentaires.users_id as user_id',
                'users.name as user_name',
                'user_profiles.display_name as user_display_name',
                'user_profiles.handle as user_handle',
                'user_profiles.avatar_url as user_avatar_url',
                'response_commentaires.likes_count',
                'response_commentaires.created_at',
                DB::raw('viewer_response_likes.responses_comment_id IS NOT NULL AS viewer_has_liked'),
            ])
            ->orderBy('response_commentaires.created_at')
            ->orderBy('response_commentaires.id')
            ->forPage($safePage, $safePerPage)
            ->get();

        return [
            'items' => $rows->map(fn (\stdClass $row): array => $this->mapResponseRow($row))->values()->all(),
            'pagination' => [
                'current_page' => $safePage,
                'per_page' => $safePerPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * @return array{
     *   id:int,
     *   comment_id:int,
     *   response:string,
     *   is_reponse_to_main_comment:bool,
     *   responded_to_who:string|null,
     *   responded_to_user_id:int|null,
     *   user_id:int,
     *   user_name:string,
     *   user_display_name:string|null,
     *   user_handle:string|null,
     *   user_avatar_url:string|null,
     *   likes_count:int,
     *   created_at:mixed,
     *   viewer_has_liked:bool
     * }|null
     */
    public function findResponseByIdForViewer(int $viewerUserId, int $responseId): ?array
    {
        $viewerResponseLikesSub = DB::table('response_comment_like')
            ->select('responses_comment_id')
            ->where('user_id', $viewerUserId);

        $row = $this->responseRowsBaseQuery($viewerUserId, $viewerResponseLikesSub)
            ->where('response_commentaires.id', $responseId)
            ->select([
                'response_commentaires.id',
                'response_commentaires.comment_id',
                'response_commentaires.response',
                'response_commentaires.is_reponse_to_main_comment',
                'response_commentaires.responded_to_who',
                'mentioned_profiles.user_id as responded_to_user_id',
                'response_commentaires.users_id as user_id',
                'users.name as user_name',
                'user_profiles.display_name as user_display_name',
                'user_profiles.handle as user_handle',
                'user_profiles.avatar_url as user_avatar_url',
                'response_commentaires.likes_count',
                'response_commentaires.created_at',
                DB::raw('viewer_response_likes.responses_comment_id IS NOT NULL AS viewer_has_liked'),
            ])
            ->first();

        if (! $row instanceof \stdClass) {
            return null;
        }

        return $this->mapResponseRow($row);
    }

    private function commentRowsBaseQuery(int $viewerUserId, $viewerLikesSub): Builder
    {
        return DB::table('comments')
            ->join('users', 'users.id', '=', 'comments.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoinSub(
                $viewerLikesSub,
                'viewer_comment_likes',
                static function ($join): void {
                    $join->on('viewer_comment_likes.comment_id', '=', 'comments.id');
                },
            );
    }

    /**
     * @return array{
     *   id:int,
     *   comment_id:int,
     *   publication_id:int,
     *   publication_type:string,
     *   content:string,
     *   user_id:int,
     *   user_name:string,
     *   user_display_name:string|null,
     *   user_handle:string|null,
     *   user_avatar_url:string|null,
     *   likes_count:int,
     *   responses_count:int,
     *   created_at:mixed,
     *   viewer_has_liked:bool
     * }
     */
    private function mapCommentRow(\stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'comment_id' => (int) $row->id,
            'publication_id' => (int) $row->publication_id,
            'publication_type' => (string) $row->publication_type,
            'content' => (string) $row->content,
            'user_id' => (int) $row->user_id,
            'user_name' => (string) $row->user_name,
            'user_display_name' => $row->user_display_name !== null ? (string) $row->user_display_name : null,
            'user_handle' => $row->user_handle !== null ? (string) $row->user_handle : null,
            'user_avatar_url' => PublicImageUrl::from($row->user_avatar_url),
            'likes_count' => (int) $row->likes_count,
            'responses_count' => (int) $row->responses_count,
            'created_at' => $this->formatCreatedAtUtc($row->created_at),
            'viewer_has_liked' => (bool) ($row->viewer_has_liked ?? false),
        ];
    }

    private function formatCreatedAtUtc(mixed $createdAt): string
    {
        return Carbon::parse((string) $createdAt, 'UTC')->utc()->toIso8601String();
    }

    private function responseRowsBaseQuery(int $viewerUserId, $viewerResponseLikesSub): Builder
    {
        return DB::table('response_commentaires')
            ->join('users', 'users.id', '=', 'response_commentaires.users_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoin(
                'user_profiles as mentioned_profiles',
                'mentioned_profiles.handle',
                '=',
                'response_commentaires.responded_to_who',
            )
            ->leftJoinSub(
                $viewerResponseLikesSub,
                'viewer_response_likes',
                static function ($join): void {
                    $join->on('viewer_response_likes.responses_comment_id', '=', 'response_commentaires.id');
                },
            );
    }

    /**
     * @return array{
     *   id:int,
     *   comment_id:int,
     *   response:string,
     *   is_reponse_to_main_comment:bool,
     *   responded_to_who:string|null,
     *   responded_to_user_id:int|null,
     *   user_id:int,
     *   user_name:string,
     *   user_display_name:string|null,
     *   user_handle:string|null,
     *   user_avatar_url:string|null,
     *   likes_count:int,
     *   created_at:mixed,
     *   viewer_has_liked:bool
     * }
     */
    private function mapResponseRow(\stdClass $row): array
    {
        return [
            'id' => (int) $row->id,
            'comment_id' => (int) $row->comment_id,
            'response' => (string) $row->response,
            'is_reponse_to_main_comment' => (bool) $row->is_reponse_to_main_comment,
            'responded_to_who' => $row->responded_to_who !== null ? (string) $row->responded_to_who : null,
            'responded_to_user_id' => isset($row->responded_to_user_id) && $row->responded_to_user_id !== null
                ? (int) $row->responded_to_user_id
                : null,
            'user_id' => (int) $row->user_id,
            'user_name' => (string) $row->user_name,
            'user_display_name' => $row->user_display_name !== null ? (string) $row->user_display_name : null,
            'user_handle' => $row->user_handle !== null ? (string) $row->user_handle : null,
            'user_avatar_url' => PublicImageUrl::from($row->user_avatar_url),
            'likes_count' => (int) $row->likes_count,
            'created_at' => $this->formatCreatedAtUtc($row->created_at),
            'viewer_has_liked' => (bool) ($row->viewer_has_liked ?? false),
        ];
    }
}
