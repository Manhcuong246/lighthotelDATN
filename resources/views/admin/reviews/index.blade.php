@extends('layouts.admin')

@section('title', 'Quản lý đánh giá')

@section('content')
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Quản lý đánh giá</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách đánh giá</h5>

            <form action="{{ route('admin.reviews.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="q" value="{{ request('q') }}" 
                       class="form-control form-control-sm" 
                       placeholder="Tìm khách, phòng, nội dung..." 
                       style="width: 240px;">

                <button type="submit" class="btn btn-primary btn-sm btn-admin-icon" title="Tìm"><i class="bi bi-search"></i></button>

                @if(request('q'))
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary btn-sm btn-admin-icon" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
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

                                {{-- Người dùng --}}
                                <td>
                                    @if($review->user)
                                        <strong>{{ $review->user->full_name }}</strong><br>
                                        <small class="text-muted">{{ $review->user->email }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Phòng --}}
                                <td>
                                    {{ optional($review->room)->name ?? '—' }}
                                </td>

                                {{-- Rating --}}
                                <td>
                                    <div class="text-warning">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                        <span class="text-dark ms-1">
                                            ({{ $review->rating }}/5)
                                        </span>
                                    </div>
                                </td>

                                {{-- Tiêu đề --}}
                                <td>{{ $review->title ?? '—' }}</td>

                                {{-- Ngày --}}
                                <td>
                                    {{ optional($review->created_at)->format('d/m/Y') ?? '—' }}
                                </td>

                                {{-- Action --}}
                                <td>
                                    <div class="admin-action-row">
                                        <a href="{{ route('admin.reviews.show', $review) }}"
                                           class="btn btn-sm btn-outline-primary btn-admin-icon"
                                           title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                        @if(auth()->user()->isAdmin())
                                            <form action="{{ route('admin.reviews.destroy', $review) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-admin-icon" title="Xóa"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Chưa có đánh giá nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($reviews->hasPages())
            <div class="card-footer bg-white border-0 py-2">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</div>
@endsection