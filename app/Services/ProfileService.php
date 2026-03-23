<?php

namespace App\Services;

use App\Repositories\ProfileRepository;

class ProfileService
{
    private ProfileRepository $profileRepository;

    public function __construct(ProfileRepository $profileRepository)
    {
        $this->profileRepository = $profileRepository;
    }

    public function getProfile(int $userId): array
    {
        $user = $this->profileRepository->getProfile($userId);

        return [
            'nama'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'perusahaan' => $user->client->nama ?? '-',
            'lokasi'     => $user->areas->pluck('nama'),
            'jabatan'    => $user->occupation,
            'deskripsi'  => $user->description,
        ];
    }
}
