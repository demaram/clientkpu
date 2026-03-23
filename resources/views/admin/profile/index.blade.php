@extends('adminlte::page')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Informasi Akun</h3>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">Nama</th>
                                <td>: {{ $profile['nama'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>: {{ $profile['email'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Nomor Telepon / HP</th>
                                <td>: {{ $profile['phone'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Perusahaan</th>
                                <td>: {{ $profile['perusahaan'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Lokasi (Area)</th>
                                <td>:
                                    @forelse($profile['lokasi'] as $area)
                                        <span class="badge badge-secondary mr-1">{{ $area }}</span>
                                    @empty
                                        -
                                    @endforelse
                                </td>
                            </tr>
                            <tr>
                                <th>Jabatan</th>
                                <td>: {{ $profile['jabatan'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Deskripsi</th>
                                <td>: {{ $profile['deskripsi'] ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
