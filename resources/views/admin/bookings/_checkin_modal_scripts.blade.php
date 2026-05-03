{{-- Scripts check-in — nạp một lần duy nhất cho cả trang --}}
<script>
(function () {
    if (window.__adminCheckinScriptsBound) return;
    window.__adminCheckinScriptsBound = true;

    window.__checkInMeta = {};

    /* ── Tiện ích ──────────────────────────────────────────────────── */
    function esc(text) {
        const d = document.createElement('div');
        d.textContent = text == null ? '' : String(text);
        return d.innerHTML;
    }

    function csrfToken() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    /* ── Tải dữ liệu từ API và render các slot phòng ──────────────── */
    window.loadGuestsForBooking = function (bookingId) {
        const container = document.getElementById('roomSlotsContainer' + bookingId);
        if (!container) return;

        container.innerHTML =
            '<div class="text-center py-4 text-muted">' +
            '<div class="spinner-border spinner-border-sm me-2"></div>Đang tải...</div>';

        fetch('/admin/bookings/' + bookingId + '/checkin-data', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        })
        .then(function (result) {
            const data = result.data || {};

            if (!result.ok || data.error) {
                container.innerHTML =
                    '<div class="alert alert-danger">' + esc(data.error || 'Lỗi tải dữ liệu') + '</div>';
                return;
            }

            window.__checkInMeta[bookingId] = { booking_rooms: data.booking_rooms || [] };

            if (data.booking) {
                applyCheckinSummary(bookingId, data.booking);
            }

            container.innerHTML = '';

            const bookingRooms = data.booking_rooms || [];
            const guests       = data.guests || [];

            if (bookingRooms.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">Đơn chưa có thông tin phòng.</div>';
                return;
            }

            // Nhóm khách theo booking_room_id
            const guestsBySlot = {};
            bookingRooms.forEach(function (br) { guestsBySlot[br.id] = []; });
            guests.forEach(function (g) {
                const key = g.booking_room_id;
                if (key != null && guestsBySlot[key] !== undefined) {
                    guestsBySlot[key].push(g);
                } else {
                    // Chưa gán slot → đưa về slot đầu tiên
                    guestsBySlot[bookingRooms[0].id].push(g);
                }
            });

            bookingRooms.forEach(function (br, idx) {
                container.appendChild(buildSlotSection(bookingId, br, guestsBySlot[br.id] || [], idx));
            });
        })
        .catch(function () {
            container.innerHTML = '<div class="alert alert-danger">Lỗi kết nối. Hãy đóng và mở lại modal.</div>';
        });
    };

    /* ── Render: một card phòng (chỉ loại phòng ở tiêu đề; số phòng do admin chọn) ─ */
    function buildSlotSection(bookingId, br, slotGuests, idx) {
        const section   = document.createElement('div');
        const tbodyId   = 'guestSlotBody_' + bookingId + '_' + br.id;
        const typeName  = br.room_type_name || 'Phòng đã đặt';

        const adults = br.adults || 0;
        const ch611  = br.children_6_11 || 0;
        const ch05   = br.children_0_5  || 0;

        const capParts = [];
        if (adults) capParts.push(adults + ' người lớn');
        if (ch611)  capParts.push(ch611  + ' trẻ 6–11');
        if (ch05)   capParts.push(ch05   + ' trẻ 0–5');
        const capText = capParts.length
            ? ' <span class="text-muted small fw-normal">(' + capParts.join(', ') + ')</span>'
            : '';

        const opts = br.room_options || [];
        const noOpts = opts.length === 0;
        let optionsHtml = '<option value="">-- Chọn số phòng --</option>';
        if (!noOpts) {
            opts.forEach(function (opt) {
                const rid = parseInt(opt.room_id, 10);
                optionsHtml += '<option value="' + rid + '">' + esc(opt.label) + '</option>';
            });
        }

        section.className = 'mb-3 border rounded p-3' + (idx > 0 ? ' mt-2' : '');
        section.dataset.slotId = String(br.id);
        section.setAttribute('data-checkin-slot', '1');

        section.innerHTML =
            '<div class="row g-2 align-items-start mb-2">'
            + '<div class="col">'
            + '<h6 class="mb-2">'
            + '<span class="badge bg-secondary me-2">' + (idx + 1) + '</span>'
            + esc(typeName) + capText
            + '</h6>'
            + '<label class="form-label small mb-0">Phòng cụ thể <span class="text-danger">*</span></label>'
            + '<select class="form-select form-select-sm slot-room-select mt-1" name="slot_room_id[' + br.id + ']" required '
            + (noOpts ? 'disabled' : '') + ' style="max-width:380px">'
            + optionsHtml
            + '</select>'
            + (noOpts ? '<div class="small text-danger mt-1">Không có phòng cùng loại khả dụng hoặc thiếu dữ liệu phòng.</div>' : '')
            + '</div>'
            + '<div class="col-auto">'
            + '<label class="form-label small mb-0 d-block text-end text-muted">&nbsp;</label>'
            + '<button type="button" class="btn btn-sm btn-success btn-add-guest-slot mt-1" '
            + 'data-booking-id="' + bookingId + '" data-slot-id="' + br.id + '">'
            + '<i class="bi bi-plus-circle me-1"></i>Thêm khách</button>'
            + '</div>'
            + '</div>'
            + '<div class="table-responsive">'
            + '<table class="table table-bordered table-sm mb-0">'
            + '<thead class="table-light">'
            + '<tr>'
            + '<th style="width:44px" class="text-center">STT</th>'
            + '<th>Họ tên <span class="text-danger">*</span></th>'
            + '<th style="width:168px">CCCD<br><small class="fw-normal text-muted">1 người lớn/phòng: 12 số (đại diện)</small></th>'
            + '<th style="width:168px">Loại khách <span class="text-danger">*</span></th>'
            + '<th style="width:52px"></th>'
            + '</tr></thead>'
            + '<tbody id="' + tbodyId + '"></tbody>'
            + '</table></div>';

        const addBtn = section.querySelector('.btn-add-guest-slot');
        const sel    = section.querySelector('.slot-room-select');
        function syncAddState() {
            addBtn.disabled = noOpts || !sel.value || sel.disabled;
        }
        sel.addEventListener('change', syncAddState);
        addBtn.addEventListener('click', function () {
            addNewGuestToSlot(bookingId, br.id);
        });
        syncAddState();

        const tbody = section.querySelector('#' + tbodyId);

        if (slotGuests.length > 0) {
            slotGuests.forEach(function (g, i) {
                tbody.appendChild(buildGuestRow(bookingId, br.id, g, i + 1));
            });
        } else {
            tbody.innerHTML = emptySlotRow();
        }

        return section;
    }

    function emptySlotRow() {
        return '<tr class="no-guests-row">'
            + '<td colspan="5" class="text-center text-muted py-3 fst-italic">'
            + 'Chưa có khách — nhấn «Thêm khách» để bổ sung.'
            + '</td></tr>';
    }

    /* ── Thêm khách mới vào một slot ──────────────────────────────── */
    window.addNewGuestToSlot = function (bookingId, slotId) {
        const tbody = document.getElementById('guestSlotBody_' + bookingId + '_' + slotId);
        if (!tbody) return;
        const noRow = tbody.querySelector('.no-guests-row');
        if (noRow) noRow.remove();
        const count = tbody.querySelectorAll('.guest-row').length;
        tbody.appendChild(buildGuestRow(bookingId, slotId, null, count + 1));
    };

    function guestRowIsAdult(row) {
        const sel = row.querySelector('.guest-type-input');
        return !!(sel && sel.value === 'adult');
    }

    /** Mỗi phòng cần ≥1 người lớn có CCCD 12 số (kiểm tra lúc gửi form). */
    function syncGuestCccdRequirement(row) {
        const cccdInput = row.querySelector('.guest-cccd-input');
        const typeSel = row.querySelector('.guest-type-input');
        if (!cccdInput || !typeSel || cccdInput.readOnly) return;
        cccdInput.removeAttribute('required');
        cccdInput.placeholder = guestRowIsAdult(row)
            ? 'Một NL trong phòng bắt buộc 12 số'
            : 'Tuỳ chọn (ưu tiên NL có CCCD)';
    }

    /* ── Tạo một hàng khách ────────────────────────────────────────── */
    function buildGuestRow(bookingId, slotId, guestData, stt) {
        const template = document.getElementById('guestRowTemplate' + bookingId);
        const clone    = template.content.cloneNode(true);
        const row      = clone.querySelector('tr');
        const index    = Date.now() + '_' + stt;

        row.dataset.slotId = slotId;

        const nameInput  = row.querySelector('.guest-name-input');
        const cccdInput  = row.querySelector('.guest-cccd-input');
        const typeInput  = row.querySelector('.guest-type-input');
        const idInput    = row.querySelector('.guest-id-input');
        const brimInput  = row.querySelector('.guest-booking-room-id-input');
        const removeBtn  = row.querySelector('.btn-remove-guest');

        nameInput.name  = 'guests[' + index + '][name]';
        cccdInput.name  = 'guests[' + index + '][cccd]';
        typeInput.name  = 'guests[' + index + '][type]';
        idInput.name    = 'guests[' + index + '][id]';
        brimInput.name  = 'guests[' + index + '][booking_room_id]';
        brimInput.value = slotId;

        row.querySelector('.guest-stt').textContent = stt;

        // Chỉ cho nhập số, tối đa 12 ký tự
        cccdInput.addEventListener('input',  function () { this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12); });
        cccdInput.addEventListener('paste',  function (e) { e.preventDefault(); });
        cccdInput.addEventListener('drop',   function (e) { e.preventDefault(); });

        removeBtn.addEventListener('click', function () { removeGuestRow(row, bookingId); });
        typeInput.addEventListener('change', function () { syncGuestCccdRequirement(row); });

        if (guestData) {
            row.dataset.guestId         = guestData.id || '';
            row.dataset.isNew           = 'false';

            nameInput.value = guestData.name || '';
            cccdInput.value = guestData.cccd || '';
            var gt = guestData.type || 'adult';
            if (gt === 'child') {
                gt = 'child_0_5';
            }
            typeInput.value = gt;
            idInput.value   = guestData.id   || '';

            if (guestData.status === 'checked_in') {
                row.classList.add('table-success');
                nameInput.readOnly  = true;
                cccdInput.readOnly  = true;
                typeInput.disabled  = true;
                removeBtn.disabled  = true;
                removeBtn.classList.replace('btn-danger', 'btn-secondary');
            }
        }

        syncGuestCccdRequirement(row);

        return row;
    }

    /* ── Xóa hàng khách ───────────────────────────────────────────── */
    function removeGuestRow(row, bookingId) {
        const tbody   = row.closest('tbody');
        const guestId = row.dataset.guestId;

        if (guestId && row.dataset.isNew === 'false') {
            if (!confirm('Xóa khách này khỏi đơn?')) return;
            fetch('/admin/booking-guests/' + guestId, {
                method: 'DELETE',
                headers: {
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken(),
                },
                credentials: 'same-origin',
            })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function (data) {
                if (data.error) { alert(data.error); return; }
                row.remove();
                renumberSlot(tbody);
            })
            .catch(function () { alert('Không xóa được khách. Thử lại.'); });
        } else {
            row.remove();
            renumberSlot(tbody);
        }
    }

    /* ── Đánh lại STT trong một slot ──────────────────────────────── */
    function renumberSlot(tbody) {
        const rows = tbody.querySelectorAll('.guest-row');
        if (rows.length === 0) {
            tbody.innerHTML = emptySlotRow();
            return;
        }
        rows.forEach(function (r, i) {
            r.querySelector('.guest-stt').textContent = i + 1;
        });
    }

    /* ── Cập nhật summary từ API payload ──────────────────────────── */
    function applyCheckinSummary(bookingId, payload) {
        const modal = document.getElementById('checkinModal' + bookingId);
        if (!modal) return;
        const box = modal.querySelector('[data-checkin-summary]');
        if (!box)  return;

        const u   = payload.user           || {};
        const rep = payload.representative || {};

        const nameEl = box.querySelector('[data-summary-name]');
        const cccdEl = box.querySelector('[data-summary-cccd]');
        const ciEl   = box.querySelector('[data-summary-checkin]');
        const coEl   = box.querySelector('[data-summary-checkout]');

        if (nameEl) nameEl.textContent = rep.full_name || u.full_name || u.name || '—';
        if (cccdEl) {
            const c = rep.cccd && String(rep.cccd).trim() !== '' ? String(rep.cccd).trim() : null;
            cccdEl.textContent = c || '—';
        }
        if (ciEl && payload.check_in)  ciEl.textContent = payload.check_in;
        if (coEl && payload.check_out) coEl.textContent = payload.check_out;
    }

    // Alias cũ — giữ để backward compat
    window.applyCheckInSummaryFromPayload = function (bookingId, p) { applyCheckinSummary(bookingId, p); };

    /* ── Sự kiện: mở modal → tải dữ liệu ─────────────────────────── */
    document.body.addEventListener('show.bs.modal', function (ev) {
        const el = ev.target;
        if (!(el instanceof HTMLElement) || !el.id || !/^checkinModal\d+$/.test(el.id)) return;
        const bookingId = parseInt(el.id.replace('checkinModal', ''), 10);
        if (bookingId) window.loadGuestsForBooking(bookingId);
    });

    /* ── Sự kiện: submit form → validate trước khi gửi ───────────── */
    document.body.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-admin-checkin-form')) return;

        const bookingId = form.getAttribute('data-booking-id');
        const container = document.getElementById('roomSlotsContainer' + bookingId);
        const allRows   = container ? Array.from(container.querySelectorAll('.guest-row')) : [];
        const errors    = [];

        if (allRows.length === 0) {
            errors.push('Vui lòng thêm ít nhất 1 khách');
        }

        let slotPickMissing = false;
        const slotSections = container ? container.querySelectorAll('[data-checkin-slot]') : [];
        slotSections.forEach(function (sec) {
            const sel = sec.querySelector('.slot-room-select');
            if (!sel) return;
            if (sel.disabled || !sel.value) {
                slotPickMissing = true;
                sel.classList.add('is-invalid');
            } else {
                sel.classList.remove('is-invalid');
            }
        });
        if (slotPickMissing) {
            errors.push('Vui lòng chọn phòng cụ thể cho mỗi dòng đặt trước khi xác nhận check-in.');
        }

        allRows.forEach(function (row, idx) {
            const name = row.querySelector('.guest-name-input').value.trim();
            const cccdRaw = row.querySelector('.guest-cccd-input').value.replace(/\D/g, '');
            const cccdEl = row.querySelector('.guest-cccd-input');
            const label = 'Khách ' + (idx + 1);
            const adult = guestRowIsAdult(row);

            if (!name) {
                errors.push(label + ': Thiếu họ tên');
                row.querySelector('.guest-name-input').classList.add('is-invalid');
            } else {
                row.querySelector('.guest-name-input').classList.remove('is-invalid');
            }

            if (adult) {
                if (cccdRaw && !/^\d{12}$/.test(cccdRaw)) {
                    errors.push(label + ': CCCD phải đúng 12 chữ số (hoặc để trống)');
                    cccdEl.classList.add('is-invalid');
                } else {
                    cccdEl.classList.remove('is-invalid');
                }
            } else if (cccdRaw && !/^\d{12}$/.test(cccdRaw)) {
                errors.push(label + ': Trẻ em — nếu nhập CCCD phải đúng 12 số (hoặc để trống)');
                cccdEl.classList.add('is-invalid');
            } else {
                cccdEl.classList.remove('is-invalid');
            }
        });

        slotSections.forEach(function (sec) {
            const rows = Array.from(sec.querySelectorAll('.guest-row'));
            let adultWithFullCccd = 0;
            let named = 0;
            rows.forEach(function (row) {
                const name = row.querySelector('.guest-name-input').value.trim();
                if (!name) {
                    return;
                }
                named++;
                if (!guestRowIsAdult(row)) {
                    return;
                }
                const cccdRaw = row.querySelector('.guest-cccd-input').value.replace(/\D/g, '');
                if (/^\d{12}$/.test(cccdRaw)) {
                    adultWithFullCccd++;
                }
            });
            const hn = sec.querySelector('h6');
            const slotTitle = hn ? hn.textContent.replace(/\s+/g, ' ').trim() : 'Một dòng phòng trên đơn';
            if (named === 0) {
                errors.push(slotTitle + ': Cần ít nhất 1 khách có họ tên.');
            }
            if (adultWithFullCccd < 1) {
                errors.push(slotTitle + ': Mỗi phòng cần ít nhất 1 người lớn có CCCD đủ 12 số (người đại diện; một người đủ nếu có cả hai).');
            }
        });

        const errDiv  = document.getElementById('validationErrors' + bookingId);
        const errList = document.getElementById('errorList' + bookingId);

        if (errors.length > 0) {
            e.preventDefault();
            errList.innerHTML = errors.map(function (err) { return '<li>' + esc(err) + '</li>'; }).join('');
            errDiv.classList.remove('d-none');
            return;
        }

        errDiv.classList.add('d-none');

        // Bật lại các field bị disabled (khách đã check-in) để submit
        if (container) {
            container.querySelectorAll('.guest-row select[disabled], .guest-row input[disabled]')
                .forEach(function (el) { el.disabled = false; });
        }
    });
})();
</script>
