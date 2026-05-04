@include('admin.bookings.partials.extras-form-styles')
{{-- @include(..., ['suffix' => ...]) --}}
<div class="js-surcharge-container" data-suffix="{{ $suffix }}">
    <div class="js-surcharge-rows abs-extras-rows">
        <div class="js-surcharge-row abs-extras-row">
            <div class="d-flex flex-wrap gap-2 gap-md-3 align-items-end">
                <div class="flex-grow-1" style="min-width: min(100%, 200px);">
                    <label class="abs-extras-label">Mô tả <span class="text-danger">*</span></label>
                    <textarea name="items[0][reason]" rows="2" class="form-control form-control-sm js-line-reason"
                              placeholder="VD: Hỏng ghế P301" style="resize: vertical; min-height: 2.75rem;"></textarea>
                </div>
                <div style="width: 8.5rem; flex-shrink: 0;">
                    <label class="abs-extras-label">Số tiền (₫) <span class="text-danger">*</span></label>
                    <input type="number" name="items[0][amount]" class="form-control form-control-sm js-line-amount"
                           min="0" step="1000" placeholder="850000">
                </div>
                <div class="ms-md-auto pb-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 js-surcharge-remove-row" hidden title="Xóa dòng" aria-label="Xóa dòng">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <button type="button" class="abs-extras-add js-surcharge-add-row mt-2">
        <i class="bi bi-plus-circle"></i>
        <span>Thêm dòng</span>
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
