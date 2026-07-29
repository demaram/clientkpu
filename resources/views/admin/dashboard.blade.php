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
            <form method="GET" action="{{ route('admin.dashboard') }}" class="form-inline">
                <label for="month" class="mr-2">Periode</label>
                <input type="month" id="month" name="month" class="form-control mr-2" value="{{ $month }}">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Filter</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h5 class="mt-3 mb-2">Lembur</h5>
            <div class="row">
                <div class="col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $lemburCounts['pending'] ?? 0 }}</h3>
                            <p>On Process</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="{{ route('admin.lembur.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $lemburCounts['waiting_approval'] ?? 0 }}</h3>
                            <p>Waiting Approval</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <a href="{{ route('admin.lembur.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $lemburCounts['approved'] ?? 0 }}</h3>
                            <p>Approved</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="{{ route('admin.lembur.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ $lemburCounts['rejected'] ?? 0 }}</h3>
                            <p>Rejected</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <a href="{{ route('admin.lembur.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <h5 class="mt-3 mb-2">Piket</h5>
            <div class="row">
                <div class="col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $piketCounts['pending'] ?? 0 }}</h3>
                            <p>On Process</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="{{ route('admin.piket.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>{{ $piketCounts['waiting_approval'] ?? 0 }}</h3>
                            <p>Waiting Approval</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <a href="{{ route('admin.piket.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $piketCounts['approved'] ?? 0 }}</h3>
                            <p>Approved</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="{{ route('admin.piket.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <div class="col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ $piketCounts['rejected'] ?? 0 }}</h3>
                            <p>Rejected</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <a href="{{ route('admin.piket.index') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Layer 1 chart — visible to step-1 approvers only --}}
    @if($isStep1Approver && $chartLayer1Data)
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Ajuan Lembur per Bulan - Approval Layer 1
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="lemburLayer1Chart" style="min-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Layer 2 chart — visible to step-2 approvers only --}}
    @if($isStep2Approver && $chartLayer2Data)
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Ajuan Lembur per Bulan - Approval Layer 2
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="lemburLayer2Chart" style="min-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Monthly total-pay chart — visible to recap users only --}}
    @if($isRecapUser && $chartData)
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Total Lembur Dibayar per Bulan
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="lemburRekapChart" style="min-height: 280px;"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

@stop

@section('css')
    {{-- Add here extra stylesheets --}}
@stop

@section('js')
    <script>
        @if($isStep1Approver && $chartLayer1Data)
        (function () {
            var ctx = document.getElementById('lemburLayer1Chart').getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLayer1Data['labels']) !!},
                    datasets: [{
                        label: 'Jumlah Ajuan Lembur',
                        data: {!! json_encode($chartLayer1Data['values']) !!},
                        backgroundColor: 'rgba(60, 141, 188, 0.75)',
                        borderColor: 'rgba(60, 141, 188, 1)',
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                precision: 0,
                            }
                        }]
                    }
                }
            });
        })();
        @endif

        @if($isStep2Approver && $chartLayer2Data)
        (function () {
            var ctx = document.getElementById('lemburLayer2Chart').getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartLayer2Data['labels']) !!},
                    datasets: [{
                        label: 'Jumlah Ajuan Lembur',
                        data: {!! json_encode($chartLayer2Data['values']) !!},
                        backgroundColor: 'rgba(0, 166, 90, 0.75)',
                        borderColor: 'rgba(0, 166, 90, 1)',
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                precision: 0,
                            }
                        }]
                    }
                }
            });
        })();
        @endif

        @if($isRecapUser && $chartData)
        (function () {
            var ctx = document.getElementById('lemburRekapChart').getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartData['labels']) !!},
                    datasets: [{
                        label: 'Total Pay (Rp)',
                        data: {!! json_encode($chartData['values']) !!},
                        backgroundColor: 'rgba(243, 156, 18, 0.75)',
                        borderColor: 'rgba(243, 156, 18, 1)',
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function (value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return 'Rp ' + parseFloat(tooltipItem.yLabel).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            });
        })();
        @endif
    </script>
@stop
