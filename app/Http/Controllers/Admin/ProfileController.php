<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private ProfileService $profileService;

    /**
     * @param  ProfileService  $profileService
     */
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Show the profile page for the logged-in user.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $profile = $this->profileService->getProfile(Auth::id());

        return view('admin.profile.index', compact('profile'));
    }
}
