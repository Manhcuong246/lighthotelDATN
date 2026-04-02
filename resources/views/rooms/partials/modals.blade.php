{{-- Combined Amenity & Policy Modal --}}
<div class="modal fade" id="policyModal{{ $type->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header-custom">
                <h5 class="modal-title-custom">{{ $type->name }} - Tiện nghi & Chính sách</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                @php
                    $roomForInfo = (isset($type->available_rooms) && $type->available_rooms->isNotEmpty()) 
                                   ? $type->available_rooms->first() 
                                   : ($type->rooms->isNotEmpty() ? $type->rooms->first() : null);
                @endphp

                {{-- Amenities Section --}}
                @if($roomForInfo)
                <div class="mb-5">
                    <h5 class="fw-bold mb-4 text-dark border-start border-4 border-primary ps-3">Tiện nghi phòng: {{ $type->name }}</h5>
                    <div class="row g-4 mb-4">
                        @foreach($roomForInfo->amenities as $amenity)
                            <div class="col-6 col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-patch-check-fill text-primary fs-5"></i>
                                    <span class="text-secondary">{{ $amenity->name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

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
                        <div class="policy-title fs-6 fw-bold text-dark mb-2">Phụ thu</div>
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
@php 
    $firstRoom = (isset($type->available_rooms) && $type->available_rooms->isNotEmpty()) 
                   ? $type->available_rooms->first() 
                   : ($type->rooms->isNotEmpty() ? $type->rooms->first() : null); 
@endphp
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
                                @forelse($images as $index => $url)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <img src="{{ $url }}" class="d-block w-100" alt="Room Image">
                                    </div>
                                @empty
                                    <div class="carousel-item active">
                                        <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80" class="d-block w-100" alt="Placeholder">
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
                                <span class="room-price-big">{{ number_format($firstRoom->base_price, 0, ',', '.') }} VNĐ</span>
                                <span class="text-muted ms-2">/ đêm</span>
                                <div class="room-rating-stars">
                                    @php 
                                        $avgRating = $firstRoom->reviews->avg('rating') ?: 5.0;
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
                                <div class="room-spec-item"><i class="bi bi-people"></i> Tối đa {{ $firstRoom->max_guests }} khách</div>
                                <div class="room-spec-item"><i class="bi bi-aspect-ratio"></i> {{ $firstRoom->area }} m²</div>
                            </div>

                            <div class="detail-card">
                                <div class="detail-section-title">Mô tả</div>
                                <p class="text-muted small mb-0">{{ $firstRoom->description ?? 'Phòng nghỉ sang trọng với không gian yên tĩnh và tầm nhìn tuyệt đẹp.' }}</p>
                            </div>

                            <div class="detail-card">
                                <div class="detail-section-title">Tiện ích</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($firstRoom->amenities as $amenity)
                                        <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill small">{{ $amenity->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reviews Section --}}
                <div class="mt-4">
                    <div class="detail-section-title">Đánh giá</div>
                    <div class="row g-3">
                        @forelse($firstRoom->reviews as $review)
                            <div class="col-12">
                                <div class="review-card">
                                    <div class="d-flex gap-3">
                                        <div class="review-user-avatar">
                                            {{ strtoupper(substr($review->user->name ?? 'G', 0, 1)) }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="review-user-name">{{ $review->user->name ?? 'Guest' }}</div>
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
                                <p class="text-muted text-center py-4 bg-white rounded-4 border">Chưa có đánh giá nào cho phòng này.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4 p-4 bg-white rounded-4 border text-center">
                        <p class="text-muted mb-3">Bạn muốn để lại đánh giá? Vui lòng đăng nhập.</p>
                        <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4 btn-sm">Đăng nhập để viết đánh giá</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
