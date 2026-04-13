// Guest Form JavaScript for Dynamic Guest Inputs by Room
class GuestFormManager {
    constructor() {
        this.init();
    }

    init() {
       console.log('[GuestForm] Initializing...');
        
        // Listen for room selection changes
        this.bindEvents();
        
        // Initial render on page load
        setTimeout(() => {
            console.log('Running initial guest input update...');
            this.updateAllGuestInputs();
            
            // Also try manual trigger after more delay
            setTimeout(() => {
                console.log('Manual trigger after 2 seconds...');
                this.updateAllGuestInputs();
            }, 2000);
        }, 100);
    }

    bindEvents() {
        // Listen for changes in room selection
        document.addEventListener('roomSelectionChanged', () => {
            this.updateAllGuestInputs();
        });

        // Listen for adult count changes in room selection
        document.addEventListener('adultCountChanged', () => {
            this.updateAllGuestInputs();
        });

        // Also listen for DOM changes that might affect room selection
        const observer = new MutationObserver(() => {
            this.updateAllGuestInputs();
        });

        const targetNode = document.getElementById('roomInputsContainer');
        if (targetNode) {
            observer.observe(targetNode, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['value']
            });
        }

        // Listen for adult count changes per room - both change and input events
        document.addEventListener('change', (e) => {
            if (e.target.name && e.target.name.startsWith('adults[')) {
                this.updateRoomGuestInputs(e.target);
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.name && e.target.name.startsWith('adults[')) {
                // Debounce input events
                clearTimeout(this.inputTimeout);
                this.inputTimeout = setTimeout(() => {
                    this.updateRoomGuestInputs(e.target);
                }, 300);
            }
        });

        // Also listen for clicks on room qty selectors
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('room-qty-select')) {
                // Wait a bit for DOM to update
                setTimeout(() => {
                    this.updateAllGuestInputs();
                }, 100);
            }
        });
    }

    updateAllGuestInputs() {
        // Get all room inputs and update each room's guest inputs
        const roomInputs = document.querySelectorAll('[data-room-index]');
        roomInputs.forEach(roomElement => {
            const roomIndex = parseInt(roomElement.dataset.roomIndex);
            this.updateRoomGuestInputs(roomIndex);
        });
    }

    updateRoomGuestInputs(roomIndexOrElement) {
        console.log('updateRoomGuestInputs called with:', roomIndexOrElement);
        
        let roomIndex, roomElement;
        
        if (typeof roomIndexOrElement === 'object') {
            // It's an element, find the room index
            const element = roomIndexOrElement;
            const roomInput = element.closest('[data-room-index]');
            if (roomInput) {
                roomIndex = parseInt(roomInput.dataset.roomIndex);
                roomElement = roomInput;
            }
        } else {
            // It's a room index
            roomIndex = roomIndexOrElement;
            roomElement = document.querySelector(`[data-room-index="${roomIndex}"]`);
        }

        console.log('roomIndex:', roomIndex, 'roomElement:', roomElement);

        if (!roomElement) return;

        const adultCount = this.getRoomAdultCount(roomIndex);
        console.log('adultCount for room', roomIndex, ':', adultCount);
        
        this.renderGuestInputsForRoom(roomIndex, adultCount);
    }

    getRoomAdultCount(roomIndex) {
        // Get adult count for specific room
        const adultInput = document.querySelector(`input[name="adults[${roomIndex}]"]`);
        return adultInput ? parseInt(adultInput.value) || 0 : 0;
    }

    renderGuestInputsForRoom(roomIndex, adultCount) {
        console.log('renderGuestInputsForRoom called:', roomIndex, adultCount);
        
        const container = document.getElementById(`guestInputsContainer-${roomIndex}`);
        const countElement = document.getElementById(`guestCount-${roomIndex}`);
        
        console.log('container found:', !!container, 'countElement found:', !!countElement);
        
        if (!container) {
            console.log('Container not found for room:', roomIndex);
            return;
        }

        // Clear existing inputs
        container.innerHTML = '';

        // Update guest count display
        if (countElement) {
            countElement.textContent = `(${adultCount} khách)`;
        }

        if (adultCount <= 0) {
            container.innerHTML = `
                <div class="text-muted text-center py-2 border rounded bg-light">
                    <small>Vui lòng chọn số lượng người lớn để nhập thông tin</small>
                </div>
            `;
            console.log('No adults, showing placeholder');
            return;
        }

        console.log('Creating inputs for', adultCount, 'guests');
        
        // Create guest inputs for each adult in this room
        for (let i = 1; i <= adultCount; i++) {
            const guestHtml = this.createGuestInputHtml(roomIndex, i);
            container.innerHTML += guestHtml;
            console.log('Added guest input for:', i);
        }

        // Add validation for CCCD inputs
        this.addCccdValidation();
        
        console.log('Guest inputs rendered successfully');
    }

    createGuestInputHtml(roomIndex, guestIndex) {
        const globalGuestIndex = this.getGlobalGuestIndex(roomIndex, guestIndex);
        
        return `
            <div class="guest-input-group mb-3 p-3 border rounded bg-light" data-room-index="${roomIndex}" data-guest-index="${guestIndex}">
                <h6 class="mb-3 fw-bold">
                    <i class="bi bi-person-fill me-2"></i>Người lớn ${guestIndex} - Phòng ${roomIndex + 1}
                </h6>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="small text-muted mb-1">Họ tên <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="guests[${globalGuestIndex}][name]" 
                               class="form-control form-control-sm guest-name" 
                               placeholder="Nhập họ tên người lớn ${guestIndex}"
                               required
                               data-guest-type="adult"
                               data-room-index="${roomIndex}">
                        <div class="invalid-feedback">
                            Vui lòng nhập họ tên
                        </div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="small text-muted mb-1">CCCD (12 số)</label>
                        <input type="text" 
                               name="guests[${globalGuestIndex}][cccd]" 
                               class="form-control form-control-sm guest-cccd" 
                               placeholder="Nhập số CCCD"
                               pattern="[0-9]{12}"
                               maxlength="12"
                               data-guest-type="adult"
                               data-room-index="${roomIndex}">
                        <div class="invalid-feedback">
                            CCCD phải gồm 12 số
                        </div>
                    </div>
                </div>
                <input type="hidden" name="guests[${globalGuestIndex}][type]" value="adult">
                <input type="hidden" name="guests[${globalGuestIndex}][room_index]" value="${roomIndex}">
            </div>
        `;
    }

    getGlobalGuestIndex(roomIndex, guestIndex) {
        // Calculate global guest index based on room and guest position
        let globalIndex = 0;
        
        for (let i = 0; i < roomIndex; i++) {
            const adultCount = this.getRoomAdultCount(i);
            globalIndex += adultCount;
        }
        
        globalIndex += guestIndex - 1; // Convert to 0-based index
        
        return globalIndex;
    }

    addCccdValidation() {
        const cccdInputs = document.querySelectorAll('.guest-cccd');
        
        cccdInputs.forEach(input => {
            // Skip if already has event listener
            if (input.dataset.hasListener) return;
            input.dataset.hasListener = 'true';
            
            input.addEventListener('input', (e) => {
                // Only allow numbers
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Validate length
                if (e.target.value.length > 0 && e.target.value.length !== 12) {
                    e.target.classList.add('is-invalid');
                } else {
                    e.target.classList.remove('is-invalid');
                }
            });

            input.addEventListener('blur', (e) => {
                if (e.target.value.length > 0 && e.target.value.length !== 12) {
                    e.target.classList.add('is-invalid');
                } else {
                    e.target.classList.remove('is-invalid');
                }
            });
        });
    }

    // Public method to trigger guest input update
    refresh() {
        this.updateAllGuestInputs();
    }

    // Method to get all guest data
    getAllGuestData() {
        const guests = [];
        const guestInputs = document.querySelectorAll('[data-guest-index]');
        
        guestInputs.forEach(guestElement => {
            const roomIndex = parseInt(guestElement.dataset.roomIndex);
            const guestIndex = parseInt(guestElement.dataset.guestIndex);
            const nameInput = guestElement.querySelector('input[name$="[name]"]');
            const cccdInput = guestElement.querySelector('input[name$="[cccd]"]');
            
            if (nameInput && nameInput.value.trim()) {
                guests.push({
                    roomIndex: roomIndex,
                    guestIndex: guestIndex,
                    name: nameInput.value.trim(),
                    cccd: cccdInput ? cccdInput.value.trim() : null,
                    type: 'adult'
                });
            }
        });
        
        return guests;
    }
}

// Initialize the guest form manager
document.addEventListener('DOMContentLoaded', function() {
    window.guestFormManager = new GuestFormManager();
});

// Helper function to trigger guest input updates from other scripts
function updateGuestInputs() {
    if (window.guestFormManager) {
        window.guestFormManager.refresh();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GuestFormManager;
}
