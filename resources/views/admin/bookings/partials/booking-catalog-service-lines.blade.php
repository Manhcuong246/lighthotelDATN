@include('admin.bookings.partials.extras-form-styles')
@if(! empty($catalogNotice))
    <p class="small text-secondary mb-2">{{ $catalogNotice }}</p>
@endif
<div class="js-booking-svc-wrap">
    <div class="js-booking-svc-rows abs-extras-rows">
        <div class="js-booking-svc-row abs-extras-row">
            <div class="d-flex flex-wrap gap-2 gap-md-3 align-items-end">
                <div class="flex-grow-1" style="min-width: min(100%, 220px);">
                    <label class="abs-extras-label">Dịch vụ <span class="fw-normal text-secondary">(tuỳ chọn)</span></label>
                    <select name="svc_items[0][service_id]" class="form-select form-select-sm">
                        <option value="">— Chọn dịch vụ —</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}">{{ $svc->name }} — {{ number_format((float) $svc->price, 0, ',', '.') }} ₫</option>
                        @endforeach
                    </select>
                </div>
                <div style="width: 5.5rem; flex-shrink: 0;">
                    <label class="abs-extras-label">SL</label>
                    <input type="number" name="svc_items[0][quantity]" value="1" min="1" max="9999" class="form-control form-control-sm text-center">
                </div>
                <div class="ms-md-auto pb-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 js-booking-svc-remove" hidden title="Xóa dòng" aria-label="Xóa dòng">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="abs-extras-add js-booking-svc-add mt-2">
        <i class="bi bi-plus-circle"></i>
        <span>Thêm dòng</span>
    </button>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function reindexSvcRows(wrap) {
                    var rows = wrap.querySelectorAll('.js-booking-svc-row');
                    rows.forEach(function (row, i) {
                        row.querySelectorAll('select[name], input[name]').forEach(function (el) {
                            if (el.name) {
                                el.name = el.name.replace(/svc_items\[\d+]/, 'svc_items[' + i + ']');
                            }
                        });
                    });
                    var multi = rows.length > 1;
                    rows.forEach(function (row) {
                        var btn = row.querySelector('.js-booking-svc-remove');
                        if (btn) btn.hidden = !multi;
                    });
                }

                document.querySelectorAll('.js-booking-svc-wrap').forEach(function (container) {
                    var wrap = container.querySelector('.js-booking-svc-rows');
                    if (!wrap) return;

                    container.querySelector('.js-booking-svc-add')?.addEventListener('click', function () {
                        var first = wrap.querySelector('.js-booking-svc-row');
                        var clone = first.cloneNode(true);
                        clone.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
                        clone.querySelectorAll('input[type="number"]').forEach(function (el) { el.value = '1'; });
                        wrap.appendChild(clone);
                        reindexSvcRows(wrap);
                    });

                    wrap.addEventListener('click', function (e) {
                        var btn = e.target.closest('.js-booking-svc-remove');
                        if (!btn || btn.hidden) return;
                        if (wrap.querySelectorAll('.js-booking-svc-row').length <= 1) return;
                        btn.closest('.js-booking-svc-row').remove();
                        reindexSvcRows(wrap);
                    });
                });
            })();
        </script>
    @endpush
@endonce
