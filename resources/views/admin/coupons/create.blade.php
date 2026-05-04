@extends('layouts.admin')

@section('title', 'Thêm Mã giảm giá mới')

@section('content')
    <div class="page-header mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('admin.coupons.index') }}" class="text-decoration-none text-muted fs-5 me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            Thêm Mã giảm giá mới
        </h1>
    </div>

    <div class="card card-admin">
        <div class="card-body p-4">
            <form action="{{ route('admin.coupons.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Mã giảm giá (Code) <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required autofocus>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Khuyến mãi (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="discount_percent" class="form-control @error('discount_percent') is-invalid @enderror" value="{{ old('discount_percent') }}" min="0" max="100" required>
                            <span class="input-group-text">%</span>
                            @error('discount_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Ngày hết hạn (tùy chọn)</label>
                        <input type="date" name="expired_at" class="form-control @error('expired_at') is-invalid @enderror" value="{{ old('expired_at') }}" min="{{ date('Y-m-d') }}">
                        @error('expired_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold ms-2" for="is_active">Kích hoạt mã giảm giá</label>
                        </div>
                    </div>

                    <div class="col-12 mt-5 text-end admin-action-row justify-content-end">
                        <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary btn-admin-icon" title="Hủy"><i class="bi bi-x-lg"></i></a>
                        <button type="submit" class="btn btn-primary" title="Lưu"><i class="bi bi-check2-lg"></i> Lưu</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
