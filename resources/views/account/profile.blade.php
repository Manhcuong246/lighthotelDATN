@extends('layouts.app')

@section('title', 'Thông tin cá nhân')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4">Thông tin cá nhân</h2>

        {{-- Thông tin cá nhân --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="card-title mb-4">Hồ sơ</h5>
                <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="form-label">Ảnh đại diện</label>
                        <div class="d-flex align-items-center gap-3">
                            @php
                                $avatarUrl = $user->avatar_url
                                    ? (str_starts_with($user->avatar_url, 'http') ? $user->avatar_url : '/storage/' . $user->avatar_url . '?v=' . config('room_images.cache_version', '1'))
                                    : null;
                            @endphp
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="Avatar" class="rounded-circle border" style="width:80px;height:80px;object-fit:cover; cursor:pointer;" id="avatarPreview" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="openProfileModal(event)">
                            @else
                                <div class="rounded-circle border d-flex align-items-center justify-content-center bg-light" style="width:80px;height:80px;font-size:2rem;font-weight:600;color:#94a3b8; cursor:pointer;" id="avatarPreview" data-bs-toggle="modal" data-bs-target="#profileModal" onclick="openProfileModal(event)">
                                    {{ strtoupper(mb_substr($user->full_name ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*" id="avatarInput">
                                <small class="text-muted">JPG, PNG, GIF, WebP. Tối đa 2MB</small>
                            </div>
                        </div>
                        @error('avatar') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" class="form-control @error('full_name') is-invalid @enderror" name="full_name" value="{{ old('full_name', $user->full_name) }}" required>
                        @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
                        <small class="text-muted">Email không thể thay đổi.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xx xxx xxx">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </form>
            </div>
        </div>

        {{-- Đổi mật khẩu --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h5 class="card-title mb-4">Đổi mật khẩu</h5>
                <form method="POST" action="{{ route('account.profile.update.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required>
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                </form>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Initialize Bootstrap modal
document.addEventListener('DOMContentLoaded', function() {
    // Check if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined') {
        console.log('Bootstrap is loaded');
    } else {
        console.log('Bootstrap not loaded, loading manually...');
        // Load Bootstrap if not available
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js';
        script.onload = function() {
            console.log('Bootstrap loaded successfully');
        };
        document.head.appendChild(script);
    }
});

function openProfileModal(event) {
    event.preventDefault();

    console.log('Opening profile modal...');

    // Check if modal exists
    var modalElement = document.getElementById('profileModal');
    if (!modalElement) {
        console.error('Modal element not found!');
        alert('Modal không tìm thấy! Vui lòng refresh trang.');
        return;
    }

    console.log('Modal element found:', modalElement);

    // Try to use Bootstrap modal first
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        console.log('Using Bootstrap modal');
        var modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.log('Bootstrap not available, using manual display');
        // Fallback: manually show modal
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

document.getElementById('avatarInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function() {
            const preview = document.getElementById('avatarPreview');
            if (preview.tagName === 'IMG') {
                preview.src = reader.result;
            } else {
                const img = document.createElement('img');
                img.src = reader.result;
                img.alt = 'Avatar';
                img.className = 'rounded-circle border';
                img.style.cssText = 'width:80px;height:80px;object-fit:cover;';
                img.id = 'avatarPreview';
                preview.parentNode.replaceChild(img, preview);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">
                    <i class="bi bi-person-circle me-2"></i>
                    Thông tin cá nhân & Lịch đặt phòng
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {{-- User Info --}}
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="bi bi-person me-2"></i>Thông tin cá nhân</h6>
                        <div class="card">
                            <div class="card-body">
                                <p><strong>Họ và tên:</strong> {{ $user->full_name }}</p>
                                <p><strong>Email:</strong> {{ $user->email }}</p>
                                <p><strong>Số điện thoại:</strong> {{ $user->phone ?? 'Chưa cập nhật' }}</p>
                                <p><strong>Ngày tham gia:</strong> {{ \Carbon\Carbon::parse($user->created_at)->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>


                        {{-- Cancellation History --}}
                        <div class="col-md-12 mt-4">
                            <h6 class="mb-3"><i class="bi bi-x-circle me-2"></i>Lịch hủy phòng</h6>
                            @php
                                $cancelledBookings = \App\Models\Booking::where('user_id', auth()->id())
                                    ->where('status', 'cancelled')
                                    ->with(['room', 'rooms'])
                                    ->orderBy('created_at', 'desc')
                                    ->get();
                            @endphp

                            @if($cancelledBookings->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Phòng</th>
                                                <th>Ngày hủy</th>
                                                <th>Lý do</th>
                                                <th>Tiền hoàn lại</th>
                                                <th>Phí hủy</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cancelledBookings as $booking)
                                                @php
                                                    $cancelRoom = $booking->rooms->first() ?? $booking->room;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        @if($cancelRoom)
                                                            <a href="{{ route('rooms.show', $cancelRoom) }}" class="text-decoration-none">
                                                                {{ $cancelRoom->name }}
                                                            </a>
                                                            @if($booking->rooms->count() > 1)
                                                                <span class="text-muted small">(+{{ $booking->rooms->count() - 1 }} phòng)</span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($booking->cancelled_at ?? $booking->updated_at)->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $booking->cancellation_reason ?? 'Yêu cầu từ khách hàng' }}</td>
                                                    <td class="text-success fw-semibold">{{ number_format($booking->refund_amount ?? 0, 0, ',', '.') }} VNĐ</td>
                                                    <td class="text-danger">{{ number_format($booking->cancellation_fee ?? 0, 0, ',', '.') }} VNĐ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-check-circle fs-1 text-muted mb-3 d-block"></i>
                                    <p class="text-muted">Bạn không có lịch hủy phòng nào.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.modal-avatar {
    transition: transform 0.2s, box-shadow 0.2s;
}
.modal-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
@endpush
