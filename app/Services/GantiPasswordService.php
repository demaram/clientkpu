<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class GantiPasswordService
{
    private UserRepository $userRepository;

    /**
     * @param  UserRepository  $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Verify the current password and update it if correct.
     *
     * @param  int     $userId
     * @param  string  $currentPassword  Plain-text current password from the request
     * @param  string  $currentHash      Hashed password currently stored in DB
     * @param  string  $newPassword      Plain-text new password
     * @return bool  false if current password does not match
     */
    public function changePassword(int $userId, string $currentPassword, string $currentHash, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $currentHash)) {
            return false;
        }

        $this->userRepository->updatePassword($userId, Hash::make($newPassword));

        return true;
    }
}
