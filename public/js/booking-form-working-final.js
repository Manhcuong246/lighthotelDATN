// Working Final Booking Form
console.log('WORKING FINAL FORM LOADING');

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM READY');
    
    const roomCountSelect = document.getElementById('roomCount');
    if (roomCountSelect) {
        roomCountSelect.addEventListener('change', function() {
            console.log('Room count changed to:', this.value);
            renderRooms();
        });
        
        // Render immediately if there's a pre-selected value
        if (roomCountSelect.value) {
            console.log('Initial room count:', roomCountSelect.value);
            renderRooms();
        }
    }
});

function renderRooms() {
    console.log('=== RENDER ROOMS ===');
    const roomCount = document.getElementById('roomCount').value;
    const container = document.getElementById('roomsContainer');
    
    console.log('Room count:', roomCount);
    console.log('Container found:', !!container);
    
    if (!container) {
        console.error('Container not found!');
        return;
    }
    
    if (!roomCount || roomCount < 1) {
        container.innerHTML = '<p class="text-muted">Vui lòng chọn số lượng phòng</p>';
        return;
    }

    let html = '<h5 class="mb-3"><i class="bi bi-door-open me-2"></i>Thông tin phòng</h5>';
    
    for (let i = 0; i < parseInt(roomCount); i++) {
        html += createRoomHTML(i);
    }

    container.innerHTML = html;
    console.log('HTML inserted');
    
    // Render guests immediately
    setTimeout(() => {
        for (let i = 0; i < parseInt(roomCount); i++) {
            renderGuests(i);
        }
    }, 100);
}

function createRoomHTML(roomIndex) {
    return `
        <div class="room-block mb-4 p-3 border rounded" data-room-index="${roomIndex}">
            <h6 class="mb-3">
                <i class="bi bi-house-door me-2"></i>
                Phòng ${roomIndex + 1}
            </h6>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label small">Người lớn</label>
                    <input type="number" 
                           id="adults_${roomIndex}" 
                           class="form-control form-control-sm" 
                           value="1" 
                           min="1" 
                           max="10"
                           onchange="window.renderGuests(${roomIndex})">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Trẻ em (0-5)</label>
                    <input type="number" 
                           id="children_0_5_${roomIndex}" 
                           class="form-control form-control-sm" 
                           value="0" 
                           min="0" 
                           max="5"
                           onchange="window.renderGuests(${roomIndex})">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Trẻ em (6-11)</label>
                    <input type="number" 
                           id="children_6_11_${roomIndex}" 
                           class="form-control form-control-sm" 
                           value="0" 
                           min="0" 
                           max="5"
                           onchange="window.renderGuests(${roomIndex})">
                </div>
            </div>

            <div id="guestsContainer_${roomIndex}" class="guests-container">
                Loading guests...
            </div>
        </div>
    `;
}

function renderGuests(roomIndex) {
    console.log('=== RENDER GUESTS FOR ROOM', roomIndex, '===');
    
    const adultsInput = document.getElementById(`adults_${roomIndex}`);
    const container = document.getElementById(`guestsContainer_${roomIndex}`);
    
    console.log('Adults input:', !!adultsInput);
    console.log('Container:', !!container);
    
    if (!adultsInput || !container) {
        console.error('Missing elements for room', roomIndex);
        return;
    }

    const adults = parseInt(adultsInput.value) || 0;
    console.log('Adult count:', adults);
    
    if (adults <= 0) {
        container.innerHTML = '<div class="text-muted text-center py-2 bg-light rounded"><small>Vui lòng chọn số lượng người lớn</small></div>';
        return;
    }

    let html = '<h6 class="mb-3"><i class="bi bi-people-fill me-2"></i>Thông tin khách hàng</h6>';
    
    for (let i = 0; i < adults; i++) {
        html += createGuestHTML(roomIndex, i);
    }

    container.innerHTML = html;
    console.log('Guests rendered for room', roomIndex);
}

function createGuestHTML(roomIndex, guestIndex) {
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
                           placeholder="Nhập họ tên"
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

// Make functions global
window.renderRooms = renderRooms;
window.renderGuests = renderGuests;

console.log('WORKING FINAL FORM READY');
