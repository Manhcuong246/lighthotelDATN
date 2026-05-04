@extends('layouts.admin')

@section('title', 'Cài đặt khách sạn & website')

@push('styles')
<style>
    .lh-set-hero {
        background: linear-gradient(135deg, #1d3557 0%, #4361ee 55%, #3a86ff 100%);
        color: #fff;
        border-radius: 1rem;
        padding: 1.75rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 12px 40px rgba(29, 53, 87, 0.22);
    }
    .lh-set-hero h1 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; }
    .lh-set-hero p { opacity: 0.92; font-size: 0.9rem; max-width: 42rem; margin-bottom: 0; }
    .lh-set-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        background: #fff;
    }
    .lh-set-tabs {
        padding: 0.75rem 0.75rem 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: #fafbfc;
    }
    .lh-set-tabs .nav-link {
        border: none;
        border-radius: 0.65rem 0.65rem 0 0;
        color: #64748b;
        font-weight: 600;
        padding: 0.65rem 1.15rem;
        margin-right: 0.25rem;
    }
    .lh-set-tabs .nav-link:hover { color: #1d3557; background: rgba(67, 97, 238, 0.06); }
    .lh-set-tabs .nav-link.active {
        color: #1d3557;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-bottom-color: #fff;
        margin-bottom: -1px;
    }
    .lh-set-pane { padding: 1.5rem 1.25rem 0.5rem; }
    @media (min-width: 768px) {
        .lh-set-pane { padding: 1.75rem 1.75rem 0.75rem; }
    }
    .lh-set-label { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: #64748b; }
    .lh-set-foot {
        background: #f8fafc;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
        padding: 1rem 1.25rem;
    }
    .lh-set-inner-card {
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 0.85rem;
        background: #f8fafc;
    }
    .lh-policy-accordion .accordion-button:not(.collapsed) {
        background: rgba(67, 97, 238, 0.08);
        color: #1d3557;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
@php
    /** @var \App\Models\HotelInfo|null $hotelInfo */
    $activeTab = $tab ?? 'general';
@endphp

<div class="container-fluid px-0 px-lg-1">
    <div class="lh-set-hero">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h1 class="mb-2"><i class="bi bi-sliders2 me-2"></i>Cài đặt khách sạn & website</h1>
                <p>Một trang cho thương hiệu, liên hệ, VietQR và văn bản pháp lý — thay cho chỉnh sửa rải rác trên giao diện khách.</p>
            </div>
            <a href="{{ route('home') }}" class="btn btn-light btn-sm fw-semibold shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i>Xem trang chủ
            </a>
        </div>
    </div>

    <div class="lh-set-card">
        <ul class="nav lh-set-tabs" id="settingsHubTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                    <i class="bi bi-building-check me-1"></i>Khách sạn & thanh toán
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'policies' ? 'active' : '' }}" id="tab-policies-btn" data-bs-toggle="tab" data-bs-target="#tab-policies" type="button" role="tab" aria-controls="tab-policies" aria-selected="{{ $activeTab === 'policies' ? 'true' : 'false' }}">
                    <i class="bi bi-journal-text me-1"></i>Chính sách & pháp lý
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsHubTabContent">
            <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="tab-general" role="tabpanel" aria-labelledby="tab-general-btn" tabindex="0">
                <form action="{{ route('admin.settings.update.general') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="lh-set-pane">
                        <div class="lh-set-inner-card p-3 p-md-4 mb-4">
                            <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-megaphone text-primary me-2"></i>Thương hiệu & liên hệ</h2>
                            <p class="small text-muted mb-4">Dùng trên trang chủ, footer, email và phiếu thanh toán.</p>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="lh-set-label d-block mb-2">Tên khách sạn</label>
                                    <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" name="name" value="{{ old('name', $hotelInfo->name ?? '') }}" placeholder="Ví dụ: Light Hotel" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="lh-set-label d-block mb-2">Điểm đánh giá hiển thị (0–5)</label>
                                    <input type="number" step="0.1" min="0" max="5" class="form-control form-control-lg @error('rating_avg') is-invalid @enderror" name="rating_avg" value="{{ old('rating_avg', $hotelInfo->rating_avg ?? '') }}" placeholder="4.8">
                                    @error('rating_avg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">Hiển thị tóm tắt trên khối hero đặt phòng.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="lh-set-label d-block mb-2">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $hotelInfo->email ?? '') }}" placeholder="contact@khachsan.com">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="lh-set-label d-block mb-2">Điện thoại</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $hotelInfo->phone ?? '') }}" placeholder="0236 xxx xxxx">
                                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="lh-set-label d-block mb-2">Địa chỉ</label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', $hotelInfo->address ?? '') }}" placeholder="Địa chỉ đầy đủ">
                                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="lh-set-label d-block mb-2">Mô tả ngắn</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" placeholder="Giới thiệu ngắn (footer, giới thiệu nhanh…)">{{ old('description', $hotelInfo->description ?? '') }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="lh-set-inner-card p-3 p-md-4 mb-2">
                            <h2 class="h6 fw-bold text-dark mb-3"><i class="bi bi-bank2 text-primary me-2"></i>Chuyển khoản & VietQR</h2>
                            <p class="small text-muted mb-4">Thông tin tài khoản nhận tiền cho đơn chuyển khoản / QR trong email hướng dẫn.</p>
                            <div class="row g-4">
                                <div class="col-lg-4">
                                    <label class="lh-set-label d-block mb-2">Mã ngân hàng</label>
                                    <input type="text" class="form-control @error('bank_id') is-invalid @enderror" name="bank_id" value="{{ old('bank_id', $hotelInfo->bank_id ?? '') }}" placeholder="mbbank">
                                    <div class="form-text">Ví dụ: <code>mbbank</code>, <code>vietcombank</code>, <code>techcombank</code></div>
                                    @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-lg-4">
                                    <label class="lh-set-label d-block mb-2">Số tài khoản</label>
                                    <input type="text" class="form-control @error('bank_account') is-invalid @enderror" name="bank_account" value="{{ old('bank_account', $hotelInfo->bank_account ?? '') }}" placeholder="Số tài khoản">
                                    @error('bank_account')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-lg-4">
                                    <label class="lh-set-label d-block mb-2">Tên chủ TK (in hoa, không dấu)</label>
                                    <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror" name="bank_account_name" value="{{ old('bank_account_name', $hotelInfo->bank_account_name ?? '') }}" placeholder="NGUYEN VAN A">
                                    @error('bank_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            @if($hotelInfo && $hotelInfo->bank_id && $hotelInfo->bank_account)
                                <div class="alert alert-info border-0 rounded-3 mt-4 mb-0 small">
                                    <i class="bi bi-qr-code-scan me-2"></i>Đủ thông tin để sinh QR VietQR khi tạo hướng dẫn thanh toán chuyển khoản.
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="lh-set-foot d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="small text-muted">Một nút lưu cho toàn bộ khối khách sạn & ngân hàng.</span>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>Lưu
                        </button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade {{ $activeTab === 'policies' ? 'show active' : '' }}" id="tab-policies" role="tabpanel" aria-labelledby="tab-policies-btn" tabindex="0">
                <form action="{{ route('admin.settings.update.site.content') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="lh-set-pane">
                        <p class="text-muted small mb-3">Nội dung trang chính sách và các đoạn pháp lý hiển thị cho khách.</p>
                        <div class="accordion lh-policy-accordion shadow-sm rounded-3 overflow-hidden border" id="accordionPolicies">
                            @foreach($policyBlocks as $i => $block)
                                @php
                                    $type = $block['type'];
                                    $rec = $block['record'];
                                    $defaultBody = \App\Http\Controllers\Admin\SettingsAdminController::defaultPolicyBody($type);
                                    $fieldKey = 'contents.'.$i.'.content';
                                @endphp
                                <div class="accordion-item border-0 border-bottom">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePol{{ $i }}">
                                            {{ $block['label'] }}
                                        </button>
                                    </h2>
                                    <div id="collapsePol{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#accordionPolicies">
                                        <div class="accordion-body pt-0 bg-white">
                                            <input type="hidden" name="contents[{{ $i }}][type]" value="{{ $type }}">
                                            <input type="hidden" name="contents[{{ $i }}][content_id]" value="{{ $rec->id ?? '' }}">
                                            <textarea name="contents[{{ $i }}][content]" class="form-control @error($fieldKey) is-invalid @enderror" rows="8">{{ old($fieldKey, $rec->content ?? $defaultBody) }}</textarea>
                                            @error($fieldKey)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="lh-set-foot d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="small text-muted">Lưu tất cả các mục trong một lần.</span>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-journal-check me-1"></i>Lưu chính sách
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
