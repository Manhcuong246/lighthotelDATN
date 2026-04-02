@extends('layouts.admin')

@section('title', 'Quản lý đánh giá')

@section('content')
<div class="container-fluid admin-page px-0">
    <div class="page-header">
        <h1>Quản lý đánh giá</h1>
    </div>

    <div class="card card-admin shadow mb-4">
        <div class="card-header-admin py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">Danh sách đánh giá</h5>

            <form action="{{ route('admin.reviews.index') }}" method="GET" class="admin-toolbar">
                <input type="text" name="q" value="{{ request('q') }}"
                       class="form-control form-control-sm admin-filter-field"
                       placeholder="Tìm khách, phòng, nội dung...">

                <button type="submit" class="btn btn-light btn-sm flex-shrink-0">
                    <i class="bi bi-search me-1"></i>Tìm
                </button>

                @if(request('q'))
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-light btn-sm flex-shrink-0">
                        Xóa lọc
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            <div class="admin-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Người dùng</th>
                            <th>Phòng</th>
                            <th>Xếp hạng</th>
                            <th>Tiêu đề</th>
                            <th>Ngày đánh giá</th>
                            <th class="text-end text-nowrap" style="min-width: 9rem;">Hành động</th>
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
                                <td class="text-end">
                                    <div class="admin-table-actions">
                                        <a href="{{ route('admin.reviews.show', $review) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Chi tiết
                                        </a>
                                        @if(auth()->user()->isAdmin())
                                        <form action="{{ route('admin.reviews.destroy', $review) }}"
                                              method="POST"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa
                                            </button>
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
            <div class="card-footer bg-white border-0 py-3">
                {{ $reviews->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection