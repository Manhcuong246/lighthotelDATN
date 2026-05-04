// Guest edit functionality for check-in modal
document.addEventListener('DOMContentLoaded', function() {
    // Find all check-in modals
    const modals = document.querySelectorAll('[id^="checkinModal"]');

    modals.forEach(modal => {
        const bookingId = modal.id.replace('checkinModal', '');
        const editBtn = document.getElementById(`editGuestsBtn${bookingId}`);

        if (editBtn) {
            editBtn.addEventListener('click', function() {
                toggleEditMode(modal, bookingId);
            });
        }

        // Load guest info when modal is shown
        modal.addEventListener('show.bs.modal', function () {
            loadGuestInfo(modal, bookingId);
        });
    });
});

function loadGuestInfo(modal, bookingId) {
    const url = `/admin/bookings/${bookingId}/guest-info`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Server error:', data.error);
                return;
            }

            // Handle both old and new API format
            let guestList = [];
            if (data.guests_by_room) {
                // New format: flatten guests_by_room into guest list
                data.guests_by_room.forEach(room => {
                    room.guests.forEach(guest => {
                        guest.room_name = room.room_name;
                        guestList.push(guest);
                    });
                });
            } else if (data.guests) {
                // Old format
                guestList = data.guests;
            }

            // Store guest data for editing
            window.currentGuestData = window.currentGuestData || {};
            window.currentGuestData[bookingId] = guestList;

            // Update booking info
            const bookingInfo = data.booking;

            // Update guest list
            const guestListContainer = modal.querySelector('.guest-list-container');
            if (!guestList || guestList.length === 0) {
                guestListContainer.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Chưa có thông tin khách hàng. Vui lòng thêm thông tin khách hàng trước khi check-in.
                    </div>
                `;
                return;
            }

            // Build guest table
            renderGuestTable(modal, bookingId, guestList, data.guests_by_room);
        })
        .catch(error => {
            console.error('Error loading guest info:', error);
            console.error('Error details:', error.message);

            // Show error message to user
            const guestListContainer = modal.querySelector('.guest-list-container');
            if (guestListContainer) {
                guestListContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Lỗi tải dữ liệu:</strong> ${error.message || 'Không thể tải thông tin khách hàng. Vui lòng thử lại.'}
                    </div>
                `;
            }
        });
}

function toggleEditMode(modal, bookingId) {
    const guestListContainer = modal.querySelector('.guest-list-container');
    const isEditMode = guestListContainer.dataset.editMode === 'true';

    if (isEditMode) {
        // Save changes
        saveGuestChanges(modal, bookingId);
    } else {
        // Enter edit mode
        enterEditMode(modal, bookingId);
    }
}

function enterEditMode(modal, bookingId) {
    const guestListContainer = modal.querySelector('.guest-list-container');
    const editBtn = document.getElementById(`editGuestsBtn${bookingId}`);

    if (!window.currentGuestData || !window.currentGuestData[bookingId]) return;

    const guestList = window.currentGuestData[bookingId];

    let editFormHTML = `
        <h6 class="mb-3">
            <i class="bi bi-pencil me-2"></i>Chỉnh sửa thông tin khách hàng
        </h6>
        <div class="row g-3">
    `;

    guestList.forEach((guest, index) => {
        editFormHTML += `
            <div class="col-md-6">
                <label class="form-label small fw-bold">Khách ${index + 1}</label>
                <input type="text"
                       class="form-control mb-2"
                       id="guestName${guest.id}"
                       value="${guest.name}"
                       placeholder="Nhập họ tên">
                <input type="text"
                       class="form-control"
                       id="guestCccd${guest.id}"
                       value="${guest.cccd || ''}"
                       placeholder="Nhập số CCCD">
                <input type="hidden" id="guestType${guest.id}" value="${guest.type}">
            </div>
        `;
    });

    editFormHTML += `
        </div>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            Nhập tên và CCCD chính xác cho tất cả khách hàng.
        </div>
    `;

    guestListContainer.innerHTML = editFormHTML;
    guestListContainer.dataset.editMode = 'true';

    if (editBtn) {
        editBtn.innerHTML = '<i class="bi bi-check me-1"></i>Lưu lại';
        editBtn.classList.remove('btn-light');
        editBtn.classList.add('btn-success');
    }

    // Hide submit button during edit
    const submitBtn = document.getElementById(`checkinSubmitBtn${bookingId}`);
    if (submitBtn) submitBtn.style.display = 'none';
}

function saveGuestChanges(modal, bookingId) {
    if (!window.currentGuestData || !window.currentGuestData[bookingId]) {
        return;
    }

    const guestList = window.currentGuestData[bookingId];
    const guests = [];

    guestList.forEach(guest => {
        const nameInput = document.getElementById(`guestName${guest.id}`);
        const cccdInput = document.getElementById(`guestCccd${guest.id}`);
        const typeInput = document.getElementById(`guestType${guest.id}`);

        if (nameInput && cccdInput && typeInput) {
            guests.push({
                id: guest.id,
                name: nameInput.value.trim(),
                cccd: cccdInput.value.trim(),
                type: typeInput.value
            });
        }
    });

    if (guests.some(g => !g.name)) {
        alert('Vui lòng nhập tên cho tất cả khách hàng!');
        return;
    }

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : document.querySelector('input[name="_token"]')?.value || '';

    // Send update request
    const url = `/admin/bookings/${bookingId}/guest-info`;

    fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ guests: guests })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Lỗi: ' + data.error);
            return;
        }

        // Update stored data and render view mode
        window.currentGuestData[bookingId] = data.guests;
        renderGuestTable(modal, bookingId, data.guests);

        // Reset button
        const editBtn = document.getElementById(`editGuestsBtn${bookingId}`);
        const guestListContainer = modal.querySelector('.guest-list-container');

        if (editBtn) {
            editBtn.innerHTML = '<i class="bi bi-pencil me-1"></i>Sửa thông tin';
            editBtn.classList.remove('btn-success');
            editBtn.classList.add('btn-light');
        }

        guestListContainer.dataset.editMode = 'false';

        // Show submit button again
        const submitBtn = document.getElementById(`checkinSubmitBtn${bookingId}`);
        if (submitBtn) submitBtn.style.display = 'block';

        alert('Cập nhật thông tin khách hàng thành công!');
    })
    .catch(error => {
        console.error('Error saving guest info:', error);
        alert('Có lỗi xảy ra khi lưu thông tin!');
    });
}

function renderGuestTable(modal, bookingId, guestList, guestsByRoom) {
    const guestListContainer = modal.querySelector('.guest-list-container');

    let guestTableHTML = `
        <h6 class="mb-3">
            <i class="bi bi-people-fill me-2"></i>Danh sách khách hàng
        </h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30px;">STT</th>
                        <th style="width: 150px;">Phòng</th>
                        <th>Tên khách hàng</th>
                        <th>Loại</th>
                        <th>CCCD</th>
                        <th style="width: 100px;">Xác nhận</th>
                    </tr>
                </thead>
                <tbody>
    `;

    // Use guestsByRoom if available (new format), otherwise group by room_index
    if (guestsByRoom && guestsByRoom.length > 0) {
        // New format: render by room with actual names
        let stt = 1;
        guestsByRoom.forEach(room => {
            const roomName = room.room_name;
            room.guests.forEach((guest, idx) => {
                guestTableHTML += `
                    <tr>
                        <td>${stt++}</td>
                        <td class="fw-bold text-primary">${idx === 0 ? roomName : ''}</td>
                        <td>
                            <span class="guest-name">${guest.name}</span>
                        </td>
                        <td>
                            <span class="badge bg-${guest.type === 'adult' ? 'primary' : 'info'}">
                                ${guest.type === 'adult' ? 'Người lớn' : 'Trẻ em'}
                            </span>
                        </td>
                        <td>
                            <span class="guest-cccd">${guest.cccd || '-'}</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check">
                                <input class="form-check-input guest-confirmation"
                                       type="checkbox"
                                       id="guestConfirm${guest.id}"
                                       data-guest-id="${guest.id}"
                                       data-guest-name="${guest.name}"
                                       data-guest-cccd="${guest.cccd || ''}"
                                       ${guest.status === 'checked_in' ? 'checked' : ''}>
                                <label class="form-check-label" for="guestConfirm${guest.id}">
                                    Xác nhận
                                </label>
                            </div>
                        </td>
                    </tr>
                `;
            });
        });
    } else {
        // Old format: group by room_index
        const groupedGuests = {};
        guestList.forEach(guest => {
            const roomIdx = guest.room_index || 0;
            if (!groupedGuests[roomIdx]) groupedGuests[roomIdx] = [];
            groupedGuests[roomIdx].push(guest);
        });

        const roomIndices = Object.keys(groupedGuests).sort((a, b) => a - b);

        roomIndices.forEach(roomIdx => {
            const guests = groupedGuests[roomIdx];
            const roomName = guestList.find(g => (g.room_index || 0) == roomIdx)?.room_name || `Phòng ${parseInt(roomIdx) + 1}`;

            guests.forEach((guest, index) => {
                guestTableHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td class="fw-bold text-primary">${index === 0 ? roomName : ''}</td>
                        <td>
                            <span class="guest-name">${guest.name}</span>
                        </td>
                        <td>
                            <span class="badge bg-${guest.type === 'adult' ? 'primary' : 'info'}">
                                ${guest.type === 'adult' ? 'Người lớn' : 'Trẻ em'}
                            </span>
                        </td>
                        <td>
                            <span class="guest-cccd">${guest.cccd || '-'}</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check">
                                <input class="form-check-input guest-confirmation"
                                       type="checkbox"
                                       id="guestConfirm${guest.id}"
                                       data-guest-id="${guest.id}"
                                       data-guest-name="${guest.name}"
                                       data-guest-cccd="${guest.cccd || ''}"
                                       ${guest.status === 'checked_in' ? 'checked' : ''}>
                                <label class="form-check-label" for="guestConfirm${guest.id}">
                                    Xác nhận
                                </label>
                            </div>
                        </td>
                    </tr>
                `;
            });
        });
    }

    guestTableHTML += `
                </tbody>
            </table>
        </div>

        <!-- Tóm tắt xác nhận -->
        <div class="alert alert-light mt-3">
            <div class="row">
                <div class="col-md-4">
                    <strong>Tổng khách:</strong> ${guestList.length}
                </div>
                <div class="col-md-4">
                    <strong>Đã xác nhận:</strong> <span id="confirmedCount${bookingId}">0</span>
                </div>
                <div class="col-md-4">
                    <strong>Cần xác nhận:</strong> <span id="remainingCount${bookingId}">${guestList.length}</span>
                </div>
            </div>
        </div>
    `;

    guestListContainer.innerHTML = guestTableHTML;
    guestListContainer.dataset.editMode = 'false';

    // Re-initialize checkboxes and event listeners
    const confirmCheckboxes = modal.querySelectorAll('.guest-confirmation');
    const confirmedCount = document.getElementById(`confirmedCount${bookingId}`);
    const remainingCount = document.getElementById(`remainingCount${bookingId}`);
    const submitBtn = document.getElementById(`checkinSubmitBtn${bookingId}`);

    function updateConfirmationStatus() {
        if (!confirmCheckboxes.length) return;

        const totalGuests = confirmCheckboxes.length;
        const confirmed = Array.from(confirmCheckboxes).filter(cb => cb.checked).length;
        const remaining = totalGuests - confirmed;

        if (confirmedCount) confirmedCount.textContent = confirmed;
        if (remainingCount) remainingCount.textContent = remaining;

        if (submitBtn) {
            submitBtn.disabled = confirmed < totalGuests;
            if (confirmed >= totalGuests) {
                submitBtn.classList.remove('btn-info');
                submitBtn.classList.add('btn-success');
            } else {
                submitBtn.classList.remove('btn-success');
                submitBtn.classList.add('btn-info');
            }
        }
    }

    confirmCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const guestId = this.dataset.guestId;
            const isChecked = this.checked;
            const url = `/admin/bookings/guests/${guestId}/toggle-status`;

            // Lấy CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')
                ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                : document.querySelector('input[name="_token"]')?.value || '';

            // Disable tạm thời để tránh click liên tục
            this.disabled = true;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                this.disabled = false;
                if (!data.success) {
                    // Revert nếu lỗi
                    this.checked = !isChecked;
                    alert('Lỗi: ' + data.message);
                }
                updateConfirmationStatus();
            })
            .catch(error => {
                this.disabled = false;
                this.checked = !isChecked;
                console.error('Error toggling guest status:', error);
                alert('Có lỗi xảy ra khi lưu trạng thái xác nhận!');
                updateConfirmationStatus();
            });
        });
    });

    // Initialize status
    updateConfirmationStatus();
}
