@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống')

@section('content')
<<<<<<< HEAD
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-dark">Cài đặt hệ thống</h1>
=======
<div class="container-fluid px-0">
    <div class="page-header">
        <h1 class="text-dark fw-bold">Cài đặt hệ thống</h1>
>>>>>>> vinam
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<<<<<<< HEAD
    <div class="row">
        <div class="col-lg-8">
=======
    <div class="row g-3">
        <div class="col-12 col-lg-8">
>>>>>>> vinam
            <!-- General Settings -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Thông tin khách sạn</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update.general') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tên khách sạn</label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name', $hotelInfo->name ?? '') }}"
                                       placeholder="Tên khách sạn">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       name="email"
                                       value="{{ old('email', $hotelInfo->email ?? '') }}"
                                       placeholder="Email của khách sạn">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Điện thoại</label>
                                <input type="text"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       name="phone"
                                       value="{{ old('phone', $hotelInfo->phone ?? '') }}"
                                       placeholder="Số điện thoại">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Địa chỉ</label>
                                <input type="text"
                                       class="form-control @error('address') is-invalid @enderror"
                                       name="address"
                                       value="{{ old('address', $hotelInfo->address ?? '') }}"
                                       placeholder="Địa chỉ">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      name="description"
                                      rows="4"
                                      placeholder="Mô tả về khách sạn">{{ old('description', $hotelInfo->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">🏦 Cấu hình thanh toán QR (VietQR)</h6>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Mã ngân hàng</label>
                                <input type="text"
                                       class="form-control @error('bank_id') is-invalid @enderror"
                                       name="bank_id"
                                       value="{{ old('bank_id', $hotelInfo->bank_id ?? '') }}"
                                       placeholder="VD: mbbank, vietcombank, vcb">
                                <small class="text-muted">VD: mbbank, vietcombank, techcombank...</small>
                                @error('bank_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Số tài khoản</label>
                                <input type="text"
                                       class="form-control @error('bank_account') is-invalid @enderror"
                                       name="bank_account"
                                       value="{{ old('bank_account', $hotelInfo->bank_account ?? '') }}"
                                       placeholder="Số tài khoản ngân hàng">
                                @error('bank_account')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tên chủ tài khoản</label>
                                <input type="text"
                                       class="form-control @error('bank_account_name') is-invalid @enderror"
                                       name="bank_account_name"
                                       value="{{ old('bank_account_name', $hotelInfo->bank_account_name ?? '') }}"
                                       placeholder="Tên chủ tài khoản">
                                @error('bank_account_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        @if($hotelInfo && $hotelInfo->bank_id && $hotelInfo->bank_account)
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> QR Code sẽ được tạo tự động khi tạo đơn đặt phòng với phương thức thanh toán Chuyển khoản.
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

<<<<<<< HEAD
            <!-- Site Content Settings -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3">
                    <h5 class="mb-0">Nội dung trang web</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update.site.content') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tiêu đề trang chủ</label>
                            <input type="text" 
                                   class="form-control @error('home_title') is-invalid @enderror" 
                                   name="home_title" 
                                   value="{{ old('home_title') }}"
                                   placeholder="Tiêu đề trang chủ">
                            @error('home_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả trang chủ</label>
                            <textarea class="form-control @error('home_description') is-invalid @enderror" 
                                      name="home_description" 
                                      rows="4"
                                      placeholder="Mô tả trang chủ">{{ old('home_description') }}</textarea>
                            @error('home_description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
=======
            <!-- Site Content Management -->
            <div class="card card-admin shadow mb-4">
                <div class="card-header-admin py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bảng quản lý nội dung trang web</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#siteContentModal" onclick="openContentModal();">
                        <i class="bi bi-plus-circle"></i> Thêm nội dung
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Loại</th>
                                    <th>Tiêu đề</th>
                                    <th>Nội dung</th>
                                    <th>Hình ảnh</th>
                                    <th>Trạng thái</th>
                                    <th>Xem trên site</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($siteContents as $content)
                                    <tr>
                                        <td>{{ $content->id }}</td>
                                        <td class="text-uppercase">{{ $content->type }}</td>
                                        <td>{{ $content->title }}</td>
                                        <td class="text-truncate" style="max-width: 240px;">{{ Str::limit($content->content, 80) }}</td>
                                        <td>{{ $content->image_url ?? '-' }}</td>
                                        <td>
                                            @if($content->is_active)
                                                <span class="badge bg-success">Đã kích hoạt</span>
                                            @else
                                                <span class="badge bg-secondary">Ẩn</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('home') }}#{{ $content->type }}" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        </td>
                                        <td class="text-nowrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-id="{{ $content->id }}"
                                                    data-type="{{ $content->type }}"
                                                    data-title="{{ e($content->title) }}"
                                                    data-content="{{ e($content->content) }}"
                                                    data-image-url="{{ e($content->image_url) }}"
                                                    data-active="{{ $content->is_active ? '1' : '0' }}"
                                                    onclick="openContentModalFromBtn(this)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('admin.settings.site.content.destroy', $content) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa nội dung này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Chưa có mục nội dung nào. Hãy thêm mới.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
>>>>>>> vinam
                </div>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
=======

<!-- Modal content manager -->
<div class="modal fade" id="siteContentModal" tabindex="-1" aria-labelledby="siteContentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="siteContentForm" action="{{ route('admin.settings.site.content.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="siteContentMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="siteContentModalLabel">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Thêm nội dung</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" name="content_id" id="content_id" value="">

                    <!-- Loại nội dung & Trạng thái -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-tag"></i>
                            Thông tin cơ bản
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Loại nội dung</label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="" disabled selected>Chọn loại...</option>
                                    <option value="banner">🎨 Banner</option>
                                    <option value="about">ℹ️ Giới thiệu</option>
                                    <option value="policy">📋 Chính sách</option>
                                    <option value="footer">📄 Footer</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Trạng thái</label>
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active" id="statusLabel">Đang hoạt động</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tiêu đề -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-type"></i>
                            Tiêu đề
                        </div>
                        <input type="text" id="title" name="title" class="form-control" required placeholder="Nhập tiêu đề nội dung...">
                    </div>

                    <!-- Nội dung chính -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-chat-dots"></i>
                            Nội dung chi tiết
                        </div>
                        <textarea id="content" name="content" class="form-control" placeholder="Nhập nội dung chi tiết tại đây..."></textarea>
                    </div>

                    <!-- Hình ảnh -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="bi bi-image"></i>
                            Hình ảnh (tùy chọn)
                        </div>
                        <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle"></i> Nhập đường dẫn ảnh hoàn chỉnh
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitButton">
                        <i class="bi bi-check-circle me-1"></i> <span id="submitText">Lưu nội dung</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .text-truncate { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        
        /* Site Content Modal - Modern Design */
        #siteContentModal .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        
        #siteContentModal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 1.75rem;
        }
        
        #siteContentModal .modal-title {
            color: white;
            font-weight: 700;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        #siteContentModal .btn-close {
            filter: brightness(0) invert(1);
        }
        
        #siteContentModal .modal-body {
            padding: 2rem;
            background: #f8f9fa;
        }
        
        /* Form sections */
        .form-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .form-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-section-title i {
            font-size: 1.1rem;
        }
        
        #siteContentModal .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.6rem;
            font-size: 0.95rem;
        }
        
        #siteContentModal .form-control,
        #siteContentModal .form-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            box-sizing: border-box;
            width: 100%;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        #siteContentModal .form-control:focus,
        #siteContentModal .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background-color: #fff;
        }
        
        #siteContentModal textarea.form-control {
            resize: vertical;
            min-height: 150px;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: pre-wrap;
            overflow-x: hidden !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Status toggle styling */
        .form-check.form-switch .form-check-input {
            width: 2.5rem;
            height: 1.3rem;
            cursor: pointer;
            background-color: #cbd5e0;
            border: none;
            transition: all 0.3s ease;
        }
        
        .form-check.form-switch .form-check-input:checked {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }
        
        .form-check-label {
            color: #2d3748;
            font-weight: 500;
            cursor: pointer;
            margin-left: 0.5rem;
        }
        
        #siteContentModal .modal-footer {
            background: #f8f9fa;
            border-top: 1px solid #e2e8f0;
            padding: 1.5rem;
        }
        
        #siteContentModal .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        #siteContentModal .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        #siteContentModal .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        #siteContentModal .btn-secondary {
            background-color: #e2e8f0;
            border: none;
            color: #2d3748;
        }
        
        #siteContentModal .btn-secondary:hover {
            background-color: #cbd5e0;
        }
        
        #siteContentModal .modal-dialog {
            max-width: 650px;
        }
    </style>
@endpush

@push('scripts')
<script>
    function openContentModal(id = null, type = '', title = '', content = '', imageUrl = '', isActive = true) {
        const modal = new bootstrap.Modal(document.getElementById('siteContentModal'));
        const form = document.getElementById('siteContentForm');
        const modalLabel = document.getElementById('siteContentModalLabel');
        const submitButton = form.querySelector('#submitButton');
        const submitText = document.getElementById('submitText');
        const statusLabel = document.getElementById('statusLabel');

        document.getElementById('content_id').value = id || '';
        document.getElementById('type').value = type || '';
        document.getElementById('title').value = title || '';
        document.getElementById('content').value = content || '';
        document.getElementById('image_url').value = imageUrl || '';
        document.getElementById('is_active').checked = !!isActive;

        if (id) {
            modalLabel.innerHTML = '<i class="bi bi-pencil-square"></i><span>Chỉnh sửa nội dung #' + id + '</span>';
            form.action = '{{ route('admin.settings.site.content.update') }}';
            document.getElementById('siteContentMethod').value = 'PUT';
            submitText.innerText = 'Cập nhật';
            submitButton.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> <span id="submitText">Cập nhật</span>';
        } else {
            modalLabel.innerHTML = '<i class="bi bi-file-earmark-text"></i><span>Thêm nội dung mới</span>';
            form.action = '{{ route('admin.settings.site.content.store') }}';
            document.getElementById('siteContentMethod').value = 'POST';
            submitText.innerText = 'Thêm';
            submitButton.innerHTML = '<i class="bi bi-plus-circle me-1"></i> <span id="submitText">Thêm</span>';
        }

        // Update status label
        statusLabel.innerText = isActive ? 'Đang hoạt động' : 'Ẩn';

        modal.show();
    }

    function openContentModalFromBtn(button) {
        const id = button.dataset.id;
        const type = button.dataset.type || '';
        const title = button.dataset.title || '';
        const content = button.dataset.content || '';
        const imageUrl = button.dataset.imageUrl || '';
        const isActive = button.dataset.active === '1';

        openContentModal(id, type, title, content, imageUrl, isActive);
    }
</script>
@endpush

@isset($siteContent)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        openContentModal(
            {{ $siteContent->id }},
            @json($siteContent->type),
            @json($siteContent->title),
            @json($siteContent->content),
            @json($siteContent->image_url),
            {{ $siteContent->is_active ? 'true' : 'false' }}
        );
    });
</script>
@endpush
@endisset

>>>>>>> vinam
@endsection
