<div class="js-booking-svc-wrap">
    <div class="js-booking-svc-rows">
        <div class="js-booking-svc-row border rounded p-3 mb-2 bg-white">
            <div class="row g-2 align-items-end">
                <div class="col-md-7 col-lg-8">
                    <label class="form-label small fw-bold mb-1">Dịch vụ <span class="text-muted fw-normal">(khi thêm DV)</span></label>
                    <select name="svc_items[0][service_id]" class="form-select form-select-sm">
                        <option value="">— Dịch vụ —</option>
                        @foreach($services as $svc)
                            <option value="{{ $svc->id }}">{{ $svc->name }} — {{ number_format((float) $svc->price, 0, ',', '.') }} ₫</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 col-lg-4">
                    <label class="form-label small fw-bold mb-1">Số lượng</label>
                    <input type="number" name="svc_items[0][quantity]" value="1" min="1" max="9999" class="form-control form-control-sm">
                </div>
                <div class="col-12 text-md-end">
                    <button type="button" class="btn btn-outline-danger btn-sm js-booking-svc-remove mt-1" hidden title="Xóa dòng">&times; Xóa dòng</button>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm js-booking-svc-add">
        <i class="bi bi-plus-lg"></i> Thêm dòng
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
