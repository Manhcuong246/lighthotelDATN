@extends('layouts.admin')

@section('title', 'Chi tiết đánh giá #' . $review->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Đánh giá #{{ $review->id }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-primary">Chỉnh sửa</a>
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
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
