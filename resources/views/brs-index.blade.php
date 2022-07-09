@extends('main')
@section('stylesheet')
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- <link rel="stylesheet" href="/assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css"> -->
<link rel="stylesheet" href="/assets/vendor/datatables2/datatables.min.css" />
<link rel="stylesheet" href="/assets/vendor/@fortawesome/fontawesome-free/css/fontawesome.min.css" />
<link rel="stylesheet" href="/assets/style.css">
<link rel="stylesheet" href="/assets/vendor/select2/dist/css/select2.min.css">

@endsection

@section('container')
<div class="header bg-primary pb-6">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                            <li class="breadcrumb-item"><a href="#"><i class="ni ni-app"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">BRS</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--6">
    @if (session('error-generate'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
        <span class="alert-text"><strong>Error! </strong> {{ implode('; ',session('error-generate')) }} </span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
    </div>
    @endif
    <div class="row">
        <div class="col">
            <div class="card-wrapper">
                <!-- Custom form validation -->
                <div class="card">
                    <!-- Card header -->
                    <div class="card-header">
                        <h3 class="mb-0">Generate BRS</h3>
                    </div>
                    <!-- Card body -->
                    <div class="card-body">
                        <form autocomplete="off" method="post" onsubmit="return onSubmit()" action="/generate" class="needs-validation" enctype="multipart/form-data" novalidate>
                            @csrf
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-control-label" for="validationCustom03">Bulan</label>
                                    <select class="form-control @error('month') is-invalid @enderror" data-toggle="select" name="month">
                                        <option disabled selected>-- Pilih Bulan --</option>
                                        @foreach($months as $month)
                                        <option value="{{$month->id}}" {{ old('month', $currentmonth) == $month->id ? 'selected' : '' }}>{{$month->name}}</option>
                                        @endforeach
                                    </select>
                                    @error('month')
                                    <div class="invalid-feedback">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-control-label" for="validationCustom03">Tahun</label>
                                    <select class="form-control @error('year') is-invalid @enderror" data-toggle="select" name="year">
                                        <option disabled selected>-- Pilih Tahun --</option>
                                        @foreach($years as $year)
                                        <option value="{{$year->id}}" {{ old('year', $currentyear) == $year->id ? 'selected' : '' }}>{{$year->name}}</option>
                                        @endforeach
                                    </select>
                                    @error('year')
                                    <div class="invalid-feedback">
                                        {{$message}}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <button class="btn btn-primary mt-3" id="sbmtbtn" type="submit">Generate</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('optionaljs')
<script src="/assets/vendor/select2/dist/js/select2.min.js"></script>

<script>
    function onChange() {
        var fileInput = document.getElementById('customFileLang');
        var filename = fileInput.files[0].name;
        document.getElementById('inputlabel').innerHTML = filename;
    }
</script>
@endsection