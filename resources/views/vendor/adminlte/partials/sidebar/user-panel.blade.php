@auth
<div class="user-panel mt-3 pb-3 mb-3 d-flex flex-column align-items-center">
    {{-- Avatar --}}
    <div class="mb-1">
        @if(auth()->user()->photo)
            <img src="{{ asset('storage/' . auth()->user()->photo) }}"
                 class="img-circle elevation-2"
                 alt="{{ auth()->user()->name }}"
                 style="width:60px;height:60px;object-fit:cover;">
        @else
            <i class="fas fa-user-circle fa-4x text-white" style="opacity:0.85;"></i>
        @endif
    </div>

    {{-- Nama User --}}
    <div class="info mb-2">
        <span class="d-block text-white text-center font-weight-bold" style="font-size:0.95rem;">
            {{ auth()->user()->name }}
        </span>
    </div>

    {{-- Aksi: Settings & Logout --}}
    <div class="d-flex align-items-center" style="gap:16px;">

        {{-- Settings dropdown --}}
        <div class="dropdown">
            <a href="#" class="text-white" data-toggle="dropdown" title="Setting Akun" style="opacity:0.85;">
                <i class="fas fa-cog fa-lg"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" style="min-width:160px;">
                <a class="dropdown-item" href="{{ route('admin.profile.index') }}">
                    <i class="fas fa-user fa-fw mr-2"></i> Profile
                </a>
                <a class="dropdown-item" href="{{ route('admin.ganti-password.index') }}">
                    <i class="fas fa-key fa-fw mr-2"></i> Ganti Password
                </a>
            </div>
        </div>

        {{-- Logout --}}
        <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-link p-0 text-white" title="Logout" style="opacity:0.85;">
                <i class="fas fa-power-off fa-lg"></i>
            </button>
        </form>

    </div>
</div>
@endauth
