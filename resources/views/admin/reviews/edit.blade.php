@extends('layouts.admin')

@section('title', 'Chỉnh sửa đánh giá #' . $review->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Chỉnh sửa đánh giá</h1>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin đánh giá</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Tiêu đề</label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $review->title) }}"
                                   placeholder="Tiêu đề đánh giá">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="rating" class="form-label fw-bold">Xếp hạng (1-5)</label>
                            <select class="form-select @error('rating') is-invalid @enderror" 
                                    id="rating" 
                                    name="rating">
                                <option value="">Chọn xếp hạng</option>
                                <option value="1" @selected(old('rating', $review->rating) == 1)>1 ⭐</option>
                                <option value="2" @selected(old('rating', $review->rating) == 2)>2 ⭐⭐</option>
                                <option value="3" @selected(old('rating', $review->rating) == 3)>3 ⭐⭐⭐</option>
                                <option value="4" @selected(old('rating', $review->rating) == 4)>4 ⭐⭐⭐⭐</option>
                                <option value="5" @selected(old('rating', $review->rating) == 5)>5 ⭐⭐⭐⭐⭐</option>
                            </select>
                            @error('rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label fw-bold">Nhận xét</label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" 
                                      id="comment" 
                                      name="comment" 
                                      rows="4"
                                      placeholder="Viết nhận xét...">{{ old('comment', $review->comment) }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                            <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-outline-secondary">Hủy</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
