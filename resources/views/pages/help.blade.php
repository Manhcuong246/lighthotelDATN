@extends('layouts.app')

@section('title', 'Trợ giúp — ' . ($hotelInfo?->name ?? 'Light Hotel'))

@section('content')
<div class="lh-breakout lh-page-hero lh-page-hero--photo mb-4">
    <div class="container lh-page-hero-inner">
        <nav class="lh-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span class="text-white opacity-50 mx-2">/</span>
            <span class="active">Trợ giúp</span>
        </nav>
        <h1>Trợ giúp</h1>
        <p class="lh-page-lead mt-2">aaaa đặt phòng và sử dụng dịch vụ tại {{ $hotelInfo?->name ?? 'Light Hotel' }}.</p>
    </div>
</div>

<div class="lh-page-body">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 order-lg-2 d-none d-lg-block">
                <div class="lh-help-aside-card mb-4">
                    <h3>Mục lục</h3>
                    <nav class="lh-help-nav" aria-label="Mục lục trợ giúp">
                        <a href="#faq-section">Câu hỏi thường gặp</a>
                        <a href="#more-help">Tài liệu thêm</a>
                        <a href="{{ route('pages.contact') }}">Liên hệ trực tiếp</a>
                    </nav>
                </div>
                <div class="lh-help-hero-side">
                    <img src="https://images.pexels.com/photos/6476589/pexels-photo-6476589.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=800" alt="Hỗ trợ khách lưu trú" loading="lazy" width="600" height="400">
                </div>
            </div>
            <div class="col-lg-8 order-lg-1">
                <div class="lh-glass-card p-4 p-lg-5" id="faq-section">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="lh-contact-icon mb-0"><i class="bi bi-question-circle"></i></div>
                        <div>
                            <h2 class="h5 fw-bold mb-0 text-dark">Câu hỏi thường gặp</h2>
                            <p class="small text-secondary mb-0">Mở từng mục để xem hướng dẫn chi tiết</p>
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
                                    Chọn ngày nhận và trả phòng trên trang chủ, nhấn <strong>Tìm</strong> để xem phòng khả dụng, chọn loại phòng phù hợp rồi hoàn tất thông tin đặt chỗ và thanh toán theo phương thức hiển thị khi bạn xác nhận đơn.
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
                                    Điều kiện hủy và đổi lịch phụ thuộc gói giá và thời điểm bạn yêu cầu. Xem chi tiết trong mục chính sách trên trang đặt chỗ, trong phần <a href="{{ route('pages.policy') }}#cancellation" class="fw-semibold">Chính sách</a>, hoặc trong email xác nhận.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Thanh toán và hóa đơn như thế nào?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#helpFaq">
                                <div class="accordion-body">
                                    Sau khi đặt thành công, bạn sẽ nhận email xác nhận kèm chi tiết thanh toán. Nếu cần hóa đơn VAT hoặc chỉnh sửa thông tin xuất hóa đơn, vui lòng gửi yêu cầu qua <a href="{{ route('pages.contact') }}" class="fw-semibold">Liên hệ</a> trong vòng 24 giờ kể từ khi thanh toán.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Cần hỗ trợ trực tiếp?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#helpFaq">
                                <div class="accordion-body">
                                    Vui lòng <a href="{{ route('pages.contact') }}" class="fw-semibold">liên hệ</a> qua điện thoại hoặc email — chúng tôi phản hồi trong giờ làm việc lễ tân và hỗ trợ khẩn khi cần.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-top" id="more-help">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <h3 class="h6 fw-bold text-dark mb-1">Tài liệu pháp lý &amp; minh bạch</h3>
                                <p class="small text-secondary mb-0">Đọc thêm về bảo mật, điều khoản và chính sách hủy trước khi đặt phòng.</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="{{ route('pages.policy') }}" class="btn btn-outline-primary btn-sm rounded-pill px-4">Chính sách &amp; điều khoản</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
