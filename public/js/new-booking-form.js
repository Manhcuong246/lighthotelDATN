// JavaScript cho form đặt phòng với dynamic guest inputs
class NewBookingFormManager {
    constructor() {
        this.guestCounts = {}; // Lưu số lượng khách cho từng phòng
        this.guestData = {}; // Lưu thông tin khách cho từng phòng
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeAllRooms();
    }

    bindEvents() {
        // Xử lý khi click nút thêm/xóa khách
        document.addEventListener('click', (e) => {
            if (e.target.matches('[onclick*="addGuest"]')) {
                e.preventDefault();
                const roomIndex = parseInt(e.target.getAttribute('onclick').match(/addGuest\((\d+)\)/)[1]);
                this.addGuest(roomIndex);
            }

            if (e.target.matches('[onclick*="removeGuest"]')) {
                e.preventDefault();
                const roomIndex = parseInt(e.target.getAttribute('onclick').match(/removeGuest\((\d+)\)/)[1]);
                this.removeGuest(roomIndex);
            }
        });

        // Xử lý khi thay đổi số lượng người
        document.addEventListener('change', (e) => {
            if (e.target.name && (e.target.name.includes('adults') || e.target.name.includes('children'))) {
                // Tìm room index từ tên input (ví dụ: adults[0], children_0_5[0])
                const match = e.target.name.match(/(\w+)\[(\d+)\]/);
                if (match) {
                    const roomIndex = parseInt(match[2]);
                    this.updateGuestCountForRoom(roomIndex);
                }
            }
        });
    }

    initializeAllRooms() {
        // Tìm tất cả các phòng trong form và khởi tạo guest forms
        const adultInputs = document.querySelectorAll('input[name^="adults["]');

        adultInputs.forEach(input => {
            const match = input.name.match(/adults\[(\d+)\]/);
            if (match) {
                const roomIndex = parseInt(match[1]);
                this.updateGuestCountForRoom(roomIndex);
            }
        });
    }

    addGuest(roomIndex) {
        if (!this.guestCounts[roomIndex]) {
            this.guestCounts[roomIndex] = 0;
        }

        this.guestCounts[roomIndex]++;
        this.renderGuestInputs(roomIndex);
    }

    updateGuestCountForRoom(roomIndex) {
        // Lấy số lượng người lớn từ form
        const adultsInput = document.querySelector(`input[name="adults[${roomIndex}]"]`);
        const children05Input = document.querySelector(`[name="children_0_5[${roomIndex}]"]`);
        const children611Input = document.querySelector(`input[name="children_6_11[${roomIndex}]"]`);

        if (adultsInput) {
            const adults = parseInt(adultsInput.value) || 0;
            const children05 = parseInt(children05Input?.value) || 0;
            const children611 = parseInt(children611Input?.value) || 0;

            // Tổng số khách = số người lớn (vì trẻ em không cần thông tin riêng)
            const totalGuests = adults;

            this.guestCounts[roomIndex] = totalGuests;
            this.renderGuestInputs(roomIndex);
        }
    }

    removeGuest(roomIndex) {
        if (!this.guestCounts[roomIndex] || this.guestCounts[roomIndex] <= 0) {
            return;
        }

        this.guestCounts[roomIndex]--;
        this.renderGuestInputs(roomIndex);
    }

    renderGuestInputs(roomIndex) {
        const container = document.getElementById(`guestInputsContainer-${roomIndex}`);
        if (!container) {
            console.error(`Container not found for room ${roomIndex}`);
            return;
        }

        // Lưu thông tin khách hiện tại trước khi render lại
        this.saveCurrentGuestData(roomIndex);

        const guestCount = this.guestCounts[roomIndex] || 0;

        if (guestCount <= 0) {
            container.innerHTML = `
                <div class="text-muted text-center py-2 bg-light rounded">
                    <small>Vui lòng thêm khách cho phòng này</small>
                </div>
            `;
            return;
        }

        let html = '';
        for (let i = 0; i < guestCount; i++) {
            html += this.createGuestInputHtml(roomIndex, i);
        }

        container.innerHTML = html;

        // Khôi phục thông tin khách đã lưu
        this.restoreGuestData(roomIndex);
    }

    createGuestInputHtml(roomIndex, guestIndex) {
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
                               class="form-control form-control-sm guest-name-input"
                               data-room-index="${roomIndex}"
                               data-guest-index="${guestIndex}"
                               data-field="name"
                               placeholder="Nhập họ tên người lớn ${guestIndex + 1}"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">CCCD (12 số) <span class="text-danger">*</span></label>
                        <input type="text"
                               name="rooms[${roomIndex}][guests][${guestIndex}][cccd]"
                               class="form-control form-control-sm guest-cccd-input"
                               data-room-index="${roomIndex}"
                               data-guest-index="${guestIndex}"
                               data-field="cccd"
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

    saveCurrentGuestData(roomIndex) {
        const guestInputs = document.querySelectorAll(`#guestInputsContainer-${roomIndex} input`);
        const roomData = this.guestData[roomIndex] || {};

        guestInputs.forEach(input => {
            const guestIndex = input.dataset.guestIndex;
            if (guestIndex !== undefined) {
                if (!roomData[guestIndex]) {
                    roomData[guestIndex] = {};
                }
                roomData[guestIndex][input.name.includes('name') ? 'name' : 'cccd'] = input.value;
            }
        });

        this.guestData[roomIndex] = roomData;
    }

    restoreGuestData(roomIndex) {
        const roomData = this.guestData[roomIndex];
        if (!roomData) {
            return;
        }

        Object.keys(roomData).forEach(guestIndex => {
            const guestData = roomData[guestIndex];

            const nameInput = document.querySelector(`#guestInputsContainer-${roomIndex} input[data-guest-index="${guestIndex}"][data-field="name"]`);
            const cccdInput = document.querySelector(`#guestInputsContainer-${roomIndex} input[data-guest-index="${guestIndex}"][data-field="cccd"]`);

            if (nameInput && guestData.name) {
                nameInput.value = guestData.name;
            }

            if (cccdInput && guestData.cccd) {
                cccdInput.value = guestData.cccd;
            }
        });
    }

    // Validate CCCD input
    validateCccd(input) {
        input.addEventListener('input', function() {
            const value = this.value;
            if (!/^[0-9]{12}$/.test(value)) {
                this.setCustomValidity('CCCD phải gồm 12 số');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    new NewBookingFormManager();

    // Xử lý validation cho tất cả CCCD inputs
    document.querySelectorAll('input[name*="cccd"]').forEach(input => {
        input.addEventListener('input', function() {
            const value = this.value;
            if (!/^[0-9]{12}$/.test(value)) {
                this.setCustomValidity('CCCD phải gồm 12 số');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    // Form validation trước khi submit
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {

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
        });
    }
});
