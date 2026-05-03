@extends('layouts.admin')

@section('title', 'Chi tiết đánh giá #' . $review->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Đánh giá #{{ $review->id }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary btn-admin-icon" title="Quay lại"><i class="bi bi-arrow-left"></i></a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Nội dung đánh giá</h5>
                </div>
                <div class="card-body">
                    @if($review->title)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tiêu đề</label>
                        <p class="form-control-plaintext">{{ $review->title }}</p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold">Xếp hạng</label>
                        <p class="form-control-plaintext">
                            <div class="text-warning">
                                @for($i = 0; $i < $review->rating; $i++)
                                    <i class="bi bi-star-fill"></i>
                                @endfor
                                <span class="text-dark ms-1">({{ $review->rating }}/5)</span>
                            </div>
                        </p>
                    </div>

                    @if($review->comment)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nhận xét</label>
                        <div class="form-control-plaintext border rounded p-3 bg-light small" style="white-space: pre-wrap;">{{ $review->comment }}</div>
                    </div>
                    @endif

                    @if($review->reply)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phản hồi từ khách sạn</label>
                        <div class="form-control-plaintext border rounded p-3 bg-light small" style="white-space: pre-wrap;">{{ $review->reply }}</div>
                        @if($review->replied_at)
                            <small class="text-muted">
                                Đã phản hồi: {{ is_string($review->replied_at) ? \Carbon\Carbon::parse($review->replied_at)->format('d/m/Y H:i:s') : $review->replied_at->format('d/m/Y H:i:s') }}
                            </small>
                        @endif
                    </div>
                    @endif

                    <form action="{{ route('admin.reviews.reply', $review) }}" method="POST" class="mt-3">
                        @csrf
                        <div class="mb-3">
                            <label for="reply" class="form-label fw-bold">Cập nhật phản hồi</label>
                            <textarea
                                class="form-control @error('reply') is-invalid @enderror"
                                id="reply"
                                name="reply"
                                rows="3"
                                placeholder="Nhập phản hồi của khách sạn...">{{ old('reply', $review->reply) }}</textarea>
                            @error('reply')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-2">
                                Nếu để trống và lưu, phản hồi sẽ bị xóa.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm" title="Lưu phản hồi"><i class="bi bi-check2-lg"></i> Lưu phản hồi</button>
                    </form>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ngày tạo</label>
                            <p class="form-control-plaintext">{{ $review->created_at ? (is_string($review->created_at) ? \Carbon\Carbon::parse($review->created_at)->format('d/m/Y H:i:s') : $review->created_at->format('d/m/Y H:i:s')) : '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Cập nhật lần cuối</label>
                            <p class="form-control-plaintext">{{ $review->updated_at ? (is_string($review->updated_at) ? \Carbon\Carbon::parse($review->updated_at)->format('d/m/Y H:i:s') : $review->updated_at->format('d/m/Y H:i:s')) : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin liên quan</h5>
                </div>
                <div class="card-body">
                    @if($review->user)
                    <p class="mb-3">
                        <strong>Người đánh giá:</strong><br>
                        {{ $review->user->full_name }}<br>
                        <small class="text-muted">{{ $review->user->email }}</small>
                    </p>
                    @endif

                    @if($review->booking)
                    <p class="mb-3">
                        <strong>Đơn đặt (xác minh lưu trú):</strong><br>
                        #{{ $review->booking->id }}
                        @if($review->booking->check_in && $review->booking->check_out)
                            <br><small class="text-muted">{{ $review->booking->check_in->format('d/m/Y') }} → {{ $review->booking->check_out->format('d/m/Y') }}</small>
                        @endif
                        <a href="{{ route('admin.bookings.show', $review->booking) }}" class="btn btn-outline-secondary btn-sm d-block mt-2">Mở đơn admin</a>
                    </p>
                    @elseif($review->booking_id)
                    <p class="mb-3 text-muted small">Đơn #{{ $review->booking_id }} (đã xóa hoặc không truy cập được).</p>
                    @else
                    <p class="mb-3 text-muted small">Đánh giá cũ — chưa gắn mã đơn.</p>
                    @endif

                    @if($review->room)
                    <p class="mb-0">
                        <strong>Phòng:</strong><br>
                        {{ $review->room->name }}
                        <a href="{{ route('rooms.show', $review->room) }}" class="btn btn-outline-primary d-block mt-2 text-nowrap" title="Xem phòng public"><i class="bi bi-box-arrow-up-right"></i> Xem phòng public</a>
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
