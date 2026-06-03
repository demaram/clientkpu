<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    /**
     * Update the hashed password for a user.
     *
     * @param  int     $userId
     * @param  string  $hashedPassword  Already-hashed value (e.g. from Hash::make)
     * @return bool
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return User::where('id', $userId)
            ->update(['password' => $hashedPassword]);
    }
}
