@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Cài đặt hệ thống</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <!-- General Settings -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin khách sạn</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update.general') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tên khách sạn</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name', $hotelInfo->name ?? '') }}"
                                       placeholder="Tên khách sạn">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       name="email"
                                       value="{{ old('email', $hotelInfo->email ?? '') }}"
                                       placeholder="Email của khách sạn">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Điện thoại</label>
                                <input type="text"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       name="phone"
                                       value="{{ old('phone', $hotelInfo->phone ?? '') }}"
                                       placeholder="Số điện thoại">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Địa chỉ</label>
                                <input type="text"
                                       class="form-control @error('address') is-invalid @enderror"
                                       name="address"
                                       value="{{ old('address', $hotelInfo->address ?? '') }}"
                                       placeholder="Địa chỉ">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      name="description"
                                      rows="4"
                                      placeholder="Mô tả về khách sạn">{{ old('description', $hotelInfo->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">🏦 Cấu hình thanh toán QR (VietQR)</h6>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Mã ngân hàng</label>
                                <input type="text"
                                       class="form-control @error('bank_id') is-invalid @enderror"
                                       name="bank_id"
                                       value="{{ old('bank_id', $hotelInfo->bank_id ?? '') }}"
                                       placeholder="VD: mbbank, vietcombank, vcb">
                                <small class="text-muted">VD: mbbank, vietcombank, techcombank...</small>
                                @error('bank_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Số tài khoản</label>
                                <input type="text"
                                       class="form-control @error('bank_account') is-invalid @enderror"
                                       name="bank_account"
                                       value="{{ old('bank_account', $hotelInfo->bank_account ?? '') }}"
                                       placeholder="Số tài khoản ngân hàng">
                                @error('bank_account')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tên chủ tài khoản</label>
                                <input type="text"
                                       class="form-control @error('bank_account_name') is-invalid @enderror"
                                       name="bank_account_name"
                                       value="{{ old('bank_account_name', $hotelInfo->bank_account_name ?? '') }}"
                                       placeholder="Tên chủ tài khoản">
                                @error('bank_account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if($hotelInfo && $hotelInfo->bank_id && $hotelInfo->bank_account)
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> QR Code sẽ được tạo tự động khi tạo đơn đặt phòng với phương thức thanh toán Chuyển khoản.
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-admin-icon" title="Lưu"><i class="bi bi-check2-lg"></i></button>
                    </form>
                </div>
            </div>

            <!-- Site Content Settings -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Nội dung trang web</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update.site.content') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tiêu đề trang chủ</label>
                            <input type="text" 
                                   class="form-control @error('home_title') is-invalid @enderror" 
                                   name="home_title" 
                                   value="{{ old('home_title') }}"
                                   placeholder="Tiêu đề trang chủ">
                            @error('home_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả trang chủ</label>
                            <textarea class="form-control @error('home_description') is-invalid @enderror" 
                                      name="home_description" 
                                      rows="4"
                                      placeholder="Mô tả trang chủ">{{ old('home_description') }}</textarea>
                            @error('home_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-admin-icon" title="Lưu"><i class="bi bi-check2-lg"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
