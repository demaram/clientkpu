<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GantiPasswordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GantiPasswordController extends Controller
{
    private GantiPasswordService $gantiPasswordService;

    public function __construct(GantiPasswordService $gantiPasswordService)
    {
        $this->gantiPasswordService = $gantiPasswordService;
    }

    public function index(): View
    {
        return view('admin.ganti-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password lama harus diisi',
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password baru minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ]);

        $user = Auth::user();

        $result = $this->gantiPasswordService->changePassword(
            $user->id,
            $request->input('current_password'),
            $user->password,
            $request->input('password')
        );

        if (!$result) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai']);
        }

        return redirect()
            ->route('admin.ganti-password.index')
            ->with('success', 'Password berhasil diubah');
    }
}
