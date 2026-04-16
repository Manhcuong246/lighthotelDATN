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
    console.log('Fetching guest info from:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            if (data.error) {
                console.error('Server error:', data.error);
                return;
            }
            
            // Store guest data for editing
            window.currentGuestData = window.currentGuestData || {};
            window.currentGuestData[bookingId] = data.guests;
            
            // Update booking info
            const bookingInfo = data.booking;
            const guestList = data.guests;
            
            // Update guest list
            const guestListContainer = modal.querySelector('.guest-list-container');
            if (guestList.length === 0) {
                guestListContainer.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Chua co thông tin khách hàng. Vui lòng thêm thông tin khách hàng truoc khi check-in.
                    </div>
                `;
                return;
            }
            
            // Build guest table
            renderGuestTable(modal, bookingId, guestList);
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
                        <strong>Lôi tai dô liêu:</strong> ${error.message || 'Không thê tai thông tin khách hàng. Vui lòng thû lai.'}
                        <br><small>Vui lòng kiêm tra console de biêt chi tiêt lôi.</small>
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
            <i class="bi bi-pencil me-2"></i>Chinh sua thông tin khách hàng
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
                       placeholder="Nhâp ho tên">
                <input type="text" 
                       class="form-control" 
                       id="guestCccd${guest.id}" 
                       value="${guest.cccd || ''}" 
                       placeholder="Nhâp sô CCCD">
                <input type="hidden" id="guestType${guest.id}" value="${guest.type}">
            </div>
        `;
    });
    
    editFormHTML += `
        </div>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            Nhâp tên và CCCD chính xác cho tât ca khách hàng.
        </div>
    `;
    
    guestListContainer.innerHTML = editFormHTML;
    guestListContainer.dataset.editMode = 'true';
    
    if (editBtn) {
        editBtn.innerHTML = '<i class="bi bi-check me-1"></i>Luu lai';
        editBtn.classList.remove('btn-light');
        editBtn.classList.add('btn-success');
    }
    
    // Hide submit button during edit
    const submitBtn = document.getElementById(`checkinSubmitBtn${bookingId}`);
    if (submitBtn) submitBtn.style.display = 'none';
}

function saveGuestChanges(modal, bookingId) {
    console.log('saveGuestChanges called for booking', bookingId);
    
    if (!window.currentGuestData || !window.currentGuestData[bookingId]) {
        console.error('No guest data found for booking', bookingId);
        return;
    }
    
    const guestList = window.currentGuestData[bookingId];
    const guests = [];
    
    console.log('Processing guests:', guestList);
    
    guestList.forEach(guest => {
        const nameInput = document.getElementById(`guestName${guest.id}`);
        const cccdInput = document.getElementById(`guestCccd${guest.id}`);
        const typeInput = document.getElementById(`guestType${guest.id}`);
        
        console.log(`Guest ${guest.id}:`, {
            nameInput: nameInput?.value,
            cccdInput: cccdInput?.value,
            typeInput: typeInput?.value
        });
        
        if (nameInput && cccdInput && typeInput) {
            guests.push({
                id: guest.id,
                name: nameInput.value.trim(),
                cccd: cccdInput.value.trim(),
                type: typeInput.value
            });
        }
    });
    
    console.log('Prepared guests data:', guests);
    
    if (guests.some(g => !g.name)) {
        alert('Vui lòng nhâp tên cho tât ca khách hàng!');
        return;
    }
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]') 
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : document.querySelector('input[name="_token"]')?.value || '';

    // Send update request
    const url = `/admin/bookings/${bookingId}/guest-info`;
    console.log('Sending PUT request to:', url);
    console.log('CSRF Token:', csrfToken);
    console.log('Request body:', JSON.stringify({ guests: guests }));
    
    fetch(url, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ guests: guests })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.error) {
            alert('Lôi: ' + data.error);
            return;
        }
        
        // Update stored data and render view mode
        window.currentGuestData[bookingId] = data.guests;
        renderGuestTable(modal, bookingId, data.guests);
        
        // Reset button
        const editBtn = document.getElementById(`editGuestsBtn${bookingId}`);
        const guestListContainer = modal.querySelector('.guest-list-container');
        
        if (editBtn) {
            editBtn.innerHTML = '<i class="bi bi-pencil me-1"></i>Sua thông tin';
            editBtn.classList.remove('btn-success');
            editBtn.classList.add('btn-light');
        }
        
        guestListContainer.dataset.editMode = 'false';
        
        // Show submit button again
        const submitBtn = document.getElementById(`checkinSubmitBtn${bookingId}`);
        if (submitBtn) submitBtn.style.display = 'block';
        
        alert('Câp nhât thông tin khách hàng thành công!');
    })
    .catch(error => {
        console.error('Error saving guest info:', error);
        alert('Có lôi xãy ra khi luu thông tin!');
    });
}

function renderGuestTable(modal, bookingId, guestList) {
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
                        <th>Tên khách hàng</th>
                        <th>Loai</th>
                        <th>CCCD</th>
                        <th style="width: 100px;">Xác nhân</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    guestList.forEach((guest, index) => {
        guestTableHTML += `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <span class="guest-name">${guest.name}</span>
                </td>
                <td>
                    <span class="badge bg-${guest.type === 'adult' ? 'primary' : 'info'}">
                        ${guest.type === 'adult' ? 'Nguyên lôn' : 'Trê em'}
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
                               data-guest-cccd="${guest.cccd || ''}">
                        <label class="form-check-label" for="guestConfirm${guest.id}">
                            Xác nhân
                        </label>
                    </div>
                </td>
            </tr>
        `;
    });
    
    guestTableHTML += `
                </tbody>
            </table>
        </div>
        
        <!-- Tom tát xác nhân -->
        <div class="alert alert-light mt-3">
            <div class="row">
                <div class="col-md-4">
                    <strong>Tông khách:</strong> ${guestList.length}
                </div>
                <div class="col-md-4">
                    <strong>Da xác nhân:</strong> <span id="confirmedCount${bookingId}">0</span>
                </div>
                <div class="col-md-4">
                    <strong>Cân xác nhân:</strong> <span id="remainingCount${bookingId}">${guestList.length}</span>
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
        checkbox.addEventListener('change', updateConfirmationStatus);
    });
    
    // Initialize status
    updateConfirmationStatus();
}
