@extends('adminlte::page')

@section('title', 'Edit Data Piket')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">Edit Data Piket</h1>
        <ol class="breadcrumb float-sm-right mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.piket.index') }}">Data Piket</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>
@endsection

@section('content')

    {{-- Flash alerts --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Terdapat kesalahan:</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Form Edit Data Piket</h3>
                </div>

                <form method="POST" action="{{ route('admin.piket.update', $piket->id) }}" id="formEditPiket" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf

                    <div class="card-body">

                        {{-- Read-only info block --}}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Karyawan</label>
                            <div class="col-sm-8">
                                <p class="form-control-static mt-2">
                                    {{ $piket->user ? $piket->user->first_name . ' ' . $piket->user->last_name : '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Status</label>
                            <div class="col-sm-8">
                                <p class="form-control-static mt-2">
                                    <span class="badge badge-secondary">Waiting Approval</span>
                                </p>
                            </div>
                        </div>

                        <hr>

                        {{-- Editable: start --}}
                        <div class="form-group row">
                            <label for="start" class="col-sm-4 col-form-label font-weight-bold">
                                Waktu Mulai <span class="text-danger">*</span>
                            </label>
                            <div class="col-sm-8">
                                <input
                                    type="datetime-local"
                                    id="start"
                                    name="start"
                                    class="form-control @error('start') is-invalid @enderror"
                                    value="{{ old('start', $piket->start ? date('Y-m-d\TH:i', strtotime($piket->start)) : '') }}"
                                    required
                                >
                                @error('start')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Editable: end --}}
                        <div class="form-group row">
                            <label for="end" class="col-sm-4 col-form-label font-weight-bold">
                                Waktu Selesai <span class="text-danger">*</span>
                            </label>
                            <div class="col-sm-8">
                                <input
                                    type="datetime-local"
                                    id="end"
                                    name="end"
                                    class="form-control @error('end') is-invalid @enderror"
                                    value="{{ old('end', $piket->end ? date('Y-m-d\TH:i', strtotime($piket->end)) : '') }}"
                                    required
                                >
                                @error('end')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Waktu selesai harus setelah waktu mulai.</small>
                            </div>
                        </div>

                        <hr>

                        {{-- Foto Check-in --}}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Foto Check-in</label>
                            <div class="col-sm-8">
                                @if ($startPhotoUrl)
                                    <div class="mb-2">
                                        <img src="{{ $startPhotoUrl }}" alt="Foto Check-in"
                                             class="img-thumbnail" style="max-height: 160px;">
                                    </div>
                                    <input type="file" name="start_photo" class="form-control-file" disabled>
                                    <small class="text-muted">Foto sudah diupload, tidak dapat diubah.</small>
                                @else
                                    <input
                                        type="file"
                                        name="start_photo"
                                        class="form-control-file @error('start_photo') is-invalid @enderror"
                                        accept="image/*"
                                    >
                                    @error('start_photo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Format: JPG, PNG, GIF. Maks 5MB.</small>
                                @endif
                            </div>
                        </div>

                        {{-- Foto Check-out --}}
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Foto Check-out</label>
                            <div class="col-sm-8">
                                @if ($endPhotoUrl)
                                    <div class="mb-2">
                                        <img src="{{ $endPhotoUrl }}" alt="Foto Check-out"
                                             class="img-thumbnail" style="max-height: 160px;">
                                    </div>
                                    <input type="file" name="end_photo" class="form-control-file" disabled>
                                    <small class="text-muted">Foto sudah diupload, tidak dapat diubah.</small>
                                @else
                                    <input
                                        type="file"
                                        name="end_photo"
                                        class="form-control-file @error('end_photo') is-invalid @enderror"
                                        accept="image/*"
                                    >
                                    @error('end_photo')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Format: JPG, PNG, GIF. Maks 5MB.</small>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="btnSimpan">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                        <a href="{{ route('admin.piket.index') }}" class="btn btn-default ml-2">
                            <i class="fas fa-times mr-1"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('js')
<script>
    /**
     * Client-side validation: ensure end > start before form is submitted.
     * Server-side also validates, but this gives instant feedback.
     */
    document.getElementById('formEditPiket').addEventListener('submit', function (e) {
        var startVal = document.getElementById('start').value;
        var endVal   = document.getElementById('end').value;

        if (!startVal || !endVal) {
            return; // Let server-side required rule handle empty values
        }

        var startDate = new Date(startVal);
        var endDate   = new Date(endVal);

        if (endDate <= startDate) {
            e.preventDefault();
            alert('Waktu selesai harus lebih besar dari waktu mulai.');
            document.getElementById('end').focus();
        }
    });
</script>
@endsection
