@extends('adminlte::page')

@section('title', 'Dashboard Client')

@section('content_header')
    <h1>Dashboard Client - {{ $user['name'] ?? 'Guest' }}</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> Success!</h5>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informasi User</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px">Nama</th>
                            <td>{{ $user['name'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $user['email'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Company</th>
                            <td>{{ $user['company'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $user['phone'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Occupation</th>
                            <td>{{ $user['occupation'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Last Login</th>
                            <td>{{ $user['last_login'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Roles</th>
                            <td>
                                @if(isset($user['roles']) && is_array($user['roles']))
                                    @foreach($user['roles'] as $role)
                                        <span class="badge badge-primary">{{ $role }}</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>-</h3>
                    <p>Projects</p>
                </div>
                <div class="icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>-</h3>
                    <p>Employees</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>-</h3>
                    <p>Payroll</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>-</h3>
                    <p>Reports</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
        </div>

        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>65</h3>
                    <p>Unique Visitors</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Selamat Datang di Dashboard Admin</h3>
                </div>
                <div class="card-body">
                    <p>Ini adalah portal admin dashboard menggunakan Laravel dan AdminLTE.</p>
                    <p>Anda telah berhasil login ke sistem. Gunakan menu di sebelah kiri untuk navigasi.</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
@stop

@section('js')
    <script>
        console.log("Dashboard loaded!");
    </script>
@stop
