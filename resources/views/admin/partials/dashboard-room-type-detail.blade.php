<div id="lhRoomTypeDetailPanel"
     class="card border-0 shadow-sm rounded-3"
     data-detail-url="{{ route('admin.dashboard.room-type-detail') }}">
    <div class="card-body p-3 p-md-4">
        <div class="lh-rt-detail-toolbar mb-3">
            <div class="lh-rt-detail-room">
                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="lhRtDetailSelect">Loại phòng</label>
                <select id="lhRtDetailSelect" class="form-select form-select-sm rounded-3 lh-dash-control w-100">
                    <option value="">— Chọn —</option>
                    @foreach($roomTypesForDetail ?? [] as $rtOpt)
                        <option value="{{ $rtOpt->id }}">{{ $rtOpt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lh-rt-detail-date">
                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="lhRtDetailStart">Từ</label>
                <input type="date" id="lhRtDetailStart" class="form-control form-control-sm rounded-3 lh-dash-control lh-rt-detail-date-input" autocomplete="off">
            </div>
            <div class="lh-rt-detail-date">
                <label class="form-label lh-dash-toolbar-label text-muted mb-1" for="lhRtDetailEnd">Đến</label>
                <input type="date" id="lhRtDetailEnd" class="form-control form-control-sm rounded-3 lh-dash-control lh-rt-detail-date-input" autocomplete="off">
            </div>
            <div class="lh-rt-detail-actions">
                <span class="form-label lh-dash-toolbar-label mb-1 lh-rt-detail-label-spacer" aria-hidden="true">&nbsp;</span>
                <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 lh-rt-detail-btn" id="lhRtDetailApply">Áp dụng</button>
            </div>
            <div class="lh-rt-detail-actions">
                <span class="form-label lh-dash-toolbar-label mb-1 lh-rt-detail-label-spacer" aria-hidden="true">&nbsp;</span>
                <a href="#" id="lhRtDetailEdit" class="btn btn-sm btn-outline-secondary rounded-3 lh-rt-detail-btn d-none" target="_blank" rel="noopener">Sửa loại</a>
            </div>
        </div>
        <p id="lhRtDetailMeta" class="small text-muted mb-3 d-none"></p>
        <div id="lhRtDetailMetrics" class="row g-2 mb-3"></div>
        <p class="small text-muted mb-2 mb-md-3" id="lhRtDetailHint"></p>
        <div class="position-relative lh-chart-tall d-none" id="lhRtDetailChartWrap">
            <canvas id="roomTypeDetailRevenueChart" aria-label="Doanh thu loại phòng đã chọn"></canvas>
        </div>
        <p class="small text-muted mb-0" id="lhRtDetailPlaceholder">Chọn loại phòng để xem thông số và biểu đồ.</p>
    </div>
</div>
