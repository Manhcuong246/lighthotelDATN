@extends('layouts.app')

@section('title', 'Trợ giúp — ' . ($hotelInfo?->name ?? 'Light Hotel'))

@section('content')
<div class="lh-breakout lh-page-hero mb-4">
    <div class="container lh-page-hero-inner">
        <nav class="lh-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span class="text-white opacity-50 mx-2">/</span>
            <span class="active">Trợ giúp</span>
        </nav>
        <h1>Trợ giúp</h1>
        <p class="lh-page-lead mt-2">Câu hỏi thường gặp khi đặt phòng và sử dụng dịch vụ tại {{ $hotelInfo?->name ?? 'Light Hotel' }}.</p>
    </div>
</div>

<div class="lh-page-body">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="lh-glass-card p-4 p-lg-5">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="lh-contact-icon mb-0"><i class="bi bi-question-circle"></i></div>
                    <div>
                        <h2 class="h5 fw-bold mb-0 text-dark">Câu hỏi thường gặp</h2>
                        <p class="small text-secondary mb-0">Chạm vào từng mục để xem chi tiết</p>
                    </div>
                </div>

                <div class="accordion lh-faq-accordion" id="helpFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
                                Làm sao để đặt phòng?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#helpFaq">
                            <div class="accordion-body">
                                Chọn ngày nhận và trả phòng trên trang chủ, chọn loại phòng phù hợp rồi hoàn tất thông tin đặt chỗ. Thanh toán thực hiện theo phương thức hiển thị khi bạn xác nhận đơn.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Tôi có thể hủy hoặc đổi lịch đặt phòng không?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#helpFaq">
                            <div class="accordion-body">
                                Điều kiện hủy và đổi lịch phụ thuộc gói giá và thời điểm bạn yêu cầu. Xem chi tiết trong mục chính sách trên trang đặt chỗ hoặc trong email xác nhận.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Cần hỗ trợ trực tiếp?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#helpFaq">
                            <div class="accordion-body">
                                Vui lòng <a href="{{ route('pages.contact') }}" class="fw-semibold">liên hệ</a> qua điện thoại hoặc email — chúng tôi phản hồi trong giờ làm việc và hỗ trợ khẩn khi cần.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 d-flex flex-wrap gap-2 align-items-center justify-content-between border-top">
                    <span class="small text-secondary">Tài liệu pháp lý</span>
                    <a href="{{ route('pages.policy') }}" class="btn btn-outline-primary btn-sm rounded-pill">Chính sách &amp; điều khoản</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
