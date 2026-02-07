@extends('layouts.admin')

@section('title', 'Chỉnh sửa thanh toán #' . $payment->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Chỉnh sửa thanh toán #{{ $payment->id }}</h1>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>

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
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin thanh toán</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.payments.update', $payment) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="amount" class="form-label fw-bold">Số tiền (VNĐ)</label>
                            <input type="number" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" 
                                   name="amount" 
                                   value="{{ old('amount', $payment->amount) }}"
                                   step="1000"
                                   min="0">
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="method" class="form-label fw-bold">Phương thức thanh toán</label>
                            <input type="text" 
                                   class="form-control @error('method') is-invalid @enderror" 
                                   id="method" 
                                   name="method" 
                                   value="{{ old('method', $payment->method) }}"
                                   placeholder="e.g., credit_card, bank_transfer, cash">
                            @error('method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold">Trạng thái</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status">
                                <option value="pending" @selected(old('status', $payment->status) === 'pending')>Chờ thanh toán</option>
                                <option value="paid" @selected(old('status', $payment->status) === 'paid')>Đã thanh toán</option>
                                <option value="failed" @selected(old('status', $payment->status) === 'failed')>Thất bại</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-outline-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if($payment->booking)
        <div class="col-lg-4">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin đặt phòng</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Mã đặt phòng:</strong> 
                        <a href="{{ route('admin.bookings.show', $payment->booking) }}">#{{ $payment->booking->id }}</a>
                    </p>
                    
                    @if($payment->booking->user)
                    <p class="mb-2">
                        <strong>Khách hàng:</strong><br>
                        {{ $payment->booking->user->full_name }}<br>
                        <small class="text-muted">{{ $payment->booking->user->email }}</small>
                    </p>
                    @endif

                    @if($payment->booking->room)
                    <p class="mb-2">
                        <strong>Phòng:</strong> {{ $payment->booking->room->name }}
                    </p>
                    @endif

                    @if($payment->booking->check_in && $payment->booking->check_out)
                    <p class="mb-2">
                        <strong>Ngày nhận/trả:</strong><br>
                        {{ (is_string($payment->booking->check_in) ? \Carbon\Carbon::parse($payment->booking->check_in)->format('d/m/Y') : $payment->booking->check_in->format('d/m/Y')) }} - {{ (is_string($payment->booking->check_out) ? \Carbon\Carbon::parse($payment->booking->check_out)->format('d/m/Y') : $payment->booking->check_out->format('d/m/Y')) }}
                    </p>
                    @endif

                    @if($payment->booking->total_price)
                    <p class="mb-0">
                        <strong>Tổng tiền:</strong><br>
                        <span class="h6 text-success">{{ number_format($payment->booking->total_price, 0, ',', '.') }} VNĐ</span>
                    </p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
