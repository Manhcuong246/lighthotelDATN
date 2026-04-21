@extends('layouts.admin')

@section('title', 'Quản lý nội dung website')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Quản lý nội dung website
                </h2>
                <a href="{{ route('admin.site-contents.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Thêm nội dung mới
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @foreach($groupedContents as $type => $contents)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        @php
                            $typeLabels = [
                                'contact_info' => '📞 Thông tin liên hệ',
                                'contact_banner' => '🖼️ Banner trang liên hệ',
                                'help_content' => '❓ Nội dung trợ giúp',
                                'help_banner' => '🖼️ Banner trang trợ giúp',
                                'policy_content' => '📋 Nội dung chính sách',
                                'policy_banner' => '🖼️ Banner trang chính sách',
                                'about_content' => 'ℹ️ Nội dung giới thiệu',
                                'about_banner' => '🖼️ Banner trang giới thiệu',
                            ];
                        @endphp
                        {{ $typeLabels[$type] ?? $type }}
                        <span class="badge bg-secondary ms-2">{{ $contents->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Tiêu đề</th>
                                    <th style="width: 40%;">Nội dung</th>
                                    <th style="width: 15%;">Hình ảnh</th>
                                    <th style="width: 10%;">Trạng thái</th>
                                    <th style="width: 15%;" class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contents as $content)
                                <tr>
                                    <td>
                                        <strong>{{ $content->title ?? 'Không có tiêu đề' }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ Str::limit(strip_tags($content->content), 100) ?? '—' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($content->image_url)
                                        <img src="{{ $content->image_url }}" alt="Image" 
                                             class="img-thumbnail" style="max-width: 100px; max-height: 60px;">
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($content->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                        @else
                                        <span class="badge bg-secondary">Ẩn</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.site-contents.edit', $content) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.site-contents.destroy', $content) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Bạn có chắc muốn xóa nội dung này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @if($groupedContents->isEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Chưa có nội dung nào</h5>
                    <p class="text-muted">Hãy thêm nội dung mới cho website của bạn</p>
                    <a href="{{ route('admin.site-contents.create') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-plus-circle me-1"></i>
                        Thêm nội dung đầu tiên
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
