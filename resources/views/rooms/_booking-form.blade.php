<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="card-title mb-3">Đặt phòng</h4>
        <form method="POST" action="{{ route('bookings.store', $room) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="full_name" class="form-control"
                       value="{{ old('full_name') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Số điện thoại</label>
                <input type="text" name="phone" class="form-control"
                       value="{{ old('phone') }}">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày nhận phòng</label>
                    <input type="date" name="check_in" class="form-control"
                           value="{{ old('check_in') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày trả phòng</label>
                    <input type="date" name="check_out" class="form-control"
                           value="{{ old('check_out') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Số lượng khách</label>
                <input type="number" name="guests" class="form-control"
                       min="1" max="{{ $room->max_guests }}"
                       value="{{ old('guests', 1) }}" required>
            </div>

            <!-- Dịch vụ đi kèm -->
            @if(isset($services) && $services->count() > 0)
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-plus-circle"></i> Dịch vụ đi kèm (tùy chọn)
                </label>
                <div class="services-list border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                    @foreach($services as $service)
                    <div class="form-check service-item d-flex justify-content-between align-items-center py-1">
                        <div>
                            <input class="form-check-input" type="checkbox" 
                                   name="services[]" 
                                   value="{{ $service->id }}" 
                                   id="service_{{ $service->id }}"
                                   data-price="{{ $service->price }}"
                                   onchange="updateTotalPrice()">
                            <label class="form-check-label" for="service_{{ $service->id }}">
                                {{ $service->name }}
                            </label>
                        </div>
                        <span class="text-muted small">{{ number_format($service->price, 0, ',', '.') }} ₫</span>
                    </div>
                    @endforeach
                </div>
                <small class="text-muted">Chọn dịch vụ bổ sung nếu cần</small>
            </div>
            @endif

            <button type="submit" class="btn btn-primary w-100">
                Xác nhận đặt phòng
            </button>
        </form>
    </div>
</div>
