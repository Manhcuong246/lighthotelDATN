@extends('layouts.app')

@section('title', 'Chính sách & điều khoản — ' . ($hotelInfo?->name ?? 'Light Hotel'))

@section('content')
<div class="lh-breakout lh-page-hero mb-4">
    <div class="container lh-page-hero-inner">
        <nav class="lh-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span class="text-white opacity-50 mx-2">/</span>
            <span class="active">Chính sách</span>
        </nav>
        <h1>Chính sách &amp; điều khoản</h1>
        <p class="lh-page-lead mt-2">Minh bạch về quyền riêng tư và điều kiện sử dụng dịch vụ đặt phòng trực tuyến.</p>
    </div>
</div>

<div class="lh-page-body">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="lh-glass-card p-4 p-lg-5">
                <div class="lh-policy-toc">
                    <a href="#privacy"><i class="bi bi-shield-lock me-1"></i> Bảo mật</a>
                    <a href="#terms"><i class="bi bi-file-text me-1"></i> Điều khoản</a>
                    <a href="{{ route('pages.contact') }}" class="lh-policy-toc-muted"><i class="bi bi-chat-dots me-1"></i> Liên hệ</a>
                </div>

                <section id="privacy" class="lh-policy-section scroll-mt-4">
                    <h2>Chính sách bảo mật</h2>
                    <p>
                        Chúng tôi thu thập thông tin cần thiết để xử lý đặt phòng (họ tên, email, số điện thoại, chi tiết lưu trú) và bảo mật theo quy định hiện hành.
                    </p>
                    <p>
                        Dữ liệu được dùng để xác nhận đơn, liên hệ khi cần và cải thiện dịch vụ. Chúng tôi không bán thông tin cá nhân cho bên thứ ba cho mục đích tiếp thị.
                    </p>
                    <p class="mb-0">
                        Bạn có thể yêu cầu chỉnh sửa hoặc xóa thông tin liên quan tài khoản qua kênh hỗ trợ trên trang <a href="{{ route('pages.contact') }}" class="fw-semibold text-decoration-none">Liên hệ</a>.
                    </p>
                </section>

                <section id="terms" class="lh-policy-section scroll-mt-4 mb-0">
                    <h2>Điều khoản sử dụng</h2>
                    <p>
                        Khi sử dụng website để tìm kiếm và đặt phòng, bạn đồng ý cung cấp thông tin trung thực và tuân thủ quy định của khách sạn trong thời gian lưu trú.
                    </p>
                    <p>
                        Giá hiển thị có thể thay đổi theo thời điểm và tình trạng phòng. Đơn đặt chỉ được xác nhận sau khi bạn nhận xác nhận chính thức (email hoặc thông báo trên hệ thống).
                    </p>
                    <p class="mb-0">
                        Khách sạn có quyền từ chối phục vụ trong trường hợp vi phạm nội quy, an ninh hoặc gây ảnh hưởng đến khách khác.
                    </p>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
