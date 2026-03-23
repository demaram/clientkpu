<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class GantiPasswordService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function changePassword(int $userId, string $currentPassword, string $currentHash, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $currentHash)) {
            return false;
        }

        $this->userRepository->updatePassword($userId, Hash::make($newPassword));

        return true;
    }
}
