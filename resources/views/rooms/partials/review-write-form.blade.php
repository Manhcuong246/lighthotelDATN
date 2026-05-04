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
            @php $lhStarRatingUid = 'lh-sr-' . bin2hex(random_bytes(4)); @endphp
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
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small text-muted mb-1" id="{{ $lhStarRatingUid }}-label">Số sao <span class="text-danger">*</span></label>
                    <div class="lh-star-rating-input">
                        <input type="hidden" name="rating" value="{{ old('rating', '') }}" required class="lh-star-rating-hidden" autocomplete="off">
                        @php
                            $lhReviewStarAria = [1 => '1 sao — Tệ', 2 => '2 sao — Chưa tốt', 3 => '3 sao — Ổn', 4 => '4 sao — Tốt', 5 => '5 sao — Rất tốt'];
                        @endphp
                        <div class="lh-star-rating-stars" role="radiogroup" aria-labelledby="{{ $lhStarRatingUid }}-label">
                            @for($s = 1; $s <= 5; $s++)
                                <button type="button" class="lh-star-btn" data-value="{{ $s }}" aria-label="{{ $lhReviewStarAria[$s] }}" aria-pressed="false">
                                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                                </button>
                            @endfor
                        </div>
                        <div class="lh-star-rating-caption small text-muted mt-1"></div>
                        @error('rating')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-8 col-lg-9">
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

@once
    @push('styles')
    <style>
        .lh-star-rating-input .lh-star-rating-stars {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.15rem;
        }
        .lh-star-rating-input .lh-star-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.15rem;
            margin: 0;
            border: none;
            background: transparent;
            color: #d1d5db;
            font-size: 1.85rem;
            line-height: 1;
            cursor: pointer;
            transition: color 0.15s ease, transform 0.12s ease;
        }
        .lh-star-rating-input .lh-star-btn.is-lit {
            color: #f59e0b;
        }
        .lh-star-rating-input.is-preview-mode .lh-star-btn.is-lit {
            color: #fbbf24;
        }
        .lh-star-rating-input .lh-star-btn:hover {
            transform: scale(1.1);
        }
        .lh-star-rating-input .lh-star-btn:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 3px;
            border-radius: 6px;
        }
        .lh-star-rating-input .lh-star-rating-caption {
            min-height: 1.35rem;
            font-weight: 500;
            color: #6b7280;
        }
    </style>
    @endpush
    @push('scripts')
    <script>
        (function () {
            var LABELS = ['', 'Tệ', 'Chưa tốt', 'Ổn', 'Tốt', 'Rất tốt'];

            function sync(wrap, previewVal) {
                var hidden = wrap.querySelector('.lh-star-rating-hidden');
                var caption = wrap.querySelector('.lh-star-rating-caption');
                var btns = wrap.querySelectorAll('.lh-star-btn');
                if (!hidden || !btns.length) return;

                var selected = parseInt(hidden.value, 10) || 0;
                var display = previewVal != null ? previewVal : selected;

                wrap.classList.toggle('is-preview-mode', previewVal != null);

                btns.forEach(function (btn, idx) {
                    var n = idx + 1;
                    var lit = display > 0 && n <= display;
                    btn.classList.toggle('is-lit', lit);
                    btn.setAttribute('aria-pressed', selected === n ? 'true' : 'false');
                });

                if (caption) {
                    if (previewVal != null && previewVal > 0) {
                        caption.textContent = LABELS[previewVal];
                    } else if (selected > 0) {
                        caption.textContent = LABELS[selected];
                    } else {
                        caption.textContent = 'Chọn số sao (bắt buộc)';
                    }
                }
            }

            function bind(wrap) {
                var hidden = wrap.querySelector('.lh-star-rating-hidden');
                var stars = wrap.querySelector('.lh-star-rating-stars');
                var btns = wrap.querySelectorAll('.lh-star-btn');
                if (!hidden || !stars || !btns.length) return;

                btns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var v = parseInt(btn.getAttribute('data-value'), 10);
                        hidden.value = String(v);
                        sync(wrap, null);
                    });
                    btn.addEventListener('mouseenter', function () {
                        var v = parseInt(btn.getAttribute('data-value'), 10);
                        sync(wrap, v);
                    });
                });
                stars.addEventListener('mouseleave', function () {
                    sync(wrap, null);
                });

                sync(wrap, null);
            }

            function init() {
                document.querySelectorAll('.review-write-form-inner .lh-star-rating-input').forEach(bind);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
    @endpush
@endonce
