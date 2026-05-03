@php
    $roomEntity = $roomEntity ?? $room ?? null;
    $reviewableBookings = $reviewableBookings ?? collect();
    if (auth()->check() && $roomEntity) {
        $reviewableBookings = $reviewableBookings->isNotEmpty()
            ? $reviewableBookings
            : \App\Models\Booking::reviewableBookingsForRoom((int) auth()->id(), (int) $roomEntity->id);
    }
    $prefillBookingId = isset($prefillBookingId) ? (int) $prefillBookingId : (int) ($reviewableBookings->first()->id ?? 0);
@endphp

@guest
    <p class="text-muted mb-3">Để lại đánh giá sau khi đã lưu trú, vui lòng đăng nhập bằng tài khoản đã đặt phòng.</p>
    <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4 btn-sm">Đăng nhập</a>
@endguest

@auth
    @if($reviewableBookings->isEmpty())
        <p class="text-muted mb-0">
            @if(\App\Models\Booking::userHasCheckedOutRoom((int) auth()->id(), (int) $roomEntity->id))
                <i class="bi bi-check2-circle text-success me-1"></i>Bạn đã gửi đánh giá cho <strong>tất cả</strong> các lượt lưu trú hoàn tất tại phòng này.
            @else
                Chỉ khách đã có đơn cho phòng này, <strong>đã thanh toán</strong> và <strong>đã check-out</strong> mới có thể viết đánh giá.
            @endif
        </p>
    @else
        <form method="POST" action="{{ route('reviews.store', ['room' => $roomEntity->id]) }}" class="text-start review-write-form-inner">
            @csrf
            @if(!empty($reviewReturnUrl))
                <input type="hidden" name="_return" value="{{ $reviewReturnUrl }}">
            @endif

            @if($reviewableBookings->count() > 1)
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Đánh giá theo lượt lưu trú <span class="text-danger">*</span></label>
                    <select name="booking_id" class="form-select form-select-sm" required>
                        @foreach($reviewableBookings as $b)
                            <option value="{{ $b->id }}" @selected((int) $prefillBookingId === (int) $b->id)>
                                Đơn #{{ $b->id }} —
                                {{ $b->check_in?->format('d/m/Y') }} → {{ $b->check_out?->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Chọn đúng đơn tương ứng với lần bạn ở phòng này.</div>
                </div>
            @else
                <input type="hidden" name="booking_id" value="{{ $reviewableBookings->first()->id }}">
            @endif

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Số sao <span class="text-danger">*</span></label>
                    <select name="rating" class="form-select form-select-sm" required>
                        <option value="">Chọn</option>
                        <option value="5">5 — Rất tốt</option>
                        <option value="4">4 — Tốt</option>
                        <option value="3">3 — Ổn</option>
                        <option value="2">2 — Chưa tốt</option>
                        <option value="1">1 — Tệ</option>
                    </select>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label small text-muted mb-1">Tiêu đề (tuỳ chọn)</label>
                    <input type="text" name="title" class="form-control form-control-sm" maxlength="255" placeholder="Ví dụ: Phòng sạch, yên tĩnh">
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted mb-1">Nhận xét <span class="text-danger">*</span></label>
                    <textarea name="comment" class="form-control form-control-sm" rows="4" maxlength="2000" required placeholder="Chia sẻ trải nghiệm của bạn (tối đa 2000 ký tự)."></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm">Gửi đánh giá</button>
                </div>
            </div>
        </form>
    @endif
@endauth
