<div class="card shadow-sm">
    <div class="card-body">
        <h4 class="card-title mb-3">Đặt phòng</h4>
        <form method="POST" action="{{ route('bookings.store', $room) }}" id="bookingForm">
            @csrf
            <input type="hidden" name="payment_method" value="bank_transfer">
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
                    <input type="date" name="check_in" class="form-control" id="check_in"
                           value="{{ old('check_in') }}" required min="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Ngày trả phòng</label>
                    <input type="date" name="check_out" class="form-control" id="check_out"
                           value="{{ old('check_out') }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Số lượng khách</label>
                <input type="number" name="guests" class="form-control" id="guests"
                       min="1" max="{{ $room->max_guests }}"
                       value="{{ old('guests', 1) }}" required>
            </div>

            <!-- Price Preview -->
            <div class="mb-3 p-3 bg-light rounded" id="pricePreview" style="display: none;">
                <div class="d-flex justify-content-between">
                    <span>Số đêm:</span>
                    <strong id="nightsCount">0</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Giá/đêm:</span>
                    <strong>{{ number_format($room->base_price, 0, ',', '.') }} ₫</strong>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span>Tổng tiền:</span>
                    <strong class="text-success" id="totalPrice">0 ₫</strong>
                </div>
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

            <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                Xác nhận đặt phòng
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkIn = document.getElementById('check_in');
        const checkOut = document.getElementById('check_out');
        const pricePreview = document.getElementById('pricePreview');
        const nightsCount = document.getElementById('nightsCount');
        const totalPrice = document.getElementById('totalPrice');
        const submitBtn = document.getElementById('submitBtn');

        const basePrice = Number('{{ $room->base_price ?? 0 }}');

        function calculatePrice() {
            if (checkIn.value && checkOut.value) {
                const start = new Date(checkIn.value);
                const end = new Date(checkOut.value);
                const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

                if (nights > 0) {
                    const roomTotal = basePrice * nights;
                    const servicesTotal = calculateServicesTotal();
                    const total = roomTotal + servicesTotal;
                    nightsCount.textContent = nights;
                    totalPrice.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
                    pricePreview.style.display = 'block';
                    submitBtn.disabled = false;
                    return total;
                }
            }
            pricePreview.style.display = 'none';
            submitBtn.disabled = true;
            return 0;
        }

        function calculateServicesTotal() {
            let total = 0;
            document.querySelectorAll('.service-item input[type="checkbox"]:checked').forEach(function(checkbox) {
                total += Number(checkbox.dataset.price);
            });
            return total;
        }

        function updateTotalPrice() {
            calculatePrice();
        }

        checkIn.addEventListener('change', function() {
            checkOut.min = this.value;
            if (checkOut.value && checkOut.value <= this.value) {
                checkOut.value = '';
            }
            calculatePrice();
        });

        checkOut.addEventListener('change', calculatePrice);
    });
</script>
