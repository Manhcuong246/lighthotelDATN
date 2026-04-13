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
    /* Removed fixed position to allow container-based layout */
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

.room-booking-body { padding: 1.5rem; }

.room-booking-group {
    margin-bottom: 1.25rem;
}
.room-booking-group:last-of-type { margin-bottom: 0; }
.room-booking-group-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 4px;
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

/* Price summary */
.room-booking-summary {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.25rem;
}
.room-booking-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
    color: #475569;
    margin-bottom: 0.25rem;
}
.room-booking-summary-row.total {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #bae6fd;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}
.room-booking-summary-row.total .val { font-size: 1.25rem; color: #2563eb; }

.btn-book {
    padding: 0.85rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border: none;
    box-shadow: 0 4px 14px rgba(37,99,235,0.3);
    transition: all 0.2s;
    color: #fff;
    width: 100%;
}
.btn-book:hover:not(:disabled) {
    background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    box-shadow: 0 6px 20px rgba(37,99,235,0.4);
    transform: translateY(-1px);
    color: #fff;
}
.btn-book:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
@endpush

<div class="room-booking-card shadow-lg">
    <div class="room-booking-header">
        <h4>Đặt phòng</h4>
        <p class="sub mb-0 text-white-50">Chọn ngày để xem giá và đặt ngay</p>
    </div>
    <div class="room-booking-body">
        <form method="POST" action="{{ route('bookings.store') }}" id="bookingForm">
            @csrf
            <input type="hidden" name="room_ids[]" value="{{ $room->id }}">
            <input type="hidden" name="payment_method" value="vnpay">

            @php
                $user = auth()->user();
                $isLoggedIn = $user !== null;
            @endphp

            {{-- Thông tin liên hệ --}}
            <div class="room-booking-group">
                <div class="room-booking-group-title">Thông tin liên hệ</div>
                <div class="mb-3">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="full_name" class="form-control"
                           value="{{ old('full_name', $isLoggedIn ? $user->full_name : '') }}"
                           placeholder="Họ và tên của bạn"
                           {{ $isLoggedIn ? 'readonly' : '' }} required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $isLoggedIn ? $user->email : '') }}"
                           placeholder="Địa chỉ email"
                           {{ $isLoggedIn ? 'readonly' : '' }} required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone', $isLoggedIn ? ($user->phone ?? '') : '') }}"
                           placeholder="Số điện thoại"
                           {{ $isLoggedIn ? 'readonly' : '' }}>
                </div>
            </div>

            {{-- Ngày đặt --}}
            <div class="room-booking-group">
                <div class="room-booking-group-title">Ngày nhận & trả phòng</div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Ngày nhận</label>
                        <input type="text" name="check_in" class="form-control" id="check_in"
                               value="{{ old('check_in') }}" placeholder="dd/mm/yyyy" required readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Ngày trả</label>
                        <input type="text" name="check_out" class="form-control" id="check_out"
                               value="{{ old('check_out') }}" placeholder="dd/mm/yyyy" required readonly>
                    </div>
                </div>
            </div>

            {{-- Tổng tiền & nút đặt --}}
            <div id="pricePreview" style="display: none;">
                <div class="room-booking-summary">
                    <div class="room-booking-summary-row">
                        <span>Số đêm</span>
                        <strong id="nightsCount">0</strong>
                    </div>
                    <div class="room-booking-summary-row">
                        <span>Giá/đêm</span>
                        <strong id="pricePerNightDisplay">{{ number_format($room->catalogueBasePrice(), 0, ',', '.') }} ₫</strong>
                    </div>
                    <div class="room-booking-summary-row total">
                        <span>Tổng tiền</span>
                        <span class="val" id="totalPrice">0 ₫</span>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-book" id="submitBtn" disabled>
                Xác nhận đặt phòng
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkInEl   = document.getElementById('check_in');
        const checkOutEl  = document.getElementById('check_out');
        const pricePreview = document.getElementById('pricePreview');
        const nightsCount  = document.getElementById('nightsCount');
        const totalPriceEl = document.getElementById('totalPrice');
        const submitBtn    = document.getElementById('submitBtn');
        const basePrice    = Number('{{ $room->catalogueBasePrice() }}');
        const bookedDates  = @json($bookedDates ?? []);

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
                    const end   = new Date(selectedDates[0]);
                    
                    // Check if any date in range is booked
                    let conflict = false;
                    for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                        const ymd = d.toISOString().split('T')[0];
                        if (bookedDates.includes(ymd)) {
                            conflict = true;
                            break;
                        }
                    }
                    if (conflict) {
                        fpCheckOut.clear();
                        alert('Khoảng thời gian này có ngày đã được đặt. Vui lòng chọn lại.');
                        return;
                    }
                }
                calculatePrice();
            }
        });

        function calculatePrice() {
            const ci = checkInEl.value;
            const co = checkOutEl.value;
            if (ci && co) {
                const diffTime = Math.abs(new Date(co) - new Date(ci));
                const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (nights > 0) {
                    const total = basePrice * nights;
                    nightsCount.textContent = nights;
                    totalPriceEl.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' ₫';
                    pricePreview.style.display = 'block';
                    submitBtn.disabled = false;
                    return;
                }
            }
            pricePreview.style.display = 'none';
            submitBtn.disabled = true;
        }
    });
</script>
