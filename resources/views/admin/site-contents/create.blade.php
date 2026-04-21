@extends('layouts.admin')

@section('title', 'Thêm nội dung mới')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>
                    Thêm nội dung mới
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
                    <form action="{{ route('admin.site-contents.store') }}" 
                          method="POST" 
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="type" class="form-label fw-bold">
                                Loại nội dung <span class="text-danger">*</span>
                            </label>
                            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">-- Chọn loại nội dung --</option>
                                <option value="contact_info" {{ old('type') == 'contact_info' ? 'selected' : '' }}>📞 Thông tin liên hệ</option>
                                <option value="contact_banner" {{ old('type') == 'contact_banner' ? 'selected' : '' }}>🖼️ Banner trang liên hệ</option>
                                <option value="help_content" {{ old('type') == 'help_content' ? 'selected' : '' }}>❓ Nội dung trợ giúp</option>
                                <option value="help_banner" {{ old('type') == 'help_banner' ? 'selected' : '' }}>🖼️ Banner trang trợ giúp</option>
                                <option value="policy_content" {{ old('type') == 'policy_content' ? 'selected' : '' }}>📋 Nội dung chính sách</option>
                                <option value="policy_banner" {{ old('type') == 'policy_banner' ? 'selected' : '' }}>🖼️ Banner trang chính sách</option>
                                <option value="about_content" {{ old('type') == 'about_content' ? 'selected' : '' }}>ℹ️ Nội dung giới thiệu</option>
                                <option value="about_banner" {{ old('type') == 'about_banner' ? 'selected' : '' }}>🖼️ Banner trang giới thiệu</option>
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Tiêu đề</label>
                            <input type="text" 
                                   name="title" 
                                   id="title" 
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}"
                                   placeholder="Nhập tiêu đề nội dung">
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label fw-bold">Nội dung</label>
                            <textarea name="content" 
                                      id="content" 
                                      rows="12"
                                      class="form-control @error('content') is-invalid @enderror"
                                      placeholder="Nhập nội dung chi tiết (hỗ trợ HTML)">{{ old('content') }}</textarea>
                            <small class="form-text text-muted">
                                💡 Bạn có thể sử dụng HTML để định dạng nội dung
                            </small>
                            @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label fw-bold">Hình ảnh</label>
                            <input type="file" 
                                   name="image_url" 
                                   id="image_url" 
                                   class="form-control @error('image_url') is-invalid @enderror"
                                   accept="image/*">
                            <small class="form-text text-muted">
                                📷 Dung lượng tối đa: 2MB. Định dạng: JPG, PNG, GIF
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
                                       {{ old('is_active', true) ? 'checked' : '' }}>
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
                                Tạo nội dung
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">💡 Hướng dẫn</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <strong>Thông tin liên hệ:</strong> Sử dụng cho trang contact
                    </p>
                    <p class="small text-muted mb-2">
                        <strong>Nội dung trợ giúp:</strong> FAQ và hướng dẫn sử dụng
                    </p>
                    <p class="small text-muted mb-2">
                        <strong>Nội dung chính sách:</strong> Điều khoản, bảo mật, hủy phòng
                    </p>
                    <p class="small text-muted mb-0">
                        <strong>Banner:</strong> Hình ảnh hiển thị đầu các trang
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
