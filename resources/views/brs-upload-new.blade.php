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
        <span class="alert-text"><strong>Gagal! </strong>{{ session('error-upload') }}</span>
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
                        <h3 class="mb-0">Upload Data BRS</h3>
                    </div>
                    <!-- Card body -->
                    <div class="card-body">
                        <div class="form-row">
                            <div class="col-md-4 mb-3">
                                <label class="form-control-label" for="validationCustom03">Bulan</label>
                                <select onchange="handleSelectChange(this)" class="form-control @error('month') is-invalid @enderror" data-toggle="select" name="month">
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
                        <div class="table-responsive">
                            <table class="table" id="datatable-id" width="100%">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 15%;">Jenis Data</th>
                                        <th style="width: 7%;">Status Upload</th>
                                        <th style="width: 40%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Data Inflasi {{$current}}</td>
                                        <td>
                                            @if($currentstatus)
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-check-bold text-success"></i>
                                                <h4 class="mb-0"><span class="badge badge-success">Sudah</span></h4>
                                            </div>
                                            @else
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-fat-remove text-danger"></i>
                                                <h4 class="mb-0"><span class="badge badge-danger">Belum</span></h4>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form class='d-flex align-items-center' autocomplete="off" method="post" action="/upload-inf" class="needs-validation" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <div class="custom-file">
                                                    <input type="file" name="file-inf" class="custom-file-input" id="file-inf-current" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-current')">
                                                    <label class="custom-file-label" id="inputlabel-file-inf-current" for="customFileLang">Pilih File</label>
                                                </div>
                                                <input type="hidden" name="label" value="{{$current}}" />
                                                <button class="btn btn-primary ml-1" id="sbmtbtn-file-inf-current" type="submit">Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Data Inflasi {{$previousMonth}}</td>
                                        <td>
                                            @if($previousMonthstatus)
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-check-bold text-success"></i>
                                                <h4 class="mb-0"><span class="badge badge-success">Sudah</span></h4>
                                            </div>
                                            @else
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-fat-remove text-danger"></i>
                                                <h4 class="mb-0"><span class="badge badge-danger">Belum</span></h4>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form class='d-flex align-items-center' autocomplete="off" method="post" action="/upload-inf" class="needs-validation" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <div class="custom-file">
                                                    <input type="file" name="file-inf" class="custom-file-input" id="file-inf-last" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-last')">
                                                    <label class="custom-file-label" id="inputlabel-file-inf-last" for="customFileLang">Pilih File</label>
                                                </div>
                                                <input type="hidden" name="label" value="{{$previousMonth}}" />
                                                <button class="btn btn-primary ml-1" id="sbmtbtn-file-inf-last" type="submit">Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Data Inflasi {{$n1}}</td>
                                        <td>
                                            @if($n1status)
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-check-bold text-success"></i>
                                                <h4 class="mb-0"><span class="badge badge-success">Sudah</span></h4>
                                            </div>
                                            @else
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-fat-remove text-danger"></i>
                                                <h4 class="mb-0"><span class="badge badge-danger">Belum</span></h4>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form class='d-flex align-items-center' autocomplete="off" method="post" action="/upload-inf" class="needs-validation" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <div class="custom-file">
                                                    <input type="file" name="file-inf" class="custom-file-input" id="file-inf-n1" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-n1')">
                                                    <label class="custom-file-label" id="inputlabel-file-inf-n1" for="customFileLang">Pilih File</label>
                                                </div>
                                                <input type="hidden" name="label" value="{{$n1}}" />
                                                <button class="btn btn-primary ml-1" id="sbmtbtn-file-inf-n1" type="submit">Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Data Inflasi {{$n2}}</td>
                                        <td>
                                            @if($n2status)
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-check-bold text-success"></i>
                                                <h4 class="mb-0"><span class="badge badge-success">Sudah</span></h4>
                                            </div>
                                            @else
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-fat-remove text-danger"></i>
                                                <h4 class="mb-0"><span class="badge badge-danger">Belum</span></h4>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form class='d-flex align-items-center' autocomplete="off" method="post" action="/upload-inf" class="needs-validation" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <div class="custom-file">
                                                    <input type="file" name="file-inf" class="custom-file-input" id="file-inf-n2" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-n2')">
                                                    <label class="custom-file-label" id="inputlabel-file-inf-n2" for="customFileLang">Pilih File</label>
                                                </div>
                                                <input type="hidden" name="label" value="{{$n1}}" />
                                                <button class="btn btn-primary ml-1" id="sbmtbtn-file-inf-n2" type="submit">Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Data Inflasi 11 Kab/Kot di Jatim</td>
                                        <td>
                                            @if($arealength > 0)
                                            <div class="d-flex align-items-center">
                                                <h4 class="mb-0">
                                                    <span class="badge badge-success" data-toggle="tooltip" data-placement="top" title="{{$areanames}}">{{$arealength}} Sudah Upload</span>
                                                </h4>
                                                <i class="ni ni-chat-round text-success ml-1" data-toggle="tooltip" data-placement="top" title="{{$areanames}}"></i>
                                            </div>
                                            @else
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-fat-remove text-danger"></i>
                                                <h4 class="mb-0"><span class="badge badge-danger">Belum</span></h4>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form class='d-flex align-items-center' autocomplete="off" method="post" action="/upload-area" class="needs-validation" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <div class="custom-file">
                                                    <input type="file" name="file-inf-area[]" class="custom-file-input" multiple id="file-inf-area" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-area')">
                                                    <label class="custom-file-label" id="inputlabel-file-inf-area" for="customFileLang">Pilih File</label>
                                                </div>
                                                <input type="hidden" name="label" id="totalfiles" />
                                                <button class="btn btn-primary ml-1" id="sbmtbtn-file-inf-area" type="submit">Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Data IHK 2023 (2022 = 100)</td>
                                        <td>
                                            @if($newbase)
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-check-bold text-success"></i>
                                                <h4 class="mb-0"><span class="badge badge-success">Sudah</span></h4>
                                            </div>
                                            @else
                                            <div class="d-flex align-items-center">
                                                <i class="ni ni-fat-remove text-danger"></i>
                                                <h4 class="mb-0"><span class="badge badge-danger">Belum</span></h4>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <form class='d-flex align-items-center' autocomplete="off" method="post" action="/upload-base" class="needs-validation" enctype="multipart/form-data" novalidate>
                                                @csrf
                                                <div class="custom-file">
                                                    <input type="file" name="file-inf-base" class="custom-file-input" id="file-inf-base" lang="en" accept=".xlsx,.xls" onchange="onChange('file-inf-base')">
                                                    <label class="custom-file-label" id="inputlabel-file-inf-base" for="customFileLang">Pilih File</label>
                                                </div>
                                                <button class="btn btn-primary ml-1" id="sbmtbtn-file-inf-base" type="submit">Upload</button>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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

        var filename = ''
        if (fileInput.files.length > 0) {
            filename = fileInput.files.length + ' file terpilih'
            document.getElementById('totalfiles').value = fileInput.files.length;
        } else {
            filename = fileInput.files[0].name;
        }

        document.getElementById('inputlabel-' + id).innerHTML = filename;

        var fileInput = document.getElementById(id);
        var submitButton = document.getElementById('sbmtbtn-' + id);

        // Check if any file is selected
        if (fileInput.files.length > 0) {
            submitButton.disabled = false; // Enable the submit button
        } else {
            submitButton.disabled = true; // Disable the submit button
        }
    }

    // Initialize function to disable the submit button on page load
    window.onload = function() {
        document.getElementById('sbmtbtn-file-inf-current').disabled = true;
        document.getElementById('sbmtbtn-file-inf-last').disabled = true;
        document.getElementById('sbmtbtn-file-inf-n1').disabled = true;
        document.getElementById('sbmtbtn-file-inf-n2').disabled = true;
        document.getElementById('sbmtbtn-file-inf-area').disabled = true;
        document.getElementById('sbmtbtn-file-inf-base').disabled = true;
    };

    function handleSelectChange(selectElement) {
        const selectedValue = selectElement.value;
        if (selectedValue) {
            window.location.href = '/upload-new/' + selectedValue;
        }
    }
</script>
@endsection