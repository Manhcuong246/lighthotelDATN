{{-- Đánh giá gắn với phòng vật lý đã ở; loại phòng hiển thị qua quan hệ room->roomType (đánh giá cùng loại được xem trên trang chủ/modal gom theo phòng mẫu). --}}
@php
    $roomEntity = $room;
@endphp
@guest
    <p class="text-muted mb-3">Bạn muốn để lại đánh giá? Vui lòng đăng nhập.</p>
    <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4 btn-sm">Đăng nhập để viết đánh giá</a>
@endguest

@auth
    @php
        $uid = (int) auth()->id();
        $rid = (int) $roomEntity->id;
        $alreadyReviewed = \App\Models\Review::userHasReviewedRoom($uid, $rid);
        $canReviewRoom = \App\Models\Booking::userCanSubmitRoomReview($uid, $rid);
    @endphp

    @if($alreadyReviewed)
        <p class="text-muted mb-0"><i class="bi bi-check2-circle text-success me-1"></i>Bạn đã gửi đánh giá cho phòng này. Mỗi tài khoản chỉ được đánh giá một lần trên mỗi phòng.</p>
    @elseif($canReviewRoom)
        <form method="POST" action="{{ route('reviews.store', ['room' => $roomEntity->id]) }}" class="text-start mx-auto review-write-form-inner" style="max-width: 720px;">
            @csrf

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Số sao</label>
                    <select name="rating" class="form-select form-select-sm" required>
                        <option value="">Chọn</option>
                        <option value="5">5 - Rất tốt</option>
                        <option value="4">4 - Tốt</option>
                        <option value="3">3 - Ổn</option>
                        <option value="2">2 - Chưa tốt</option>
                        <option value="1">1 - Tệ</option>
                    </select>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label small text-muted mb-1">Tiêu đề (tuỳ chọn)</label>
                    <input type="text" name="title" class="form-control form-control-sm" maxlength="255" placeholder="Ví dụ: Phòng sạch sẽ, nhân viên thân thiện">
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted mb-1">Nhận xét</label>
                    <textarea name="comment" class="form-control form-control-sm" rows="3" maxlength="2000" required placeholder="Chia sẻ trải nghiệm của bạn về phòng và loại phòng..."></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm">Gửi đánh giá</button>
                </div>
            </div>
        </form>
    @else
        <p class="text-muted mb-0">Chỉ khách đã có đơn cho phòng này, đã thanh toán và đã check-out mới có thể viết đánh giá.</p>
    @endif
@endauth
