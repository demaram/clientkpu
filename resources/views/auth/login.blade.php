@extends('adminlte::auth.login')

@section('auth_header', 'Client Dashboard Login')

@section('auth_body')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form action="{{ route('login') }}" method="post">
        @csrf

        <div class="input-group mb-3">
            <input type="email" 
                   name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" 
                   placeholder="Email"
                   required
                   autofocus>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope"></span>
                </div>
            </div>
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="input-group mb-3">
            <input type="password" 
                   name="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   placeholder="Password"
                   required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                </div>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="row">
            <div class="col-8">
                <div class="icheck-primary">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">
                        Ingat Saya
                    </label>
                </div>
            </div>
            <div class="col-4">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
        </div>
    </form>

    <p class="mb-0 mt-3">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> Gunakan akun yang terdaftar di sistem payroll
        </small>
    </p>
@stop
