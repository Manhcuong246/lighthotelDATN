@extends('layouts.app')

@section('title', 'Yêu cầu hoàn tiền #' . $booking->id)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('account.bookings') }}">Lịch sử đặt phòng</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('account.bookings.show', $booking) }}">Đơn #{{ $booking->id }}</a></li>
                    <li class="breadcrumb-item active">Yêu cầu hoàn tiền</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-4 text-center">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2"></i>Yêu cầu hoàn tiền</h4>
                    <p class="mb-0 opacity-75 mt-1 small text-uppercase">Áp dụng cho đơn đặt phòng #{{ $booking->id }}</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <!-- Policy Info -->
                    <div class="alert alert-info border-0 rounded-3 shadow-sm mb-4 d-flex align-items-center">
                        <div class="bg-info bg-opacity-25 rounded-circle p-3 me-3 text-info">
                            <i class="bi bi-info-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">Chính sách hoàn tiền khi hủy phòng</h6>
                            <p class="mb-0 small opacity-75">
                                • Trước 24h so với giờ check-in (14:00): Hoàn 100%<br>
                                • Trong vòng 24h trước check-in: Hoàn 50%<br>
                                • Sau thời gian check-in: Không hoàn tiền
                            </p>
                        </div>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border text-center h-100">
                                <span class="text-muted small d-block mb-1 text-uppercase fw-semibold">Tỷ lệ hoàn tiền</span>
                                <span class="fs-2 fw-bold text-primary">{{ $calc['percentage'] }}%</span>
                                <div class="mt-2 small">
                                    @if($calc['percentage'] == 100)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">Hoàn 100% (trước 24h)</span>
                                    @elseif($calc['percentage'] == 50)
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2">Hoàn 50% (trong 24h)</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2">Không hoàn tiền (đã quá hạn)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border text-center h-100">
                                <span class="text-muted small d-block mb-1 text-uppercase fw-semibold">Số tiền ước tính</span>
                                <span class="fs-2 fw-bold text-success">{{ number_format($calc['amount'], 0, ',', '.') }} ₫</span>
                                <div class="mt-2 small text-muted">
                                    Dựa trên tổng tiền: {{ number_format($booking->total_price, 0, ',', '.') }} ₫
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('account.bookings.refund.submit', $booking) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Tên chủ tài khoản <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-primary"></i></span>
                                    <input type="text" name="account_name" class="form-control border-start-0 py-2 ps-0 @error('account_name') is-invalid @enderror" value="{{ old('account_name') }}" placeholder="VD: NGUYEN VAN A" required>
                                    @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small">Số tài khoản <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-hash text-primary"></i></span>
                                    <input type="text" name="account_number" class="form-control border-start-0 py-2 ps-0 @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="Nhập số tài khoản" required>
                                    @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted small">Tên ngân hàng <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-bank text-primary"></i></span>
                                    <select name="bank_name" class="form-select border-start-0 py-2 ps-0 @error('bank_name') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('bank_name') ? '' : 'selected' }}>-- Chọn ngân hàng --</option>
                                        <optgroup label="Phổ biến">
                                            <option value="Vietcombank" {{ old('bank_name') == 'Vietcombank' ? 'selected' : '' }}>Vietcombank (VCB)</option>
                                            <option value="Techcombank" {{ old('bank_name') == 'Techcombank' ? 'selected' : '' }}>Techcombank (TCB)</option>
                                            <option value="BIDV" {{ old('bank_name') == 'BIDV' ? 'selected' : '' }}>BIDV</option>
                                            <option value="VietinBank" {{ old('bank_name') == 'VietinBank' ? 'selected' : '' }}>VietinBank</option>
                                            <option value="Agribank" {{ old('bank_name') == 'Agribank' ? 'selected' : '' }}>Agribank</option>
                                            <option value="MB Bank" {{ old('bank_name') == 'MB Bank' ? 'selected' : '' }}>MB Bank (MB)</option>
                                            <option value="TPBank" {{ old('bank_name') == 'TPBank' ? 'selected' : '' }}>TPBank</option>
                                            <option value="ACB" {{ old('bank_name') == 'ACB' ? 'selected' : '' }}>ACB</option>
                                            <option value="VPBank" {{ old('bank_name') == 'VPBank' ? 'selected' : '' }}>VPBank</option>
                                        </optgroup>
                                        <optgroup label="Ngân hàng khác">
                                            <option value="Sacombank" {{ old('bank_name') == 'Sacombank' ? 'selected' : '' }}>Sacombank</option>
                                            <option value="HDBank" {{ old('bank_name') == 'HDBank' ? 'selected' : '' }}>HDBank</option>
                                            <option value="VIB" {{ old('bank_name') == 'VIB' ? 'selected' : '' }}>VIB</option>
                                            <option value="SHB" {{ old('bank_name') == 'SHB' ? 'selected' : '' }}>SHB</option>
                                            <option value="SeABank" {{ old('bank_name') == 'SeABank' ? 'selected' : '' }}>SeABank</option>
                                            <option value="MSB" {{ old('bank_name') == 'MSB' ? 'selected' : '' }}>MSB</option>
                                            <option value="OCB" {{ old('bank_name') == 'OCB' ? 'selected' : '' }}>OCB</option>
                                            <option value="Nam A Bank" {{ old('bank_name') == 'Nam A Bank' ? 'selected' : '' }}>Nam A Bank</option>
                                            <option value="Bac A Bank" {{ old('bank_name') == 'Bac A Bank' ? 'selected' : '' }}>Bac A Bank</option>
                                            <option value="VietCapital Bank" {{ old('bank_name') == 'VietCapital Bank' ? 'selected' : '' }}>Bản Việt (BVBank)</option>
                                            <option value="Eximbank" {{ old('bank_name') == 'Eximbank' ? 'selected' : '' }}>Eximbank</option>
                                            <option value="LienVietPostBank" {{ old('bank_name') == 'LienVietPostBank' ? 'selected' : '' }}>LPBank</option>
                                            <option value="Khác" {{ old('bank_name') == 'Khác' ? 'selected' : '' }}>Ngân hàng khác...</option>
                                        </optgroup>
                                    </select>
                                    @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted small">Mã QR (Không bắt buộc)</label>
                                <div class="p-3 bg-light rounded-3 border">
                                    <input type="file" name="qr_image" class="form-control @error('qr_image') is-invalid @enderror" id="qr_image">
                                    <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Tải lên mã QR chuyển khoản sẽ giúp Admin xử lý nhanh hơn. Chấp nhận: jpg, png, max 2MB.</div>
                                    @error('qr_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold text-muted small">Ghi chú thêm</label>
                                <textarea name="note" class="form-control @error('note') is-invalid @enderror" rows="3" placeholder="Lý do hủy hoặc lưu ý thêm...">{{ old('note') }}</textarea>
                                @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-top">
                            <div class="d-flex flex-column flex-md-row gap-3">
                                <button type="submit" class="btn btn-primary px-5 py-2 rounded-3 flex-fill">
                                    <i class="bi bi-check-circle me-2"></i>Gửi yêu cầu hoàn tiền
                                </button>
                                <a href="{{ route('account.bookings.show', $booking) }}" class="btn btn-light px-4 py-2 rounded-3 border">
                                    Hủy bỏ
                                </a>
                            </div>
                            <p class="text-center text-muted small mt-3 mb-0">
                                <i class="bi bi-shield-check me-1"></i>Dữ liệu của bạn được bảo mật tuyệt đối.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
