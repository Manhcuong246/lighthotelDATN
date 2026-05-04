<div class="lh-room-type-pie-panel rounded-3 overflow-hidden border border-light-subtle bg-white"
     id="lhRoomTypePiePanel"
     data-ranking-url="{{ route('admin.dashboard.room-revenue-ranking') }}">
    <div class="px-3 py-3 border-bottom border-light-subtle">
        <h3 class="h6 fw-bold mb-0">Top 5 loại · paid</h3>
    </div>
    <div class="p-3 bg-light bg-opacity-40 border-bottom border-light-subtle">
        <div class="d-flex flex-wrap align-items-end gap-2 gap-md-3">
            <div class="flex-grow-1 flex-md-grow-0 lh-rt-detail-date" style="min-width: 140px;">
                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="lhPieStart">Từ</label>
                <input type="date" id="lhPieStart" class="form-control form-control-sm rounded-3 lh-pie-start lh-rt-detail-date-input" autocomplete="off">
            </div>
            <div class="flex-grow-1 flex-md-grow-0 lh-rt-detail-date" style="min-width: 140px;">
                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="lhPieEnd">Đến</label>
                <input type="date" id="lhPieEnd" class="form-control form-control-sm rounded-3 lh-pie-end lh-rt-detail-date-input" autocomplete="off">
            </div>
            <div class="d-flex flex-column justify-content-end">
                <span class="form-label lh-dash-toolbar-label mb-1 lh-rt-detail-label-spacer" aria-hidden="true">&nbsp;</span>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary rounded-3 lh-pie-apply px-3 lh-rt-detail-btn">Áp dụng</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 lh-pie-reset lh-rt-detail-btn" title="Từ đầu đến hôm nay" aria-label="Đặt lại mặc định"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>
        </div>
        <p class="small text-muted mt-2 mb-0 lh-pie-range-hint"></p>
    </div>
    <div class="p-3 p-md-4">
        <div class="position-relative lh-chart-pie-wrap mx-auto">
            <canvas id="roomTypeRevenuePieChart" aria-label="Biểu đồ tròn doanh thu theo loại phòng"></canvas>
        </div>
        <p class="small text-muted text-center mt-3 mb-0 lh-pie-empty d-none">Không có dữ liệu.</p>
    </div>
</div>
