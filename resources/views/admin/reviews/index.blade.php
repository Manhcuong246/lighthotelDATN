@extends('layouts.admin')

@section('title', 'Quản lý đánh giá')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Quản lý đánh giá</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3">
            <h5 class="mb-0">Danh sách đánh giá</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Người dùng</th>
                            <th>Phòng</th>
                            <th>Xếp hạng</th>
                            <th>Tiêu đề</th>
                            <th>Ngày đánh giá</th>
                            <th width="180">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                            <tr>
                                <td>{{ $review->id }}</td>
                                <td>
                                    @if($review->user)
                                        {{ $review->user->full_name }}<br>
                                        <small class="text-muted">{{ $review->user->email }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $review->room ? $review->room->name : '—' }}</td>
                                <td>
                                    <div class="text-warning">
                                        @for($i = 0; $i < $review->rating; $i++)
                                            <i class="bi bi-star-fill"></i>
                                        @endfor
                                        <span class="text-dark ms-1">({{ $review->rating }}/5)</span>
                                    </div>
                                </td>
                                <td>{{ $review->title ?? '—' }}</td>
                                <td>{{ $review->created_at ? (is_string($review->created_at) ? \Carbon\Carbon::parse($review->created_at)->format('d/m/Y') : $review->created_at->format('d/m/Y')) : '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                    <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-sm btn-outline-secondary">Sửa</a>
                                    @if(auth()->user()->isAdmin())
                                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Chưa có đánh giá nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reviews->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $reviews->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
