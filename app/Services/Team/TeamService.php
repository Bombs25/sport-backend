<?php

namespace App\Services\Team;

use App\Models\Team;
use App\Services\Search\TypesenseTeamService;
use App\Support\PublicImageUrl;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Typesense\Exceptions\TypesenseClientError;

class TeamService
{
    public function __construct(
        private readonly TypesenseTeamService $typesenseTeams,
    ) {}

    /**
     * Construit la réponse "Mes équipes" en séparant les équipes créées et rejointes,
     * avec un compteur de membres actifs par équipe.
     *
     * @return array{created: list<array<string, mixed>>, joined: list<array<string, mixed>>, counts: array{created: int, joined: int}}
     */
    public function listMine(int $userId): array
    {
        $createdTeams = DB::table('teams')
            ->where('creator_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $memberTeamIds = DB::table('team_members')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->pluck('team_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $createdIds = $createdTeams->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $joinedIds = array_values(array_diff($memberTeamIds, $createdIds));

        $joinedTeams = $joinedIds === []
            ? collect()
            : DB::table('teams')->whereIn('id', $joinedIds)->orderByDesc('created_at')->get();

        $countsMap = $this->activeMemberCountsForTeamIds(array_values(array_unique(array_merge(
            $createdIds,
            $joinedIds,
        ))));

        $createdPayload = $createdTeams
            ->map(fn (object $row): array => $this->formatListRow($row, $countsMap))
            ->values()
            ->all();

        $joinedPayload = $joinedTeams
            ->map(fn (object $row): array => $this->formatListRow($row, $countsMap))
            ->values()
            ->all();

        return [
            'created' => $createdPayload,
            'joined' => $joinedPayload,
            'counts' => [
                'created' => count($createdPayload),
                'joined' => count($joinedPayload),
            ],
        ];
    }

    /**
     * Crée une équipe et rattache immédiatement le créateur en capitaine actif.
     *
     * @param  array{name: string, sport_id: int, description?: string|null, hq_city?: string|null, hq_latitude?: float|null, hq_longitude?: float|null, cover_image_url?: string|null, logo_url?: string|null, competition_type?: string|null, skill_level?: string|null}  $data
     */
    public function createForCreator(int $creatorId, array $data): Team
    {
        $slug = $this->allocateUniqueSlug($data['name']);

        $team = DB::transaction(function () use ($creatorId, $data, $slug): Team {
            $teamId = DB::table('teams')->insertGetId([
                'creator_id' => $creatorId,
                'sport_id' => $data['sport_id'],
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'hq_city' => $data['hq_city'] ?? null,
                'hq_latitude' => $data['hq_latitude'] ?? null,
                'hq_longitude' => $data['hq_longitude'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'logo_url' => $data['logo_url'] ?? null,
                'competition_type' => $data['competition_type'] ?? 'leisure',
                'skill_level' => $data['skill_level'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('team_members')->insert([
                'team_id' => $teamId,
                'user_id' => $creatorId,
                'role' => 'captain',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return Team::query()->findOrFail($teamId);
        });

        $this->cacheUserSportId($creatorId, (int) $data['sport_id']);
        $this->syncTeamToTypesense((int) $team->id);

        return $team;
    }

    /**
     * Met à jour de manière partielle une équipe existante et régénère le slug si le nom change.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTeam(Team $team, array $data): void
    {
        $updates = [];

        foreach (
            [
                'description' => 'description',
                'hq_city' => 'hq_city',
                'hq_latitude' => 'hq_latitude',
                'hq_longitude' => 'hq_longitude',
                'cover_image_url' => 'cover_image_url',
                'logo_url' => 'logo_url',
                'competition_type' => 'competition_type',
                'skill_level' => 'skill_level',
            ] as $key => $col
        ) {
            if (array_key_exists($key, $data)) {
                $updates[$col] = $data[$key];
            }
        }

        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
            $updates['slug'] = $this->allocateUniqueSlug($data['name'], $team->id);
        }

        if (array_key_exists('sport_id', $data)) {
            $updates['sport_id'] = $data['sport_id'];
        }

        if ($updates === []) {
            return;
        }

        $updates['updated_at'] = now();

        DB::table('teams')->where('id', $team->id)->update($updates);
        $team->refresh();
        $this->syncTeamToTypesense((int) $team->id);
    }

    /**
     * Supprime une équipe (les membres liés sont supprimés en cascade via la FK).
     */
    public function deleteTeam(Team $team): void
    {
        DB::table('teams')->where('id', $team->id)->delete();
    }

    private function syncTeamToTypesense(int $teamId): void
    {
        try {
            $this->typesenseTeams->syncTeamFromDatabase($teamId);
        } catch (TypesenseClientError $e) {
            Log::warning('Typesense team sync failed.', [
                'team_id' => $teamId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enregistre une demande d'intégration d'un utilisateur dans une équipe.
     *
     * @throws ValidationException
     */
    public function requestIntegration(Team $team, int $userId): void
    {
        $this->ensureTeamIsCollective($team);
        $this->ensureUserCanJoinSport($userId, (int) $team->sport_id, (int) $team->id);
        $this->cacheUserSportId($userId, (int) $team->sport_id);

        $existingMembership = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingMembership !== null && $existingMembership->status === 'active') {
            throw ValidationException::withMessages([
                'team_id' => __('Cet utilisateur fait déjà partie de cette équipe.'),
            ]);
        }

        $now = now();

        if ($existingMembership === null) {
            DB::table('team_members')->insert([
                'team_id' => $team->id,
                'user_id' => $userId,
                'role' => 'member',
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('team_members')
            ->where('id', $existingMembership->id)
            ->update([
                'role' => 'member',
                'status' => 'pending',
                'updated_at' => $now,
            ]);
    }

    /**
     * Accepte ou refuse une demande d'intégration à une équipe.
     *
     * @throws ValidationException
     */
    public function decideIntegration(Team $team, int $applicantUserId, string $decision, int $actorUserId): void
    {
        $this->ensureCanManageIntegrations($team, $actorUserId);

        $membership = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $applicantUserId)
            ->where('status', 'pending')
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'asker_user_id' => __('Aucune demande d’intégration en attente pour cet utilisateur.'),
            ]);
        }

        if ($decision === 'accept') {
            $this->ensureUserCanJoinSport($applicantUserId, (int) $team->sport_id, (int) $team->id);

            DB::table('team_members')
                ->where('id', $membership->id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
            $this->cacheUserSportId($applicantUserId, (int) $team->sport_id);

            return;
        }

        DB::table('team_members')
            ->where('id', $membership->id)
            ->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);
    }

    /**
     * Permet à un membre de quitter son équipe ou à un créateur/captain actif de retirer un membre.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function removeMember(Team $team, int $actorUserId, int $memberUserId): void
    {
        $membership = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $memberUserId)
            ->where('status', 'active')
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'member_user_id' => __('Ce membre actif est introuvable dans cette équipe.'),
            ]);
        }

        $isSelfLeave = $actorUserId === $memberUserId;
        if (! $isSelfLeave) {
            $this->ensureCanManageIntegrations($team, $actorUserId);
        }

        if ((int) $team->creator_id === $memberUserId) {
            throw ValidationException::withMessages([
                'member_user_id' => __('Le créateur ne peut pas être retiré de son équipe.'),
            ]);
        }

        DB::table('team_members')
            ->where('id', $membership->id)
            ->update([
                'role' => 'member',
                'status' => 'left',
                'updated_at' => now(),
            ]);
    }

    /**
     * Retourne le statut du user connecté vis-à-vis d'une équipe.
     *
     * @return array{team_id: int, is_member: bool, integration_requested: bool, membership_status: string|null, role: string|null}
     */
    public function membershipStatus(Team $team, int $userId): array
    {
        $membership = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $userId)
            ->select(['status', 'role'])
            ->first();

        $membershipStatus = $membership?->status;

        return [
            'team_id' => (int) $team->id,
            'is_member' => $membershipStatus === 'active',
            'integration_requested' => $membershipStatus === 'pending',
            'membership_status' => $membershipStatus,
            'role' => $membership?->role,
        ];
    }

    /**
     * Liste paginée des demandes d'intégration en attente pour une équipe.
     *
     * @return array{
     *   items: list<array{user_id:int,name:string,email:string,avatar_url:string|null,requested_at:mixed}>,
     *   pagination: array{current_page:int,per_page:int,total:int,last_page:int}
     * }
     *
     * @throws AuthorizationException
     */
    public function listPendingIntegrations(Team $team, int $actorUserId, int $page = 1): array
    {
        $this->ensureCanManageIntegrations($team, $actorUserId);

        $perPage = 10;
        $safePage = max(1, $page);

        $baseQuery = DB::table('team_members')
            ->join('users', 'users.id', '=', 'team_members.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('team_members.team_id', $team->id)
            ->where('team_members.status', 'pending');

        $total = (int) (clone $baseQuery)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $rows = $baseQuery
            ->orderByDesc('team_members.created_at')
            ->forPage($safePage, $perPage)
            ->select([
                'users.id as user_id',
                'users.name',
                'users.email',
                'user_profiles.avatar_url',
                'team_members.created_at as requested_at',
            ])
            ->get();

        return [
            'items' => $rows
                ->map(static fn (object $row): array => [
                    'user_id' => (int) $row->user_id,
                    'name' => $row->name,
                    'email' => $row->email,
                    'avatar_url' => PublicImageUrl::from($row->avatar_url),
                    'requested_at' => $row->requested_at,
                ])
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $safePage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * Retourne le payload détaillé d'une équipe avec les informations sport et l'effectif actif.
     *
     * @return array<string, mixed>
     */
    public function buildDetailPayload(Team $team): array
    {
        $row = DB::table('teams')
            ->join('sports', 'sports.id', '=', 'teams.sport_id')
            ->where('teams.id', $team->id)
            ->select([
                'teams.*',
                'sports.name as sport_name',
                'sports.slug as sport_slug',
                'sports.practice_type as sport_practice_type',
            ])
            ->first();

        if ($row === null) {
            abort(404);
        }

        $countsMap = $this->activeMemberCountsForTeamIds([(int) $team->id]);
        $membersCount = (int) ($countsMap[(int) $team->id] ?? 0);

        return $this->formatDetailRow($row, $membersCount);
    }

    /**
     * Retourne les données pour la page profil équipe.
     *
     * @return array{
     *   id:int,
     *   name:string,
     *   hq_city:string|null,
     *   sport:array{id:int,name:string,slug:string,practice_type:string|null},
     *   members_count:int,
     *   members:array{
     *     items:list<array{user_id:int,name:string,avatar_url:string|null,role:string}>,
     *     pagination:array{current_page:int,per_page:int,total:int,last_page:int}
     *   }
     * }
     */
    public function buildProfilePayload(Team $team, int $page = 1): array
    {
        $teamRow = DB::table('teams')
            ->join('sports', 'sports.id', '=', 'teams.sport_id')
            ->where('teams.id', $team->id)
            ->select([
                'teams.id',
                'teams.name',
                'teams.hq_city',
                'sports.id as sport_id',
                'sports.name as sport_name',
                'sports.slug as sport_slug',
                'sports.practice_type as sport_practice_type',
            ])
            ->first();

        if ($teamRow === null) {
            abort(404);
        }

        $perPage = 10;
        $safePage = max(1, $page);

        $membersBaseQuery = DB::table('team_members')
            ->join('users', 'users.id', '=', 'team_members.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->where('team_members.team_id', $team->id)
            ->where('team_members.status', 'active');

        $membersCount = (int) (clone $membersBaseQuery)->count();
        $lastPage = max(1, (int) ceil($membersCount / $perPage));

        $memberRows = $membersBaseQuery
            ->orderBy('team_members.role')
            ->orderBy('users.name')
            ->forPage($safePage, $perPage)
            ->select([
                'users.id as user_id',
                'users.name',
                'user_profiles.avatar_url',
                'team_members.role',
            ])
            ->get();

        return [
            'id' => (int) $teamRow->id,
            'name' => $teamRow->name,
            'hq_city' => $teamRow->hq_city,
            'sport' => [
                'id' => (int) $teamRow->sport_id,
                'name' => $teamRow->sport_name,
                'slug' => $teamRow->sport_slug,
                'practice_type' => $teamRow->sport_practice_type,
            ],
            'members_count' => $membersCount,
            'members' => [
                'items' => $memberRows
                    ->map(static fn (object $row): array => [
                        'user_id' => (int) $row->user_id,
                        'name' => $row->name,
                        'avatar_url' => PublicImageUrl::from($row->avatar_url),
                        'role' => $row->role,
                    ])
                    ->values()
                    ->all(),
                'pagination' => [
                    'current_page' => $safePage,
                    'per_page' => $perPage,
                    'total' => $membersCount,
                    'last_page' => $lastPage,
                ],
            ],
        ];
    }

    /**
     * Crée une demande de match entre deux équipes.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function requestMatch(
        Team $homeTeam,
        int $actorUserId,
        int $awayTeamId,
        string $scheduledAt,
        ?string $venue = null,
        ?string $notes = null,
    ): int {
        $this->ensureCanRequestMatch($homeTeam, $actorUserId);

        if ((int) $homeTeam->id === $awayTeamId) {
            throw ValidationException::withMessages([
                'away_team_id' => __('Une équipe ne peut pas se défier elle-même.'),
            ]);
        }

        $awayTeam = DB::table('teams')
            ->where('id', $awayTeamId)
            ->select(['id', 'sport_id'])
            ->first();

        if ($awayTeam === null) {
            throw ValidationException::withMessages([
                'away_team_id' => __('Équipe adverse introuvable.'),
            ]);
        }

        if ((int) $homeTeam->sport_id !== (int) $awayTeam->sport_id) {
            throw ValidationException::withMessages([
                'away_team_id' => __('La demande de match est possible uniquement entre deux équipes du même sport.'),
            ]);
        }

        $existingPendingBetweenTeams = DB::table('match_events')
            ->where('status', 'requested')
            ->where(function ($query) use ($homeTeam, $awayTeam): void {
                $query
                    ->where(function ($pairQuery) use ($homeTeam, $awayTeam): void {
                        $pairQuery
                            ->where('home_team_id', $homeTeam->id)
                            ->where('away_team_id', $awayTeam->id);
                    })
                    ->orWhere(function ($pairQuery) use ($homeTeam, $awayTeam): void {
                        $pairQuery
                            ->where('home_team_id', $awayTeam->id)
                            ->where('away_team_id', $homeTeam->id);
                    });
            })
            ->exists();

        if ($existingPendingBetweenTeams) {
            throw ValidationException::withMessages([
                'away_team_id' => __('Une demande de match est déjà en cours entre ces deux équipes.'),
            ]);
        }

        $hasSameDateForRequester = DB::table('match_events')
            ->where('home_team_id', $homeTeam->id)
            ->where('status', 'requested')
            ->where('scheduled_at', $scheduledAt)
            ->exists();

        if ($hasSameDateForRequester) {
            throw ValidationException::withMessages([
                'scheduled_at' => __('Cette équipe a déjà une demande de match en cours à cette date.'),
            ]);
        }

        return (int) DB::table('match_events')->insertGetId([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'scheduled_at' => $scheduledAt,
            'venue' => $venue,
            'status' => 'requested',
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Liste les demandes de match pour l'onglet reçu ou envoyé.
     *
     * @return array{
     *   type:string,
     *   status:string|null,
     *   scheduled_at:string|null,
     *   sport_name:string|null,
     *   can_manage_match_requests:bool,
     *   items:list<array<string,mixed>>,
     *   pagination:array{current_page:int,per_page:int,total:int,last_page:int}
     * }
     */
    public function listMatchRequests(
        int $actorUserId,
        string $type = 'received',
        ?string $status = null,
        ?string $scheduledAt = null,
        ?string $sportName = null,
        int $page = 1
    ): array {
        $managedTeamIds = $this->manageableTeamIdsForUser($actorUserId);
        $canManageMatchRequests = $managedTeamIds !== [];

        $safeType = $type === 'sent' ? 'sent' : 'received';
        $safeStatus = in_array($status, ['pending', 'accepted', 'refused', 'scores_to_confirm', 'finished'], true) ? $status : null;
        $safeSportName = $sportName !== null ? trim($sportName) : null;
        $safeScheduledDate = $scheduledAt !== null ? date('Y-m-d', strtotime($scheduledAt)) : null;
        $safePage = max(1, $page);
        $perPage = 10;

        $baseQuery = DB::table('match_events')
            ->leftJoin('match_results', 'match_results.match_event_id', '=', 'match_events.id')
            ->join('teams as home_teams', 'home_teams.id', '=', 'match_events.home_team_id')
            ->join('teams as away_teams', 'away_teams.id', '=', 'match_events.away_team_id')
            ->join('sports', 'sports.id', '=', 'home_teams.sport_id')
            ->whereIn(
                $safeType === 'received' ? 'match_events.away_team_id' : 'match_events.home_team_id',
                $managedTeamIds
            )
            ->whereIn('match_events.status', ['requested', 'scheduled', 'cancelled', 'finished'])
            ->when($safeStatus !== null, static function ($query) use ($safeStatus): void {
                if ($safeStatus === 'pending') {
                    $query->where('match_events.status', 'requested');

                    return;
                }

                if ($safeStatus === 'accepted') {
                    $query->where('match_events.status', 'scheduled');

                    return;
                }

                if ($safeStatus === 'finished') {
                    $query->where('match_events.status', 'finished');

                    return;
                }

                if ($safeStatus === 'scores_to_confirm') {
                    $query->where('match_results.status', 'score_pending_validation');

                    return;
                }

                if ($safeStatus === 'refused') {
                    $query->where(function ($q): void {
                        $q->where('match_events.status', 'cancelled')
                            ->orWhere('match_results.status', 'refused');
                    });
                }
            })
            ->when($safeScheduledDate !== null, static function ($query) use ($safeScheduledDate): void {
                $query->whereDate('match_events.scheduled_at', $safeScheduledDate);
            })
            ->when($safeSportName !== null && $safeSportName !== '', static function ($query) use ($safeSportName): void {
                $query->where('sports.name', $safeSportName);
            });

        $total = (int) (clone $baseQuery)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $rows = $baseQuery
            ->orderByDesc('match_events.created_at')
            ->forPage($safePage, $perPage)
            ->select([
                'match_events.id as match_event_id',
                'match_events.home_team_id',
                'match_events.away_team_id',
                'home_teams.name as home_team_name',
                'away_teams.name as away_team_name',
                'sports.name as sport_name',
                'sports.practice_type as sport_practice_type',
                'match_events.scheduled_at',
                'match_events.venue',
                'match_events.status',
                'match_results.status as match_result_status',
                'match_events.created_at',
            ])
            ->get();

        $collectiveTeamIds = $rows
            ->filter(static fn (object $row): bool => $row->sport_practice_type === 'collective')
            ->flatMap(static fn (object $row): array => [(int) $row->home_team_id, (int) $row->away_team_id])
            ->unique()
            ->values()
            ->all();
        $membersByTeamId = $this->activeMembersByTeamIds($collectiveTeamIds);

        return [
            'type' => $safeType,
            'status' => $safeStatus,
            'scheduled_at' => $safeScheduledDate,
            'sport_name' => $safeSportName,
            'can_manage_match_requests' => $canManageMatchRequests,
            'items' => $rows->map(
                static function (object $row) use ($safeType, $membersByTeamId): array {
                    $isReceived = $safeType === 'received';
                    $publicStatus = match ($row->match_result_status) {
                        'score_pending_validation' => 'scores_to_confirm',
                        'refused' => 'refused',
                        default => match ($row->status) {
                            'requested' => 'pending',
                            'scheduled' => 'accepted',
                            'cancelled' => 'refused',
                            'finished' => 'finished',
                            default => $row->status,
                        },
                    };

                    $item = [
                        'match_event_id' => (int) $row->match_event_id,
                        'direction' => $safeType,
                        'status' => $publicStatus,
                        'scheduled_at' => $row->scheduled_at,
                        'venue' => $row->venue,
                        'home_team' => [
                            'id' => (int) $row->home_team_id,
                            'name' => $row->home_team_name,
                        ],
                        'away_team' => [
                            'id' => (int) $row->away_team_id,
                            'name' => $row->away_team_name,
                        ],
                        'sport' => [
                            'name' => $row->sport_name,
                            'practice_type' => $row->sport_practice_type,
                        ],
                        'badge' => $isReceived && $publicStatus === 'pending' ? 'new' : $publicStatus,
                    ];

                    if ($row->sport_practice_type === 'collective') {
                        $item['home_team']['members'] = $membersByTeamId[(int) $row->home_team_id] ?? [];
                        $item['away_team']['members'] = $membersByTeamId[(int) $row->away_team_id] ?? [];
                    }

                    return $item;
                }
            )->values()->all(),
            'pagination' => [
                'current_page' => $safePage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * Retourne les membres actifs indexés par équipe.
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, list<array{user_id:int,name:string,avatar_url:string|null,role:string}>>
     */
    private function activeMembersByTeamIds(array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }

        $rows = DB::table('team_members')
            ->join('users', 'users.id', '=', 'team_members.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->whereIn('team_members.team_id', $teamIds)
            ->where('team_members.status', 'active')
            ->orderBy('team_members.team_id')
            ->orderBy('team_members.role')
            ->orderBy('users.name')
            ->select([
                'team_members.team_id',
                'users.id as user_id',
                'users.name',
                'user_profiles.avatar_url',
                'team_members.role',
            ])
            ->get();

        $membersByTeam = [];
        foreach ($rows as $row) {
            $teamId = (int) $row->team_id;
            $membersByTeam[$teamId] ??= [];
            $membersByTeam[$teamId][] = [
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'avatar_url' => PublicImageUrl::from($row->avatar_url),
                'role' => $row->role,
            ];
        }

        return $membersByTeam;
    }

    /**
     * Accepte ou refuse une demande de match reçue.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function decideMatchRequest(int $matchEventId, int $actorUserId, string $decision): void
    {
        $matchEvent = DB::table('match_events')
            ->where('id', $matchEventId)
            ->select(['id', 'away_team_id', 'status'])
            ->first();

        if ($matchEvent === null) {
            throw ValidationException::withMessages([
                'match_event_id' => __('Demande de match introuvable.'),
            ]);
        }

        $this->ensureCanRequestMatch(
            Team::query()->findOrFail((int) $matchEvent->away_team_id),
            $actorUserId
        );

        if ($matchEvent->status !== 'requested') {
            throw ValidationException::withMessages([
                'match_event_id' => __('Cette demande de match n’est plus en attente.'),
            ]);
        }

        DB::table('match_events')
            ->where('id', $matchEventId)
            ->update([
                'status' => $decision === 'accept' ? 'scheduled' : 'cancelled',
                'updated_at' => now(),
            ]);
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureCanRequestMatch(Team $team, int $actorUserId): void
    {
        if ((int) $team->creator_id === $actorUserId) {
            return;
        }

        $isActiveCaptain = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $actorUserId)
            ->where('status', 'active')
            ->where('role', 'captain')
            ->exists();

        if (! $isActiveCaptain) {
            throw new AuthorizationException(__("Y'a que le createur ou le capitaine de l'equipe qui peuvent demander un match,modifier ou annuler une demande de match"));
        }
    }

    /**
     * @return array<int,int>
     */
    private function manageableTeamIdsForUser(int $userId): array
    {
        $createdIds = DB::table('teams')
            ->where('creator_id', $userId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $captainIds = DB::table('team_members')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('role', 'captain')
            ->pluck('team_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($createdIds, $captainIds)));
    }

    /**
     * Calcule en une requête groupée le nombre de membres actifs pour une liste d'équipes.
     *
     * @param  array<int, int>  $teamIds
     * @return array<int, int>
     */
    private function activeMemberCountsForTeamIds(array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }

        $rows = DB::table('team_members')
            ->whereIn('team_id', $teamIds)
            ->where('status', 'active')
            ->groupBy('team_id')
            ->selectRaw('team_id, count(*) as c')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->team_id] = (int) $r->c;
        }

        return $map;
    }

    /**
     * Formate une équipe pour la vue liste ("mes équipes").
     *
     * @param  array<int, int>  $countsMap
     */
    private function formatListRow(object $row, array $countsMap): array
    {
        $id = (int) $row->id;

        return [
            'id' => $id,
            'name' => $row->name,
            'slug' => $row->slug,
            'sport_id' => (int) $row->sport_id,
            'description' => $row->description,
            'hq_city' => $row->hq_city,
            'hq_latitude' => $row->hq_latitude !== null ? (float) $row->hq_latitude : null,
            'hq_longitude' => $row->hq_longitude !== null ? (float) $row->hq_longitude : null,
            'cover_image_url' => PublicImageUrl::from($row->cover_image_url),
            'logo_url' => PublicImageUrl::from($row->logo_url),
            'competition_type' => $row->competition_type,
            'skill_level' => $row->skill_level,
            'members_count' => (int) ($countsMap[$id] ?? 0),
            'created_at' => $row->created_at,
        ];
    }

    /**
     * Formate une équipe pour la vue détail.
     *
     * @return array<string, mixed>
     */
    private function formatDetailRow(object $row, int $membersCount): array
    {
        return [
            'id' => (int) $row->id,
            'creator_id' => (int) $row->creator_id,
            'name' => $row->name,
            'slug' => $row->slug,
            'sport' => [
                'id' => (int) $row->sport_id,
                'name' => $row->sport_name,
                'slug' => $row->sport_slug,
                'practice_type' => $row->sport_practice_type,
            ],
            'description' => $row->description,
            'hq_city' => $row->hq_city,
            'hq_latitude' => $row->hq_latitude !== null ? (float) $row->hq_latitude : null,
            'hq_longitude' => $row->hq_longitude !== null ? (float) $row->hq_longitude : null,
            'cover_image_url' => PublicImageUrl::from($row->cover_image_url),
            'logo_url' => PublicImageUrl::from($row->logo_url),
            'competition_type' => $row->competition_type,
            'skill_level' => $row->skill_level,
            'members_count' => $membersCount,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ];
    }

    /**
     * Génère un slug unique à partir du nom d'équipe, avec suffixe incrémental si nécessaire.
     */
    private function allocateUniqueSlug(string $fromName, ?int $exceptTeamId = null): string
    {
        $base = Str::slug($fromName);
        if ($base === '') {
            $base = 'equipe';
        }

        $slug = $base;
        $n = 0;

        while (DB::table('teams')
            ->where('slug', $slug)
            ->when($exceptTeamId !== null, static fn ($q) => $q->where('id', '!=', $exceptTeamId))
            ->exists()
        ) {
            $n++;
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    /**
     * @throws ValidationException
     */
    private function ensureTeamIsCollective(Team $team): void
    {
        $practiceType = DB::table('sports')
            ->where('id', $team->sport_id)
            ->value('practice_type');

        if ($practiceType !== 'collective') {
            throw ValidationException::withMessages([
                'team_id' => __('Seules les équipes de sport collectif acceptent des intégrations.'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureUserCanJoinSport(int $userId, int $sportId, int $exceptTeamId): void
    {
        $alreadyHasSportTeam = DB::table('team_members')
            ->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('team_members.user_id', $userId)
            ->where('team_members.status', 'active')
            ->where('teams.sport_id', $sportId)
            ->where('teams.id', '!=', $exceptTeamId)
            ->exists();

        if ($alreadyHasSportTeam) {
            throw ValidationException::withMessages([
                'sport_id' => __('Un utilisateur ne peut pas avoir deux équipes actives du même sport.'),
            ]);
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureCanManageIntegrations(Team $team, int $actorUserId): void
    {
        if ((int) $team->creator_id === $actorUserId) {
            return;
        }

        $isActiveCaptain = DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $actorUserId)
            ->where('status', 'active')
            ->where('role', 'captain')
            ->exists();

        if (! $isActiveCaptain) {
            throw new AuthorizationException(__('Action non autorisée pour cette équipe.'));
        }
    }

    private function cacheUserSportId(int $userId, int $sportId): void
    {
        $cacheKey = 'register:user:sports:'.$userId;
        $cachedSportIds = Cache::store('app_main_cache')->get($cacheKey, []);
        $normalizedSportIds = collect(is_array($cachedSportIds) ? $cachedSportIds : [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->push($sportId)
            ->unique()
            ->values()
            ->all();

        Cache::store('app_main_cache')->forever($cacheKey, $normalizedSportIds);
    }
}
