@extends('layouts.admin')

@section('title', 'Chi tiết đánh giá #' . $review->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Đánh giá #{{ $review->id }}</h1>
        <div class="d-flex gap-2">
<<<<<<< HEAD
            <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-primary">Chỉnh sửa</a>
=======
>>>>>>> vinam
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary">Quay lại</a>
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
                        <p class="form-control-plaintext">{{ $review->comment }}</p>
                    </div>
                    @endif

<<<<<<< HEAD
=======
                    @if($review->reply)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phản hồi từ khách sạn</label>
                        <p class="form-control-plaintext">{{ $review->reply }}</p>
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

                        <button type="submit" class="btn btn-primary btn-sm">Lưu phản hồi</button>
                    </form>

>>>>>>> vinam
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

                    @if($review->room)
                    <p class="mb-0">
                        <strong>Phòng:</strong><br>
                        {{ $review->room->name }}
<<<<<<< HEAD
=======
                        <a href="{{ route('rooms.show', $review->room) }}" class="btn btn-outline-primary btn-sm d-block mt-2">
                            Xem chi tiết phòng
                        </a>
>>>>>>> vinam
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
