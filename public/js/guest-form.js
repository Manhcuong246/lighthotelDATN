// =============================================================
// Guest Form Manager — lưu tạm dữ liệu theo phòng để không mất
// khi form bị render lại.
// Cache: guestData[typeId][roomIndex][guestIndex] = { name, cccd }
// =============================================================
class GuestFormManager {
    constructor() {
        /**
         * Cấu trúc: guestData[typeId][roomIndex][guestIndex] = { name, cccd }
         * Ví dụ: guestData['5']['0']['0'] = { name: 'Nguyễn A', cccd: '123456789012' }
         */
        this.guestData = {};
        this.init();
    }

    init() {
        // Event delegation: lắng nghe input trên toàn trang
        document.addEventListener('input', (e) => {
            const input = e.target;
            if (input.classList.contains('guest-name') || input.classList.contains('guest-cccd')) {
                this._saveInputValue(input);
            }
        });

        // Cũng bắt change để chắc chắn
        document.addEventListener('change', (e) => {
            const input = e.target;
            if (input.classList.contains('guest-name') || input.classList.contains('guest-cccd')) {
                this._saveInputValue(input);
            }
        });

        // Listen for room selection changes → fill lại dữ liệu
        document.addEventListener('roomSelectionChanged', () => {
            setTimeout(() => this._restoreAllInputValues(), 50);
        });

        // Listen for adult count changes → fill lại sau re-render
        document.addEventListener('adultCountChanged', () => {
            setTimeout(() => this._restoreAllInputValues(), 50);
        });

        // MutationObserver: khi guest-inputs-container thay đổi → fill lại
        const rootBody = document.body;
        if (rootBody) {
            const observer = new MutationObserver((mutations) => {
                let hasGuestContainer = false;
                mutations.forEach(m => {
                    m.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            if (node.classList && (
                                node.classList.contains('guest-inputs-container') ||
                                node.classList.contains('guest-input-card') ||
                                node.querySelector && node.querySelector('.guest-input-card')
                            )) {
                                hasGuestContainer = true;
                            }
                        }
                    });
                });
                if (hasGuestContainer) {
                    this._restoreAllInputValues();
                }
            });

            observer.observe(rootBody, {
                childList: true,
                subtree: true
            });
        }

        // Initial restore sau khi DOM ready
        setTimeout(() => this._restoreAllInputValues(), 200);
    }

    /**
     * Lưu giá trị input vào cache guestData
     */
    _saveInputValue(input) {
        const typeId    = input.dataset.typeId;
        const roomIndex = input.dataset.roomIndex;
        const guestIdx  = input.dataset.guestIndex;

        if (typeId === undefined || roomIndex === undefined || guestIdx === undefined) return;

        if (!this.guestData[typeId]) this.guestData[typeId] = {};
        if (!this.guestData[typeId][roomIndex]) this.guestData[typeId][roomIndex] = {};
        if (!this.guestData[typeId][roomIndex][guestIdx]) this.guestData[typeId][roomIndex][guestIdx] = {};

        if (input.classList.contains('guest-name')) {
            this.guestData[typeId][roomIndex][guestIdx].name = input.value;
        } else if (input.classList.contains('guest-cccd')) {
            this.guestData[typeId][roomIndex][guestIdx].cccd = input.value;
        }
    }

    /**
     * Fill lại tất cả guest input hiện tại trên DOM từ guestData
     */
    _restoreAllInputValues() {
        // Name inputs
        document.querySelectorAll('.guest-name').forEach(input => {
            const typeId    = input.dataset.typeId;
            const roomIndex = input.dataset.roomIndex;
            const guestIdx  = input.dataset.guestIndex;
            if (typeId === undefined || roomIndex === undefined || guestIdx === undefined) return;

            const cached = this.guestData?.[typeId]?.[roomIndex]?.[guestIdx];
            if (cached && cached.name !== undefined && input.value === '') {
                input.value = cached.name;
            }
        });

        // CCCD inputs
        document.querySelectorAll('.guest-cccd').forEach(input => {
            const typeId    = input.dataset.typeId;
            const roomIndex = input.dataset.roomIndex;
            const guestIdx  = input.dataset.guestIndex;
            if (typeId === undefined || roomIndex === undefined || guestIdx === undefined) return;

            const cached = this.guestData?.[typeId]?.[roomIndex]?.[guestIdx];
            if (cached && cached.cccd !== undefined && input.value === '') {
                input.value = cached.cccd;
            }
        });
    }

    /**
     * Lưu (snapshot) tất cả giá trị hiện tại trên DOM vào cache
     * Gọi trước khi re-render
     */
    saveAllCurrentValues() {
        document.querySelectorAll('.guest-name, .guest-cccd').forEach(input => {
            if (input.value.trim() !== '') {
                this._saveInputValue(input);
            }
        });
    }

    /**
     * Fill lại ngay sau khi render (kể cả khi value đã có)
     * Khác với _restoreAllInputValues: ghi đè luôn không check empty
     */
    forceRestoreAllValues() {
        document.querySelectorAll('.guest-name').forEach(input => {
            const typeId    = input.dataset.typeId;
            const roomIndex = input.dataset.roomIndex;
            const guestIdx  = input.dataset.guestIndex;
            if (typeId === undefined || roomIndex === undefined || guestIdx === undefined) return;

            const cached = this.guestData?.[typeId]?.[roomIndex]?.[guestIdx];
            if (cached && cached.name !== undefined) {
                input.value = cached.name;
            }
        });

        document.querySelectorAll('.guest-cccd').forEach(input => {
            const typeId    = input.dataset.typeId;
            const roomIndex = input.dataset.roomIndex;
            const guestIdx  = input.dataset.guestIndex;
            if (typeId === undefined || roomIndex === undefined || guestIdx === undefined) return;

            const cached = this.guestData?.[typeId]?.[roomIndex]?.[guestIdx];
            if (cached && cached.cccd !== undefined) {
                input.value = cached.cccd;
            }
        });

        // Gắn lại CCCD validation cho các input mới
        this._addCccdValidation();
    }

    /**
     * Xóa cache cho một room type cụ thể (khi user bỏ chọn phòng hoàn toàn)
     */
    clearRoomType(typeId) {
        if (this.guestData[typeId]) {
            delete this.guestData[typeId];
        }
    }

    /**
     * Xóa cache cho một phòng cụ thể (khi giảm số lượng phòng)
     */
    clearRoom(typeId, roomIndex) {
        if (this.guestData[typeId] && this.guestData[typeId][roomIndex]) {
            delete this.guestData[typeId][roomIndex];
        }
    }

    /**
     * Lấy toàn bộ guest data để submit
     */
    getAllGuestData() {
        return this.guestData;
    }

    /**
     * Serialize guestData thành mảng phẳng để submit form
     * Trả về array: [{ typeId, roomIndex, guestIndex, name, cccd }]
     */
    getFlatGuestList() {
        const list = [];
        Object.keys(this.guestData).forEach(typeId => {
            Object.keys(this.guestData[typeId]).forEach(roomIndex => {
                Object.keys(this.guestData[typeId][roomIndex]).forEach(guestIdx => {
                    const g = this.guestData[typeId][roomIndex][guestIdx];
                    if (g.name || g.cccd) {
                        list.push({
                            typeId: typeId,
                            room_index: parseInt(roomIndex),
                            guest_index: parseInt(guestIdx),
                            name: g.name || '',
                            cccd: g.cccd || '',
                            type: 'adult'
                        });
                    }
                });
            });
        });
        return list;
    }

    /**
     * CCCD: chỉ nhập số, tối đa 12 ký tự
     */
    _addCccdValidation() {
        document.querySelectorAll('.guest-cccd').forEach(input => {
            if (input.dataset.hasListener) return;
            input.dataset.hasListener = 'true';

            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 12);
                this._validateCccd(e.target);
                this._saveInputValue(e.target);
            });

            input.addEventListener('blur', (e) => {
                this._validateCccd(e.target);
            });
        });
    }

    _validateCccd(input) {
        if (input.value.length > 0 && input.value.length !== 12) {
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    }

    /**
     * Render guest forms dynamically based on adult count
     * Called when adult count changes in a room
     */
    renderGuestFormsByAdultCount(roomItem) {
        if (!roomItem) return;

        const adultsInput = roomItem.querySelector('.adults-count');
        const guestInputsContainer = roomItem.querySelector('.guest-inputs-container');
        const typeId = roomItem.dataset.typeId || roomItem.dataset.typeId;
        const roomIndex = roomItem.dataset.roomIndex;

        if (!adultsInput || !guestInputsContainer) return;

        const adultCount = parseInt(adultsInput.value) || 1;
        const currentForms = guestInputsContainer.querySelectorAll('.guest-input-card');
        const currentCount = currentForms.length;

        // Save current values before re-rendering
        this.saveAllCurrentValues();

        if (adultCount > currentCount) {
            // Add more forms
            for (let i = currentCount; i < adultCount; i++) {
                const guestHtml = `
                    <div class="guest-input-card" data-guest-index="${i}">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="input-group-label">Họ tên - Khách hàng ${i + 1}</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-person"></i>
                                    <input type="text"
                                           class="form-control input-with-icon guest-name"
                                           placeholder="Ví dụ: Nguyễn Văn ${String.fromCharCode(65 + i)}"
                                           data-type-id="${typeId}"
                                           data-room-index="${roomIndex}"
                                           data-guest-index="${i}">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="input-group-label">Số CCCD - Khách hàng ${i + 1}</label>
                                <div class="input-icon-wrapper">
                                    <i class="bi bi-card-checklist"></i>
                                    <input type="text"
                                           class="form-control input-with-icon guest-cccd"
                                           placeholder="Nhập 12 số CCCD" maxlength="12" pattern="[0-9]{12}"
                                           data-type-id="${typeId}"
                                           data-room-index="${roomIndex}"
                                           data-guest-index="${i}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                guestInputsContainer.insertAdjacentHTML('beforeend', guestHtml);
            }
            // Add validation for new inputs
            this._addCccdValidation();
        } else if (adultCount < currentCount) {
            // Remove excess forms (from the end)
            for (let i = currentCount - 1; i >= adultCount; i--) {
                if (currentForms[i]) {
                    currentForms[i].remove();
                }
            }
            // Clear cache for removed guests
            if (this.guestData[typeId] && this.guestData[typeId][roomIndex]) {
                for (let i = adultCount; i < currentCount; i++) {
                    delete this.guestData[typeId][roomIndex][i];
                }
            }
        }

        // Restore values for remaining forms
        this.forceRestoreAllValues();
    }

    // --- Compat: các method cũ được giữ để không break code khác ---

    updateAllGuestInputs() {
        this.forceRestoreAllValues();
    }

    updateRoomGuestInputs(roomIndexOrElement) {
        setTimeout(() => this.forceRestoreAllValues(), 50);
    }

    refresh() {
        this.forceRestoreAllValues();
    }
}

// Khởi tạo
document.addEventListener('DOMContentLoaded', function() {
    window.guestFormManager = new GuestFormManager();
});

// Helper cho scripts khác
function updateGuestInputs() {
    if (window.guestFormManager) {
        window.guestFormManager.refresh();
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = GuestFormManager;
}
