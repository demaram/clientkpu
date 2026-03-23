<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return User::where('id', $userId)
            ->update(['password' => $hashedPassword]);
    }
}
