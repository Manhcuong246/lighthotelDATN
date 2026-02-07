@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Cài đặt hệ thống</h1>
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

    <div class="row">
        <div class="col-lg-8">
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
                                       class="form-control @error('hotel_name') is-invalid @enderror" 
                                       name="hotel_name" 
                                       value="{{ old('hotel_name') }}"
                                       placeholder="Tên khách sạn">
                                @error('hotel_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" 
                                       class="form-control @error('hotel_email') is-invalid @enderror" 
                                       name="hotel_email" 
                                       value="{{ old('hotel_email') }}"
                                       placeholder="Email của khách sạn">
                                @error('hotel_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Điện thoại</label>
                                <input type="text" 
                                       class="form-control @error('hotel_phone') is-invalid @enderror" 
                                       name="hotel_phone" 
                                       value="{{ old('hotel_phone') }}"
                                       placeholder="Số điện thoại">
                                @error('hotel_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Địa chỉ</label>
                                <input type="text" 
                                       class="form-control @error('hotel_address') is-invalid @enderror" 
                                       name="hotel_address" 
                                       value="{{ old('hotel_address') }}"
                                       placeholder="Địa chỉ">
                                @error('hotel_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả</label>
                            <textarea class="form-control @error('hotel_description') is-invalid @enderror" 
                                      name="hotel_description" 
                                      rows="4"
                                      placeholder="Mô tả về khách sạn">{{ old('hotel_description') }}</textarea>
                            @error('hotel_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
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

                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
