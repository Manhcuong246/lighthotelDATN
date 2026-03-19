@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.flatpickr-day.flatpickr-disabled { color: #ccc !important; cursor: not-allowed !important; }

.room-booking-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 40px rgba(15,23,42,0.1), 0 2px 10px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.05);
    overflow: hidden;
}

.room-booking-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #fff;
    padding: 1.25rem 1.5rem;
}
.room-booking-header h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.02em;
}
.room-booking-header .sub { opacity: 0.8; font-size: 0.875rem; margin-top: 0.25rem; }

.room-booking-body { padding: 1.5rem 1.5rem 1.75rem; }

.room-booking-group {
    margin-bottom: 1.5rem;
}
.room-booking-group:last-of-type { margin-bottom: 0; }
.room-booking-group-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.room-booking-card .form-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #475569;
    margin-bottom: 0.35rem;
}
.room-booking-card .form-control {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: 0.6rem 0.85rem;
    font-size: 0.9375rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.room-booking-card .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    outline: none;
}
.room-booking-card .form-control::placeholder { color: #94a3b8; }

/* Service items - không overlap */
.room-booking-services {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.room-booking-service-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}
.room-booking-service-item:hover { background: #f1f5f9; }
.room-booking-service-item:has(input:checked) {
    background: #eff6ff;
    border-color: #93c5fd;
}
.room-booking-service-item input { cursor: pointer; margin-right: 0.75rem; }
.room-booking-service-item label {
    flex: 1;
    cursor: pointer;
    margin: 0;
    font-size: 0.9375rem;
    color: #334155;
}
.room-booking-service-item .price {
    font-weight: 600;
    color: #0f172a;
    font-size: 0.9375rem;
}

/* Price summary */
.room-booking-summary {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1rem 1.25rem;
}
.room-booking-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    color: #475569;
}
.room-booking-summary-row.total {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #bae6fd;
    font-size: 1rem;
    font-weight: 600;
    color: #0f172a;
}
.room-booking-summary-row.total .val { font-size: 1.25rem; color: #059669; }

.room-booking-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
}
@media (min-width: 768px) {
    .room-booking-actions {
        flex-direction: row;
        align-items: flex-end;
        justify-content: space-between;
    }
    .room-booking-actions .btn-book { width: auto; min-width: 200px; }
    .room-booking-actions .room-booking-summary { width: auto; min-width: 220px; }
}

.room-booking-card .btn-book {
    padding: 0.85rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border: none;
    box-shadow: 0 4px 14px rgba(37,99,235,0.4);
    transition: transform 0.2s, box-shadow 0.2s;
}
.room-booking-card .btn-book:hover {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    box-shadow: 0 6px 20px rgba(37,99,235,0.45);
    transform: translateY(-1px);
}
.room-booking-card .btn-book:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
@endpush

<div class="room-booking-card">
    <div class="room-booking-header">
        <h4>Đặt phòng</h4>
        <p class="sub">Điền thông tin và chọn ngày để xem giá</p>
    </div>
    <div class="room-booking-body">
        <form method="POST" action="{{ route('bookings.store', $room) }}" id="bookingForm">
            @csrf
            <input type="hidden" name="payment_method" value="vnpay">

            @php
                $user = auth()->user();
                $isLoggedIn = $user !== null;
            @endphp
            <div class="room-booking-group">
                <div class="room-booking-group-title">Thông tin liên hệ</div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" name="full_name" class="form-control"
                               value="{{ old('full_name', $isLoggedIn ? $user->full_name : '') }}"
                               placeholder="{{ $isLoggedIn ? '' : 'Nguyễn Văn A' }}"
                               {{ $isLoggedIn ? 'readonly' : '' }} required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $isLoggedIn ? $user->email : '') }}"
                               placeholder="{{ $isLoggedIn ? '' : 'email@example.com' }}"
                               {{ $isLoggedIn ? 'readonly' : '' }} required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $isLoggedIn ? ($user->phone ?? '') : '') }}"
                               placeholder="{{ $isLoggedIn ? '' : '0901234567' }}"
                               {{ $isLoggedIn ? 'readonly' : '' }}>
                    </div>
                </div>
            </div>

            <div class="room-booking-group">
                <div class="room-booking-group-title">Ngày đặt và số khách</div>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Ngày nhận phòng</label>
                        <input type="text" name="check_in" class="form-control" id="check_in"
                               value="{{ old('check_in') }}" placeholder="dd/mm/yyyy" required readonly>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Ngày trả phòng</label>
                        <input type="text" name="check_out" class="form-control" id="check_out"
                               value="{{ old('check_out') }}" placeholder="dd/mm/yyyy" required readonly>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Số lượng khách</label>
                        <input type="number" name="guests" class="form-control" id="guests"
                               min="1" max="{{ $room->max_guests }}"
                               value="{{ old('guests', 1) }}" required>
                    </div>
                </div>
            </div>

            @if(isset($services) && $services->count() > 0)
            <div class="room-booking-group">
                <div class="room-booking-group-title">Dịch vụ đi kèm (tùy chọn)</div>
                <div class="room-booking-services">
                    @foreach($services as $service)
                    <div class="room-booking-service-item">
                        <input class="form-check-input" type="checkbox"
                               name="services[]"
                               value="{{ $service->id }}"
                               id="service_{{ $service->id }}"
                               data-price="{{ $service->price }}"
                               onchange="updateTotalPrice()">
                        <label for="service_{{ $service->id }}">{{ $service->name }}</label>
                        <span class="price">{{ number_format($service->price, 0, ',', '.') }} ₫</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="room-booking-actions">
                <div class="room-booking-summary w-100" id="pricePreview" style="display: none;">
                    <div class="room-booking-summary-row">
                        <span>Số đêm</span>
                        <strong id="nightsCount">0</strong>
                    </div>
                    <div class="room-booking-summary-row">
                        <span>Giá/đêm</span>
                        <strong>{{ number_format($room->base_price, 0, ',', '.') }} ₫</strong>
                    </div>
                    <div class="room-booking-summary-row total">
                        <span>Tổng tiền</span>
                        <span class="val" id="totalPrice">0 ₫</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-book w-100" id="submitBtn" disabled>
                    Xác nhận đặt phòng
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkInEl = document.getElementById('check_in');
        const checkOutEl = document.getElementById('check_out');
        const pricePreview = document.getElementById('pricePreview');
        const nightsCount = document.getElementById('nightsCount');
        const totalPrice = document.getElementById('totalPrice');
        const submitBtn = document.getElementById('submitBtn');

        const basePrice = Number('{{ $room->base_price ?? 0 }}');
        const bookedDates = @json($bookedDates ?? []);

        const fpCheckIn = flatpickr(checkInEl, {
            minDate: 'today',
            disable: bookedDates,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            locale: 'vn',
            onChange: function(selectedDates, dateStr) {
                fpCheckOut.set('minDate', dateStr);
                if (fpCheckOut.selectedDates[0] && fpCheckOut.selectedDates[0] <= selectedDates[0]) {
                    fpCheckOut.clear();
                }
                calculatePrice();
            }
        });

        const fpCheckOut = flatpickr(checkOutEl, {
            minDate: 'today',
            disable: bookedDates,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            locale: 'vn',
            onChange: function(selectedDates, dateStr) {
                if (selectedDates[0] && fpCheckIn.selectedDates[0]) {
                    const start = new Date(fpCheckIn.selectedDates[0]);
                    const end = new Date(selectedDates[0]);
                    for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                        const ymd = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                        if (bookedDates.includes(ymd)) {
                            fpCheckOut.clear();
                            return;
                        }
                    }
                }
                calculatePrice();
            }
        });

        function calculatePrice() {
            const checkInVal = checkInEl.value;
            const checkOutVal = checkOutEl.value;
            if (checkInVal && checkOutVal) {
                const start = new Date(checkInVal);
                const end = new Date(checkOutVal);
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
            document.querySelectorAll('input[name="services[]"]:checked').forEach(function(cb) {
                total += Number(cb.dataset.price || 0);
            });
            return total;
        }

        function updateTotalPrice() {
            calculatePrice();
        }
    });
</script>
