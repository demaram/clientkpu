<?php

namespace App\Repositories;

use App\Models\User;

class ProfileRepository
{
    public function getProfile(int $userId): User
    {
        return User::select(['id', 'name', 'email', 'phone', 'id_client', 'occupation', 'description'])
            ->with([
                'client:id,nama',
                'areas:master_area.id,nama',
            ])
            ->findOrFail($userId);
    }
}
