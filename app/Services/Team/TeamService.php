<?php

namespace App\Services\Team;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
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

        return DB::transaction(function () use ($creatorId, $data, $slug): Team {
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
    }

    /**
     * Met à jour de manière partielle une équipe existante et régénère le slug si le nom change.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTeam(Team $team, array $data): void
    {
        $updates = [];

        foreach ([
            'description' => 'description',
            'hq_city' => 'hq_city',
            'hq_latitude' => 'hq_latitude',
            'hq_longitude' => 'hq_longitude',
            'cover_image_url' => 'cover_image_url',
            'logo_url' => 'logo_url',
            'competition_type' => 'competition_type',
            'skill_level' => 'skill_level',
        ] as $key => $col) {
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
    }

    /**
     * Supprime une équipe (les membres liés sont supprimés en cascade via la FK).
     */
    public function deleteTeam(Team $team): void
    {
        DB::table('teams')->where('id', $team->id)->delete();
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
            'cover_image_url' => $row->cover_image_url,
            'logo_url' => $row->logo_url,
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
            'cover_image_url' => $row->cover_image_url,
            'logo_url' => $row->logo_url,
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
            ->exists()) {
            $n++;
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
