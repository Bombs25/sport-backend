<?php

namespace App\Services\Auth;

use App\Models\User;

class PasswordChangeService
{
    public function changePassword(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => $newPassword])->save();
    }
}
