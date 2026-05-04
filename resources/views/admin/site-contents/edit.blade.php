@extends('layouts.admin')

@section('title', 'Chỉnh sửa nội dung')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-pencil me-2"></i>
                    Chỉnh sửa nội dung
                </h2>
                <a href="{{ route('admin.site-contents.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.site-contents.update', $siteContent) }}" 
                          method="POST" 
                          enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="type" class="form-label">
                                Loại nội dung <span class="text-danger">*</span>
                            </label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">-- Chọn loại nội dung --</option>
                                @php
                                    $types = [
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
                                @foreach($types as $value => $label)
                                <option value="{{ $value }}" {{ old('type', $siteContent->type) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề</label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title', $siteContent->title) }}"
                                   placeholder="Nhập tiêu đề nội dung">
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Nội dung</label>
                            <textarea name="content" 
                                      id="content" 
                                      rows="12"
                                      class="form-control @error('content') is-invalid @enderror"
                                      placeholder="Nhập nội dung chi tiết (hỗ trợ HTML)">{{ old('content', $siteContent->content) }}</textarea>
                            <small class="form-text text-muted">
                                💡 Bạn có thể sử dụng HTML để định dạng nội dung
                            </small>
                            @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">Hình ảnh</label>
                            @if($siteContent->image_url)
                            <div class="mb-2">
                                <img src="{{ $siteContent->image_url }}" alt="Current image" 
                                     class="img-thumbnail" style="max-width: 200px;">
                                <p class="small text-muted mt-1">Hình ảnh hiện tại</p>
                            </div>
                            @endif
                            <input type="file" 
                                   name="image_url" 
                                   id="image_url" 
                                   class="form-control @error('image_url') is-invalid @enderror"
                                   accept="image/*">
                            <small class="form-text text-muted">
                                📷 Upload ảnh mới để thay thế. Dung lượng tối đa: 2MB
                            </small>
                            @error('image_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       name="is_active" 
                                       id="is_active" 
                                       value="1" 
                                       {{ old('is_active', $siteContent->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Hiển thị trên website
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('admin.site-contents.index') }}" class="btn btn-secondary">
                                Hủy
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                Cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">📊 Thông tin</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <strong>Tạo lúc:</strong> {{ $siteContent->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Cập nhật:</strong> {{ $siteContent->updated_at?->format('d/m/Y H:i') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
