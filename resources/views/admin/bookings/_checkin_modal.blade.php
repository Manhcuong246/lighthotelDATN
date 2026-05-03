{{-- Modal Check-in khách hàng - Đơn #{{ $booking->id }} --}}
<div class="modal fade" id="checkinModal{{ $booking->id }}" tabindex="-1"
     aria-labelledby="checkinModalLabel{{ $booking->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="checkinModalLabel{{ $booking->id }}">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Check-in — Đơn #{{ $booking->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ action([App\Http\Controllers\Admin\BookingAdminController::class, 'checkInWithAssignment'], $booking) }}"
                  method="POST" id="checkinForm{{ $booking->id }}"
                  data-admin-checkin-form data-booking-id="{{ $booking->id }}">
                @csrf

                <div class="modal-body">

                    {{-- Thông tin đơn (cập nhật từ API) --}}
                    <div class="alert alert-info mb-3" data-checkin-summary>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Người đại diện:</strong>
                                <span data-summary-name>{{ $booking->user?->full_name ?? '—' }}</span><br>
                                <strong>CCCD:</strong> <span data-summary-cccd>—</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Nhận phòng:</strong>
                                <span data-summary-checkin>{{ $booking->check_in?->format('d/m/Y') ?? '—' }}</span><br>
                                <strong>Trả phòng:</strong>
                                <span data-summary-checkout>{{ $booking->check_out?->format('d/m/Y') ?? '—' }}</span>
                            </div>
                        </div>
                        <p class="small text-muted mb-0 mt-2">Mỗi đơn chỉ có <strong>một</strong> người đại diện (theo CCCD trên đơn hoặc tài khoản đặt). <strong>Mỗi phòng khi check-in:</strong> ít nhất 1 người lớn <strong>khai báo CCCD đủ 12 số</strong> (người đại diện lưu trú tại phòng đó). Có thể chỉ cần <strong>một</strong> người lớn nếu người đó có CCCD. Nếu số người thực tế cao hơn lúc đặt và vẫn trong sức chứa phòng, hệ thống tự ghi nhận <strong>phụ thu chênh lệch người ở</strong>; nếu thấp hơn, hệ thống chỉ ghi nhận chênh lệch (không tự động hoàn/giảm tiền).</p>
                    </div>

                    {{-- Thông báo lỗi validate --}}
                    <div id="validationErrors{{ $booking->id }}" class="alert alert-danger d-none">
                        <ul class="mb-0" id="errorList{{ $booking->id }}"></ul>
                    </div>

                    {{-- Container các slot phòng — được render bởi JS --}}
                    <div id="roomSlotsContainer{{ $booking->id }}">
                        <div class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm me-2"></div>Đang tải...
                        </div>
                    </div>

                    {{-- Template hàng khách (ẩn, dùng bởi JS) --}}
                    <template id="guestRowTemplate{{ $booking->id }}">
                        <tr class="guest-row" data-guest-id="" data-is-new="true">
                            <td class="guest-stt text-center fw-semibold"></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" class="form-control form-control-sm guest-name-input"
                                           placeholder="Họ và tên" required>
                                    <input type="hidden" class="guest-id-input" value="">
                                    <input type="hidden" class="guest-booking-room-id-input" value="">
                                </div>
                            </td>
                            <td>
                                <input type="tel" class="form-control form-control-sm guest-cccd-input"
                                       placeholder="Ít nhất 1 người lớn/phòng: CCCD 12 số" maxlength="12" autocomplete="off">
                            </td>
                            <td>
                                <select class="form-select form-select-sm guest-type-input" title="Độ tuổi để tính phí theo đơn">
                                    <option value="adult">Người lớn (từ 12 tuổi)</option>
                                    <option value="child_0_5">Trẻ em 0–5 tuổi</option>
                                    <option value="child_6_11">Trẻ em 6–11 tuổi</option>
                                </select>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-remove-guest">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Đóng
                    </button>
                    <button type="submit" class="btn btn-info" id="checkinSubmitBtn{{ $booking->id }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Xác nhận Check-in
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
