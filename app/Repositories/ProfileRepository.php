<?php

namespace App\Repositories;

use App\Models\User;

class ProfileRepository
{
    /**
     * Fetch a user with their client(s) and areas for the profile page.
     *
     * @param  int   $userId
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getProfile(int $userId): User
    {
        return User::select(['id', 'name', 'email', 'phone', 'id_client', 'occupation', 'description'])
            ->with([
                'client:id,nama',
                'clientAccess.client:id,nama',
                'areas:master_area.id,nama',
            ])
            ->findOrFail($userId);
    }
}
