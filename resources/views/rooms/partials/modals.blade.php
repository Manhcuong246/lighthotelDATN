{{-- Combined Amenity & Policy Modal --}}
<div class="modal fade" id="policyModal{{ $type->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">{{ $type->name }} - Dịch vụ kèm &amp; chính sách</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                {{-- Dịch vụ đi kèm theo loại phòng (danh mục dịch vụ) --}}
                <div class="mb-5">
                    <h5 class="fw-bold mb-4 text-dark border-start border-4 border-primary ps-3">Dịch vụ đi kèm: {{ $type->name }}</h5>
                    @if($type->services->isNotEmpty())
                        <div class="row g-4 mb-4">
                            @foreach($type->services as $svc)
                                <div class="col-6 col-md-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-bag-check-fill text-primary fs-5"></i>
                                        <span class="text-secondary">{{ $svc->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Loại phòng này chưa gắn dịch vụ đi kèm trong quản trị.</p>
                    @endif
                </div>

                {{-- Policy Section --}}
                <div>
                    <h5 class="fw-bold mb-4 text-dark border-start border-4 border-primary ps-3">{{ $type->name }} - Chính sách & Thông tin</h5>

                    <div class="policy-section mb-4">
                        <div class="policy-title fs-6 fw-bold text-dark mb-2">Chính sách hoàn hủy</div>
                        <p class="text-muted mb-0">Nếu hủy, thay đổi hoặc không đến, khách sẽ trả toàn bộ giá trị tiền đặt phòng.</p>
                    </div>

                    <div class="policy-section mb-4">
                        <div class="policy-title fs-6 fw-bold text-dark mb-2">Thanh toán</div>
                        <p class="text-muted mb-0">Thanh toán toàn bộ giá trị tiền đặt phòng ngay sau khi xác nhận đơn.</p>
                    </div>

                    <div class="policy-section mb-4">
                        <div class="policy-title fs-6 fw-bold text-dark mb-2">Thời gian nhận/trả phòng</div>
                        <ul class="list-unstyled text-muted mb-0">
                            <li>Nhận phòng: <strong class="text-dark">14:00</strong></li>
                            <li>Trả phòng: <strong class="text-dark">12:00</strong></li>
                        </ul>
                    </div>

                    <div class="policy-section mb-4">
                        <div class="policy-title fs-6 fw-bold text-primary mb-2">Phụ thu</div>
                        <ul class="list-unstyled text-muted mb-0">
                            <li>Phụ thu người lớn: <strong>Liên hệ khi check-in</strong></li>
                            <li>Phụ thu trẻ em: <strong>Liên hệ khi check-in</strong></li>
                        </ul>
                    </div>

                    <div class="policy-section">
                        <div class="policy-title fs-6 fw-bold text-dark mb-2">Chính sách khác</div>
                        <p class="text-muted small mb-0">
                             [The booking confirmation will be sent to the email address provided by the Customer. The hotel shall bear no responsibility in case the booking confirmation email is not delivered successfully due to the email address being entered incorrectly, the email being marked as spam, or full recipient mailbox... Any booking cancellation or adjustment request must be communicated via email to the Hotel.]
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Room Detail Modal --}}
@php $firstRoom = $type->available_rooms ? $type->available_rooms->first() : null; @endphp
@if($firstRoom)
<div class="modal fade room-detail-modal" id="roomDetailModal{{ $firstRoom->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">{{ $type->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="row g-4">
                    {{-- Left Side: Carousel --}}
                    <div class="col-lg-7">
                        <div id="carouselRoom{{ $firstRoom->id }}" class="carousel slide room-detail-carousel mb-4" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @php $images = $firstRoom->getDisplayImageUrls(); @endphp
                                @php
                                    $modalPlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='400'%3E%3Crect fill='%23e2e8f0' width='100%25' height='100%25'/%3E%3Ctext fill='%23475569' font-family='system-ui,sans-serif' font-size='18' x='50%25' y='50%25' text-anchor='middle' dominant-baseline='middle'%3ELight Hotel%3C/text%3E%3C/svg%3E";
                                @endphp
                                @forelse($images as $index => $url)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <img src="{{ $url }}" class="d-block w-100" alt="Room Image"
                                             onerror="this.onerror=null;this.src='{{ $modalPlaceholder }}'">
                                    </div>
                                @empty
                                    <div class="carousel-item active">
                                        <img src="{{ $modalPlaceholder }}" class="d-block w-100" alt="Placeholder">
                                    </div>
                                @endforelse
                            </div>
                            @if(count($images) > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselRoom{{ $firstRoom->id }}" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carouselRoom{{ $firstRoom->id }}" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Right Side: Info --}}
                    <div class="col-lg-5">
                        <div class="room-detail-header-info">
                            <h2 class="fw-bold mb-2">{{ $firstRoom->name }}</h2>
                            <div class="d-flex align-items-center mb-3">
                                <span class="room-price-big">{{ number_format($firstRoom->catalogueBasePrice(), 0, ',', '.') }} VNĐ</span>
                                <span class="text-muted ms-2">/ đêm</span>
                                <div class="room-rating-stars">
                                    @php
                                        $roomIdsForTypeModal = $type->rooms->pluck('id')->filter()->values();
                                        $avgRating = $roomIdsForTypeModal->isNotEmpty()
                                            ? (\App\Models\Review::whereIn('room_id', $roomIdsForTypeModal)->avg('rating') ?: 5.0)
                                            : ($firstRoom->reviews->avg('rating') ?: 5.0);
                                        $fullStars = floor($avgRating);
                                        $hasHalf = ($avgRating - $fullStars) >= 0.5;
                                    @endphp
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $fullStars)
                                            <i class="bi bi-star-fill"></i>
                                        @elseif($i == $fullStars + 1 && $hasHalf)
                                            <i class="bi bi-star-half"></i>
                                        @else
                                            <i class="bi bi-star"></i>
                                        @endif
                                    @endfor
                                    <span class="text-muted small ms-1">({{ number_format($avgRating, 1) }})</span>
                                </div>
                            </div>

                            <div class="mb-4 d-flex flex-wrap gap-3">
                                <div class="room-spec-item"><i class="bi bi-house"></i> {{ $firstRoom->beds }} giường</div>
                                <div class="room-spec-item"><i class="bi bi-droplet"></i> {{ $firstRoom->baths ?? 1 }} phòng tắm</div>
                                <div class="room-spec-item"><i class="bi bi-people"></i> Tối đa {{ $firstRoom->catalogueMaxGuests() }} khách</div>
                                <div class="room-spec-item"><i class="bi bi-aspect-ratio"></i> {{ $firstRoom->area }} m²</div>
                            </div>

                            <div class="detail-card">
                                <div class="detail-section-title">Mô tả</div>
                                <p class="text-muted small mb-0">{{ $firstRoom->description ?? 'Phòng nghỉ sang trọng với không gian yên tĩnh và tầm nhìn tuyệt đẹp.' }}</p>
                            </div>

                            <div class="detail-card">
                                <div class="detail-section-title">Dịch vụ đi kèm (theo loại phòng)</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @forelse($type->services as $svc)
                                        <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">{{ $svc->name }}</span>
                                    @empty
                                        <span class="text-muted small">Chưa cấu hình dịch vụ kèm cho loại phòng.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reviews Section (gom theo cùng loại phòng — các đánh giá lưu theo room_id thuộc loại) --}}
                @php
                    $roomIdsForTypeReviews = $type->rooms->pluck('id')->filter()->values();
                    $modalTypeReviews = $roomIdsForTypeReviews->isNotEmpty()
                        ? \App\Models\Review::with(['user', 'room'])->whereIn('room_id', $roomIdsForTypeReviews)->latest()->limit(20)->get()
                        : collect();
                @endphp
                <div class="mt-4">
                    <div class="detail-section-title">Đánh giá theo loại phòng</div>
                    <p class="small text-muted mb-3">Hiển thị đánh giá từ mọi phòng vật lý thuộc <strong>{{ $type->name }}</strong> (kể cả số phòng khác nhau).</p>
                    <div class="row g-3">
                        @forelse($modalTypeReviews as $review)
                            <div class="col-12">
                                <div class="review-card">
                                    <div class="d-flex gap-3">
                                        <div class="review-user-avatar">
                                            {{ strtoupper(substr($review->user->full_name ?? $review->user->name ?? 'G', 0, 1)) }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="review-user-name">{{ $review->user->full_name ?? $review->user->name ?? 'Guest' }}</div>
                                            @if($review->room)
                                                <div class="text-muted small mb-1">{{ $review->room->name }}</div>
                                            @endif
                                            <div class="text-warning small mb-2">
                                                @for($i = 0; $i < 5; $i++)
                                                    <i class="bi bi-star{{ $i < $review->rating ? '-fill' : '' }}"></i>
                                                @endfor
                                            </div>
                                            <div class="fw-bold small mb-1">{{ $review->title ?? 'Tuyệt vời' }}</div>
                                            <p class="review-comment mb-0">{{ $review->comment }}</p>

                                            @if($review->reply)
                                                <div class="review-reply">
                                                    <div class="review-reply-label">Phản hồi từ khách sạn:</div>
                                                    <p class="mb-0">{{ $review->reply }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="review-date">{{ $review->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted text-center py-4 bg-white rounded-4 border">Chưa có đánh giá nào cho loại phòng này.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 p-4 bg-white rounded-4 border text-center">
                        @include('rooms.partials.review-write-form', ['room' => $firstRoom])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
