@extends('layouts.admin')

@section('title', 'Sửa đơn đặt phòng (Compact)')

@section('content')
<div class="container-fluid admin-page px-2 px-lg-3">
    <!-- Header -->
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-secondary rounded-2">
                <span class="me-1">←</span> Quay lại
            </a>
            <h1 class="h4 fw-bold mb-0">✏️ Sửa đơn #{{ $booking->id }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <h6 class="alert-heading fw-bold mb-2">❌ Có lỗi xảy ra!</h6>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <!-- Thông tin khách hàng & phòng - Nằm ngang -->
        <div class="card card-admin mb-3">
            <div class="card-header-admin">
                <h5 class="mb-0">Khách hàng &amp; phòng</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-sm-3">
                        <label class="form-label small fw-bold text-muted mb-1">Khách hàng</label>
                           <input type="text" class="form-control form-control-sm rounded-2" disabled
                               value="{{ $booking->user?->full_name ?? '' }}" />
                           <small class="text-muted">{{ $booking->user?->email ?? '' }}</small>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Phòng</label>
                        <input type="text" class="form-control form-control-sm rounded-2" disabled
                               value="{{ $booking->roomNamesLabel() }}" />
                    </div>
                    <div class="col-sm-1">
                        <label for="guests" class="form-label small fw-bold text-muted mb-1">Khách</label>
                        <input type="number" class="form-control form-control-sm rounded-2 @error('guests') is-invalid @enderror"
                               id="guests" name="guests" min="1" value="{{ old('guests', $booking->resolvedGuestCount()) }}" required />
                        @error('guests')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="check_in" class="form-label small fw-bold text-muted mb-1">Check-in</label>
                        <input type="date" class="form-control form-control-sm rounded-2 @error('check_in') is-invalid @enderror"
                               id="check_in" name="check_in" value="{{ old('check_in', $booking->check_in?->format('Y-m-d')) }}" required />
                        @error('check_in')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label for="check_out" class="form-label small fw-bold text-muted mb-1">Check-out</label>
                        <input type="date" class="form-control form-control-sm rounded-2 @error('check_out') is-invalid @enderror"
                               id="check_out" name="check_out" value="{{ old('check_out', $booking->check_out?->format('Y-m-d')) }}" required />
                        @error('check_out')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Phòng type</label>
                        <input type="text" class="form-control form-control-sm rounded-2" disabled
                               value="{{ $booking->room?->type }}" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Giá & Trạng thái - Nằm ngang -->
        <div class="card card-admin mb-3">
            <div class="card-header-admin">
                <h5 class="mb-0">Giá &amp; trạng thái</h5>
            </div>
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3">
                        <label for="total_price" class="form-label small fw-bold text-muted mb-1">Tổng tiền (VNĐ)</label>
                        <input type="number" class="form-control form-control-sm rounded-2 @error('total_price') is-invalid @enderror"
                               id="total_price" name="total_price" min="0" step="1000"
                               value="{{ old('total_price', $booking->total_price) }}" required />
                        @error('total_price')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-3">
                        <label for="status" class="form-label small fw-bold text-muted mb-1">Trạng thái</label>
                        <select class="form-select form-select-sm rounded-2 @error('status') is-invalid @enderror"
                                id="status" name="status" required
                                aria-describedby="statusHelp">
                            <option value="pending" {{ old('status', $booking->status)=='pending'?'selected':'' }}>🕐 Chờ xác nhận</option>
                            <option value="confirmed" {{ old('status', $booking->status)=='confirmed'?'selected':'' }}>✅ Đã xác nhận</option>
                            <option value="cancellation_pending" {{ old('status', $booking->status)=='cancellation_pending'?'selected':'' }}>📩 Chờ xử lý hủy</option>
                            <option value="cancelled" {{ old('status', $booking->status)=='cancelled'?'selected':'' }}>🛑 Đã hủy</option>
                            <option value="refunded" {{ old('status', $booking->status)=='refunded'?'selected':'' }}>💳 Đã hoàn tiền</option>
                            <option value="completed" {{ old('status', $booking->status)=='completed'?'selected':'' }}>🏁 Hoàn thành</option>
                        </select>
                        <small id="statusHelp" class="text-muted">Mỗi giá trị tương ứng một dòng trong bảng &quot;Ý nghĩa trạng thái&quot; bên dưới.</small>
                        @error('status')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">ID Đơn</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text rounded-2">#{{ $booking->id }}</span>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Tạo lúc</label>
                        <small class="d-block text-muted">{{ $booking->created_at?->format('d/m H:i') ?? '—' }}</small>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-bold text-muted mb-1">Cập nhật</label>
                        <small class="d-block text-muted">{{ $booking->updated_at?->format('d/m H:i') ?? '—' }}</small>
                    </div>
                </div>

                @if($booking->actual_check_in || $booking->actual_check_out)
                <div class="mt-2 pt-2 border-top">
                    <div class="row g-2">
                        @if($booking->actual_check_in)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">✓ Check-in thực tế</small>
                            <code class="text-dark">{{ $booking->actual_check_in->format('d/m/Y H:i') }}</code>
                        </div>
                        @endif
                        @if($booking->actual_check_out)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">✓ Check-out thực tế</small>
                            <code class="text-dark">{{ $booking->actual_check_out->format('d/m/Y H:i') }}</code>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div class="card card-admin mb-3">
            <div class="card-header-admin">
                <h5 class="mb-0">Hướng dẫn sửa đơn</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-danger mb-2"><i class="bi bi-lock-fill me-1"></i> Không đổi trên form này</h6>
                        <ul class="list-unstyled small mb-0 text-muted">
                            <li class="mb-1">Khách hàng, danh sách phòng (đổi phòng → xóa đơn &amp; tạo đơn mới hoặc xử lý ngoài hệ thống).</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-success mb-2"><i class="bi bi-pencil-square me-1"></i> Được sửa ở trên</h6>
                        <ul class="list-unstyled small mb-0 text-muted">
                            <li class="mb-1">Ngày nhận / trả, số khách, tổng tiền, <strong>trạng thái</strong> (chọn đúng theo bảng dưới).</li>
                        </ul>
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-list-columns-reverse me-1"></i> Ý nghĩa từng trạng thái (trùng với ô chọn)</h6>
                <p class="small text-muted mb-2">Icon trong dropdown và trong bảng dưới là một — tránh nhầm <strong>Chờ xác nhận</strong> (đơn mới) với <strong>Chờ xử lý hủy</strong> (khách đã gửi yêu cầu hủy).</p>
                <div class="admin-table-wrap">
                    <table class="table table-sm table-bordered align-middle mb-0 booking-status-legend">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap" style="min-width: 12rem;">Trạng thái</th>
                                <th>Khi nào dùng / Việc tiếp theo</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td class="text-nowrap fw-semibold">🕐 Chờ xác nhận</td>
                                <td>Đơn vừa tạo: chưa duyệt hoặc chưa thanh toán xong. Admin có thể chuyển sang <strong>Đã xác nhận</strong> sau khi duyệt / nhận tiền.</td>
                            </tr>
                            <tr>
                                <td class="text-nowrap fw-semibold">✅ Đã xác nhận</td>
                                <td>Đơn hiệu lực: đã duyệt (và thường đã thanh toán). Chờ đến ngày nhận phòng hoặc thao tác check-in.</td>
                            </tr>
                            <tr class="table-warning bg-opacity-25">
                                <td class="text-nowrap fw-semibold">📩 Chờ xử lý hủy</td>
                                <td>Khách đã bấm hủy và hệ thống yêu cầu duyệt (có phí hủy). <strong>Chưa giải phóng phòng</strong> cho đến khi xử lý trên trang <a href="{{ route('admin.bookings.show', $booking) }}">chi tiết đơn</a> (Chấp nhận / Từ chối). Không chỉnh tay lung tung nếu không hiểu luồng.</td>
                            </tr>
                            <tr>
                                <td class="text-nowrap fw-semibold">🛑 Đã hủy</td>
                                <td>Đơn không còn hiệu lực; lịch phòng đã mở. Nếu đã thanh toán: bước hoàn tiền thủ công (khách gửi TK → admin upload chứng từ).</td>
                            </tr>
                            <tr>
                                <td class="text-nowrap fw-semibold">💳 Đã hoàn tiền</td>
                                <td>Đơn đã hủy và kế toán đã hoàn tiền xong (có chứng từ trên hệ thống). Trạng thái cuối của luồng hoàn.</td>
                            </tr>
                            <tr>
                                <td class="text-nowrap fw-semibold">🏁 Hoàn thành</td>
                                <td>Khách đã ở và check-out xong — kỳ lưu trú kết thúc bình thường (không liên quan hủy / hoàn tiền).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Lưu thay đổi
            </button>
            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary px-4">
                Quay lại chi tiết
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Bootstrap validation
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
@endpush
@endsection
