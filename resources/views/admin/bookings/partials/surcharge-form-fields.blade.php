{{-- @include(..., ['suffix' => ...]) --}}
<div class="js-surcharge-container" data-suffix="{{ $suffix }}">
    <div class="js-surcharge-rows">
        <div class="js-surcharge-row border rounded p-3 mb-2 bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-8">
                    <label class="form-label small fw-bold mb-1">Mô tả <span class="text-danger">*</span></label>
                    <textarea name="items[0][reason]" rows="2" class="form-control form-control-sm js-line-reason"
                              placeholder="VD: Hỏng ghế P301"></textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold mb-1">Số tiền (₫) <span class="text-danger">*</span></label>
                    <input type="number" name="items[0][amount]" class="form-control form-control-sm js-line-amount"
                           min="0" step="1000" placeholder="850000">
                </div>
                <div class="col-12 text-md-end">
                    <button type="button" class="btn btn-outline-danger btn-sm js-surcharge-remove-row mt-1" hidden title="Xóa dòng">&times; Xóa dòng</button>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm js-surcharge-add-row">
        <i class="bi bi-plus-lg"></i> Thêm dòng
    </button>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function reindexRows(wrap) {
                    var rows = wrap.querySelectorAll('.js-surcharge-row');
                    rows.forEach(function (row, i) {
                        row.querySelectorAll('input[name], textarea[name]').forEach(function (el) {
                            if (el.name) {
                                el.name = el.name.replace(/items\[\d+]/, 'items[' + i + ']');
                            }
                        });
                    });
                    var multi = rows.length > 1;
                    rows.forEach(function (row) {
                        var btn = row.querySelector('.js-surcharge-remove-row');
                        if (btn) btn.hidden = !multi;
                    });
                }

                function bindContainer(container) {
                    var wrap = container.querySelector('.js-surcharge-rows');
                    if (!wrap) return;

                    var addBtn = container.querySelector('.js-surcharge-add-row');
                    if (addBtn) {
                        addBtn.addEventListener('click', function () {
                            var first = wrap.querySelector('.js-surcharge-row');
                            var clone = first.cloneNode(true);
                            clone.querySelectorAll('input, textarea').forEach(function (el) { el.value = ''; });
                            wrap.appendChild(clone);
                            reindexRows(wrap);
                        });
                    }

                    wrap.addEventListener('click', function (e) {
                        var btn = e.target.closest('.js-surcharge-remove-row');
                        if (!btn || btn.hidden) return;
                        var row = btn.closest('.js-surcharge-row');
                        if (wrap.querySelectorAll('.js-surcharge-row').length <= 1) return;
                        row.remove();
                        reindexRows(wrap);
                    });
                }

                document.querySelectorAll('.js-surcharge-container').forEach(bindContainer);
            })();
        </script>
    @endpush
@endonce
