<?php

namespace App\Jobs\Team;

use App\Contracts\Stats\SeasonStrategy;
use App\Contracts\Stats\StatsRepository;
use App\Models\User;
use App\Notifications\TeamAverageProgressNotification;
use App\Notifications\TeamTopRankChangeNotification;
use App\Services\Notifications\ExpoPushService;
use App\Services\Stats\SeasonWindow;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ApplyValidatedMatchResultStatsJob implements ShouldQueue
{
    /** @var array<string, array{victory: int, draw: int, defeat: int}> */
    private const SPORT_POINTS = [
        'football' => ['victory' => 3, 'draw' => 1, 'defeat' => 0],
        'basketball' => ['victory' => 3, 'draw' => 1, 'defeat' => 0],
        'tennis' => ['victory' => 3, 'draw' => 0, 'defeat' => 0],
        'running' => ['victory' => 3, 'draw' => 0, 'defeat' => 0],
        'yoga' => ['victory' => 3, 'draw' => 0, 'defeat' => 0],
        'padel' => ['victory' => 3, 'draw' => 0, 'defeat' => 0],
    ];

    use Dispatchable;
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        private readonly int $homeTeamId,
        private readonly int $awayTeamId,
        private readonly int $homeScore,
        private readonly int $awayScore,
    ) {}

    /**
     * SeasonStrategy: calcule la fenetre de saison active (flexible selon l'implementation bindee).
     * StatsRepository: execute toutes les lectures/ecritures stats via Query Builder.
     */
    public function handle(
        ExpoPushService $expoPushService,
        SeasonStrategy $seasonStrategy,
        StatsRepository $statsRepository,
    ): void {
        $sport = DB::table('teams')
            ->join('sports', 'sports.id', '=', 'teams.sport_id')
            ->where('teams.id', $this->homeTeamId)
            ->select(['sports.id as sport_id', 'sports.slug'])
            ->first();

        if ($sport === null) {
            throw ValidationException::withMessages([
                'match_event_id' => __('Sport introuvable pour ce match.'),
            ]);
        }

        $sportId = (int) $sport->sport_id;
        $sportSlug = (string) $sport->slug;
        $points = self::SPORT_POINTS[$sportSlug] ?? ['victory' => 3, 'draw' => 1, 'defeat' => 0];
        $seasonWindow = $seasonStrategy->resolveWindowForDate(now()->toImmutable());
        $beforeSnapshots = $this->loadSnapshotsFromCacheFirst($sportId, $seasonWindow, $statsRepository);
        $now = now();

        if ($this->homeScore === $this->awayScore) {
            DB::transaction(function () use ($sportId, $seasonWindow, $points, $now, $statsRepository): void {
                $statsRepository->incrementTeamStats($this->homeTeamId, $sportId, $seasonWindow, 'draw_count', $points['draw'], $now);
                $statsRepository->incrementTeamStats($this->awayTeamId, $sportId, $seasonWindow, 'draw_count', $points['draw'], $now);
            });

            $afterSnapshots = $this->loadTeamSnapshots($sportId, $seasonWindow, $statsRepository);
            $this->storeSnapshotsInCache($sportId, $seasonWindow, $afterSnapshots);
            $this->notifyRankingAndAverageChanges($sportId, $beforeSnapshots, $afterSnapshots, $expoPushService);

            return;
        }

        if ($this->homeScore > $this->awayScore) {
            DB::transaction(function () use ($sportId, $seasonWindow, $points, $now, $statsRepository): void {
                $statsRepository->incrementTeamStats($this->homeTeamId, $sportId, $seasonWindow, 'victory_count', $points['victory'], $now);
                $statsRepository->incrementTeamStats($this->awayTeamId, $sportId, $seasonWindow, 'defeat_count', $points['defeat'], $now);
            });

            $afterSnapshots = $this->loadTeamSnapshots($sportId, $seasonWindow, $statsRepository);
            $this->storeSnapshotsInCache($sportId, $seasonWindow, $afterSnapshots);
            $this->notifyRankingAndAverageChanges($sportId, $beforeSnapshots, $afterSnapshots, $expoPushService);

            return;
        }

        DB::transaction(function () use ($sportId, $seasonWindow, $points, $now, $statsRepository): void {
            $statsRepository->incrementTeamStats($this->homeTeamId, $sportId, $seasonWindow, 'defeat_count', $points['defeat'], $now);
            $statsRepository->incrementTeamStats($this->awayTeamId, $sportId, $seasonWindow, 'victory_count', $points['victory'], $now);
        });

        $afterSnapshots = $this->loadTeamSnapshots($sportId, $seasonWindow, $statsRepository);
        $this->storeSnapshotsInCache($sportId, $seasonWindow, $afterSnapshots);
        $this->notifyRankingAndAverageChanges($sportId, $beforeSnapshots, $afterSnapshots, $expoPushService);
    }

    public function backoff(): array
    {
        return [10, 20, 30];
    }

    public function retryUntil(): DateTime
    {
        return (new DateTime)->add(new \DateInterval('PT24H'));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping(
                'apply-validated-match-result-stats-'.$this->homeTeamId.'-'.$this->awayTeamId
            ),
        ];
    }

    /**
     * @return array<int, array{team_id: int, team_name: string, point_count: int, rank: int|null, average: float}>
     */
    private function loadTeamSnapshots(int $sportId, SeasonWindow $seasonWindow, StatsRepository $statsRepository): array
    {
        $rankedStats = collect($statsRepository->loadTeamSnapshots($sportId, $seasonWindow, $this->trackedTeamIds()))
            ->keyBy(fn (array $snapshot): int => (int) $snapshot['team_id']);
        $maxPointCount = $statsRepository->maxPointCount($sportId, $seasonWindow);

        $snapshots = [];
        foreach ($this->trackedTeamIds() as $teamId) {
            /** @var array{team_id: int, team_name: string, point_count: int, rank: int|null}|null $snapshot */
            $snapshot = $rankedStats->get($teamId);
            $snapshots[$teamId] = [
                'team_id' => $teamId,
                'team_name' => (string) ($snapshot['team_name'] ?? ''),
                'point_count' => (int) ($snapshot['point_count'] ?? 0),
                'rank' => isset($snapshot['rank']) ? (int) $snapshot['rank'] : null,
                'average' => $this->calculateAverageOnTwenty(
                    (int) ($snapshot['point_count'] ?? 0),
                    $maxPointCount
                ),
            ];
        }

        return $snapshots;
    }

    /**
     * @param  array<int, array{team_id: int, team_name: string, point_count: int, rank: int|null, average: float}>  $beforeSnapshots
     * @param  array<int, array{team_id: int, team_name: string, point_count: int, rank: int|null, average: float}>  $afterSnapshots
     */
    private function notifyRankingAndAverageChanges(
        int $sportId,
        array $beforeSnapshots,
        array $afterSnapshots,
        ExpoPushService $expoPushService,
    ): void {
        // Parcourt uniquement les deux equipes impactees par ce resultat de match.
        foreach ($this->trackedTeamIds() as $teamId) {
            $before = $beforeSnapshots[$teamId] ?? null;
            $after = $afterSnapshots[$teamId] ?? null;
            // Ignore l'equipe si un snapshot est manquant (cache/DB incomplet).
            if ($before === null || $after === null) {
                continue;
            }

            // Determine la transition top 3 entre l'etat avant et apres mise a jour.
            $wasTopThree = $before['rank'] !== null && $before['rank'] <= 3;
            $isTopThree = $after['rank'] !== null && $after['rank'] <= 3;

            if ($wasTopThree !== $isTopThree) {
                $changeType = $isTopThree ? 'entered_top_3' : 'left_top_3';

                // Notifie les membres de l'equipe qui entre/sort du top 3.
                $this->notifyTeamMembersTopThreeChange(
                    $teamId,
                    $after['team_name'],
                    $before['rank'],
                    $after['rank'],
                    $changeType,
                    $expoPushService,
                );

                // Lors d'une entree top 3, diffuse aussi l'info a toutes les equipes du meme sport.
                if ($isTopThree) {
                    NotifySportTopRankChangeJob::dispatch(
                        $sportId,
                        $teamId,
                        $after['team_name'],
                        $before['rank'],
                        $after['rank'],
                        $changeType,
                    );
                }
            }

            // Notifie les paliers de progression de moyenne (>=5, >=10, >=15).
            $this->notifyAverageThresholdsReached(
                $teamId,
                $after['team_name'],
                $before['average'],
                $after['average'],
                $after['rank'],
                $expoPushService,
            );
        }
    }

    private function notifyTeamMembersTopThreeChange(
        int $teamId,
        string $teamName,
        ?int $beforeRank,
        ?int $afterRank,
        string $changeType,
        ExpoPushService $expoPushService,
    ): void {
        $members = $this->loadTeamMembers($teamId);
        if ($members->isEmpty()) {
            return;
        }

        Notification::send($members, new TeamTopRankChangeNotification(
            $teamId,
            $teamName,
            $beforeRank,
            $afterRank,
            $changeType,
        ));

        $newPosition = $afterRank !== null ? (string) $afterRank : 'non classe';
        $message = $changeType === 'entered_top_3'
            ? "Bravo ! {$teamName} entre dans le top 3."
            : "{$teamName} sort du top 3 et passe a la position {$newPosition}.";
        $this->sendPushToUsers($members, $teamName, $message, [
            'type' => 'team_top_rank_change',
            'team_id' => $teamId,
            'team_name' => $teamName,
            'before_rank' => $beforeRank,
            'after_rank' => $afterRank,
            'change_type' => $changeType,
            'message' => $message,
        ], $expoPushService);
    }

    private function notifyAverageThresholdsReached(
        int $teamId,
        string $teamName,
        float $beforeAverage,
        float $afterAverage,
        ?int $currentRank,
        ExpoPushService $expoPushService,
    ): void {
        // Parcourt les paliers de progression a celebrer.
        foreach ([5, 10, 15] as $threshold) {
            // Declenche seulement si l'equipe vient de franchir le palier.
            if ($beforeAverage < $threshold && $afterAverage >= $threshold) {
                // Charge les membres/creator de l'equipe qui recevront la notification.
                $members = $this->loadTeamMembers($teamId);
                if ($members->isEmpty()) {
                    continue;
                }

                // Envoie une notification de felicitation avec le palier atteint.
                Notification::send($members, new TeamAverageProgressNotification(
                    $teamId,
                    $teamName,
                    $threshold,
                    $beforeAverage,
                    $afterAverage,
                    $currentRank,
                ));

                $rankLabel = $currentRank !== null ? (string) $currentRank : 'non classe';
                $message = "Felicitation ! {$teamName} est maintenant classee #{$rankLabel} dans son sport.";
                $this->sendPushToUsers($members, $teamName, $message, [
                    'type' => 'team_average_progress',
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'threshold' => $threshold,
                    'before_average' => $beforeAverage,
                    'after_average' => $afterAverage,
                    'current_rank' => $currentRank,
                    'message' => $message,
                ], $expoPushService);
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function loadTeamMembers(int $teamId)
    {
        $memberIds = DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('status', 'active')
            ->pluck('user_id')
            ->merge([
                DB::table('teams')
                    ->where('id', $teamId)
                    ->value('creator_id'),
            ])
            ->filter(static fn ($id): bool => $id !== null)
            ->unique()
            ->values();

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $memberIds)->get();
    }

    /**
     * @return array<int, int>
     */
    private function trackedTeamIds(): array
    {
        return [$this->homeTeamId, $this->awayTeamId];
    }

    private function calculateAverageOnTwenty(int $pointCount, int $maxPointCount): float
    {
        if ($maxPointCount <= 0) {
            return 0.0;
        }

        return round(($pointCount * 20) / $maxPointCount, 2);
    }

    /**
     * @return array<int, array{team_id: int, team_name: string, point_count: int, rank: int|null, average: float}>
     */
    private function loadSnapshotsFromCacheFirst(int $sportId, SeasonWindow $seasonWindow, StatsRepository $statsRepository): array
    {
        $teamIds = $this->trackedTeamIds();
        $snapshots = [];
        $missingTeamIds = [];

        foreach ($teamIds as $teamId) {
            $cachedSnapshot = Cache::store('app_main_cache')->get($this->snapshotsCacheKey($sportId, $seasonWindow, $teamId));
            if (is_array($cachedSnapshot)) {
                $snapshots[$teamId] = $cachedSnapshot;
            } else {
                $missingTeamIds[] = $teamId;
            }
        }

        if ($missingTeamIds === []) {
            return $snapshots;
        }

        $freshSnapshots = $this->loadTeamSnapshots($sportId, $seasonWindow, $statsRepository);
        $this->storeSnapshotsInCache($sportId, $seasonWindow, $freshSnapshots);

        foreach ($freshSnapshots as $teamId => $snapshot) {
            $snapshots[$teamId] = $snapshot;
        }

        return $snapshots;
    }

    /**
     * @param  array<int, array{team_id: int, team_name: string, point_count: int, rank: int|null, average: float}>  $snapshots
     */
    private function storeSnapshotsInCache(int $sportId, SeasonWindow $seasonWindow, array $snapshots): void
    {
        foreach ($snapshots as $teamId => $snapshot) {
            Cache::store('app_main_cache')->forever(
                $this->snapshotsCacheKey($sportId, $seasonWindow, (int) $teamId),
                $snapshot
            );
        }
    }

    private function snapshotsCacheKey(int $sportId, SeasonWindow $seasonWindow, int $teamId): string
    {
        return 'team:stats:snapshot:sport:'.$sportId.':season:'.$seasonWindow->key.':team:'.$teamId;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    private function sendPushToUsers(
        Collection $users,
        string $title,
        string $message,
        array $data,
        ExpoPushService $expoPushService,
    ): void {
        $expoTokens = User::expoPushTokensFrom($users);

        if ($expoTokens === []) {
            return;
        }

        $expoPushService->send(
            $expoTokens,
            $title,
            $message,
            null,
            json_encode($data) ?: null,
        );
    }
}
