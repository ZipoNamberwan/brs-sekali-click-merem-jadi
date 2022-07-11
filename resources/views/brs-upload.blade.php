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
    @if (session('success-upload'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
        <span class="alert-text"><strong>Sukses! </strong>{{ session('success-upload') }} </span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
    </div>
    @endif

    @if (session('error-upload'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="fas fa-check-circle"></i></span>
        <span class="alert-text"><strong>Sukses! </strong>{{ session('error-upload') }}</span>
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
                        <form autocomplete="off" method="post" action="/upload" class="needs-validation" enctype="multipart/form-data" novalidate>
                            @csrf
                            <div class="form-row">
                                <div class="col-md-8 mb-3">
                                    <div class="form-row">
                                        <div class="col-md-8">
                                            <label class="form-control-label" for="quantity">Upload Data Excel Inflasi</label>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-md-8">
                                            <div class="custom-file">
                                                <input type="file" name="file-inf" class="custom-file-input" id="file-inf" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf')">
                                                <label class="custom-file-label" id="inputlabel-file-inf" for="customFileLang">Pilih File</label>
                                            </div>
                                        </div>
                                        @error('file-inf')
                                        <div class="error-feedback">
                                            {{$message}}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-row mt-2">
                                        <div class="col-md-8">
                                            <label class="form-control-label" for="quantity">Upload Data Excel Inflasi per Kota</label>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-md-8">
                                            <div class="custom-file">
                                                <input type="file" name="file-inf-area" class="custom-file-input" id="file-inf-area" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-area')">
                                                <label class="custom-file-label" id="inputlabel-file-inf-area" for="customFileLang">Pilih File</label>
                                            </div>
                                        </div>
                                        @error('file-inf-area')
                                        <div class="error-feedback">
                                            {{$message}}
                                        </div>
                                        @enderror
                                    </div>
                                    <div class="form-row mt-2">
                                        <div class="col-md-8">
                                            <label class="form-control-label" for="quantity">Upload Data Excel Inflasi Energi dan Makanan</label>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="col-md-8">
                                            <div class="custom-file">
                                                <input type="file" name="file-inf-food-energy" class="custom-file-input" id="file-inf-food-energy" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-food-energy')">
                                                <label class="custom-file-label" id="inputlabel-file-inf-food-energy" for="customFileLang">Pilih File</label>
                                            </div>
                                        </div>
                                        @error('file-inf-food-energy')
                                        <div class="error-feedback">
                                            {{$message}}
                                        </div>
                                        @enderror
                                    </div>
                                    <button class="btn btn-primary mt-3" id="sbmtbtn" type="submit">Upload</button>
                                </div>
                            </div>
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
    function onChange(id) {
        var fileInput = document.getElementById(id);
        var filename = fileInput.files[0].name;
        document.getElementById('inputlabel-' + id).innerHTML = filename;
    }
</script>
@endsection