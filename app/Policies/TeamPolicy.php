<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $this->isActiveMember($team->id, $user->id);
    }

    public function update(User $user, Team $team): bool
    {
        if ((int) $team->creator_id === (int) $user->id) {
            return true;
        }

        return DB::table('team_members')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('role', 'captain')
            ->exists();
    }

    public function delete(User $user, Team $team): bool
    {
        return (int) $team->creator_id === (int) $user->id;
    }

    private function isActiveMember(int $teamId, int $userId): bool
    {
        return DB::table('team_members')
            ->where('team_id', $teamId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }
}
