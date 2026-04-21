@extends('layouts.admin')

@section('content')
<div class="container-fluid px-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">Chỉnh sửa báo cáo hư hỏng</h3>

        <a href="{{ route('staff.damage-reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    {{-- LỖI VALIDATION --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Có lỗi xảy ra!</strong> Vui lòng kiểm tra lại dữ liệu.
        </div>
    @endif

    {{-- FORM --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            <form action="{{ route('staff.damage-reports.update', $report->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('staff.damage_reports._form', ['report' => $report])

            </form>

        </div>
    </div>

</div>
@endsection