// Dynamic Booking Form Manager
class DynamicBookingForm {
    constructor() {
        this.init();
    }

    init() {
        console.log('Dynamic Booking Form initialized');
        this.bindEvents();
    }

    bindEvents() {
        // Form validation
        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', this.validateForm.bind(this));
        }
    }

    // Render rooms based on selected count
    renderRooms() {
        const roomCount = parseInt(document.getElementById('roomCount').value);
        const container = document.getElementById('roomsContainer');

        if (!roomCount || roomCount < 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<h5 class="mb-3"><i class="bi bi-door-open me-2"></i>Thông tin phòng</h5>';

        for (let i = 0; i < roomCount; i++) {
            html += this.createRoomBlock(i);
        }

        container.innerHTML = html;

        // Render guest inputs for each room after DOM is updated
        setTimeout(() => {
            for (let i = 0; i < roomCount; i++) {
                this.renderGuests(i);
            }
        }, 100);
    }

    // Create room block HTML
    createRoomBlock(roomIndex) {
        return `
            <div class="room-block mb-4 p-3 border rounded" data-room-index="${roomIndex}">
                <h6 class="mb-3">
                    <i class="bi bi-house-door me-2"></i>
                    Phòng ${roomIndex + 1}
                </h6>

                <!-- Số lượng người -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label small">Người lớn</label>
                        <input type="number"
                               id="adults_${roomIndex}"
                               class="form-control form-control-sm"
                               value="1"
                               min="1"
                               max="10"
                               onchange="renderGuests(${roomIndex})">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Trẻ em (0-5 tuổi)</label>
                        <input type="number"
                               id="children_0_5_${roomIndex}"
                               class="form-control form-control-sm"
                               value="0"
                               min="0"
                               max="5"
                               onchange="renderGuests(${roomIndex})">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Trẻ em (6-11 tuổi)</label>
                        <input type="number"
                               id="children_6_11_${roomIndex}"
                               class="form-control form-control-sm"
                               value="0"
                               min="0"
                               max="5"
                               onchange="renderGuests(${roomIndex})">
                    </div>
                </div>

                <!-- Guest inputs container -->
                <div id="guestsContainer_${roomIndex}" class="guests-container">
                    <!-- Guest inputs will be rendered after DOM is ready -->
                </div>
            </div>
        `;
    }

    // Render guest inputs for a specific room
    renderGuests(roomIndex) {
        const adultsInput = document.getElementById(`adults_${roomIndex}`);
        const children05Input = document.getElementById(`children_0_5_${roomIndex}`);
        const children611Input = document.getElementById(`children_6_11_${roomIndex}`);

        if (!adultsInput) return '';

        const adults = parseInt(adultsInput.value) || 0;
        const children05 = parseInt(children05Input?.value) || 0;
        const children611 = parseInt(children611Input?.value) || 0;

        // Chỉ render form cho người lớn (trẻ em không cần CCCD)
        const totalGuests = adults;

        if (totalGuests <= 0) {
            return '<div class="text-muted text-center py-2 bg-light rounded"><small>Vui lòng chọn số lượng người lớn</small></div>';
        }

        let html = '<h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Thông tin khách hàng</h6>';

        for (let i = 0; i < totalGuests; i++) {
            html += this.createGuestInput(roomIndex, i);
        }

        return html;
    }

    // Create guest input HTML
    createGuestInput(roomIndex, guestIndex) {
        return `
            <div class="guest-input-group mb-3 p-3 border rounded bg-light">
                <h6 class="mb-2">
                    <i class="bi bi-person me-2"></i>
                    Người lớn ${guestIndex + 1} - Phòng ${roomIndex + 1}
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label small">Họ tên <span class="text-danger">*</span></label>
                        <input type="text"
                               name="rooms[${roomIndex}][guests][${guestIndex}][name]"
                               class="form-control form-control-sm"
                               placeholder="Nhập họ tên người lớn ${guestIndex + 1}"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">CCCD (12 số) <span class="text-danger">*</span></label>
                        <input type="text"
                               name="rooms[${roomIndex}][guests][${guestIndex}][cccd]"
                               class="form-control form-control-sm"
                               placeholder="Nhập số CCCD"
                               maxlength="12"
                               pattern="[0-9]{12}"
                               required>
                        <div class="form-text text-muted small">CCCD phải gồm 12 số</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Form validation
    validateForm(e) {
        const roomCount = parseInt(document.getElementById('roomCount').value);

        if (!roomCount || roomCount < 1) {
            e.preventDefault();
            alert('Vui lòng chọn số lượng phòng!');
            return false;
        }

        // Kiểm tra tất cả guest inputs
        const guestInputs = document.querySelectorAll('[name*="guests"]');
        let hasErrors = false;

        guestInputs.forEach(input => {
            if (input.name.includes('name') && !input.value.trim()) {
                hasErrors = true;
                input.classList.add('is-invalid');
            } else if (input.name.includes('name')) {
                input.classList.remove('is-invalid');
            }

            if (input.name.includes('cccd')) {
                const value = input.value.trim();
                if (!/^[0-9]{12}$/.test(value)) {
                    hasErrors = true;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            }
        });

        if (hasErrors) {
            e.preventDefault();
            alert('Vui lòng điền đầy đủ thông tin khách hàng!');
            return false;
        }

        console.log('Form validation passed');
        return true;
    }
}

// Global functions for onclick handlers
function renderRooms() {
    window.dynamicBookingForm.renderRooms();
}

function renderGuests(roomIndex) {
    if (window.dynamicBookingForm) {
        const container = document.getElementById(`guestsContainer_${roomIndex}`);
        if (container) {
            container.innerHTML = window.dynamicBookingForm.renderGuests(roomIndex);
        }
    }
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.dynamicBookingForm = new DynamicBookingForm();

    // Xử lý validation cho tất cả CCCD inputs
    document.addEventListener('input', function(e) {
        if (e.target.name && e.target.name.includes('cccd')) {
            const value = e.target.value;
            if (!/^[0-9]{12}$/.test(value)) {
                e.target.setCustomValidity('CCCD phải gồm 12 số');
            } else {
                e.target.setCustomValidity('');
            }
        }
    });
});
