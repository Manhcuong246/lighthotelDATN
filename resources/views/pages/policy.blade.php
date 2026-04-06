@extends('layouts.app')

@section('title', 'Chính sách & điều khoản — ' . ($hotelInfo?->name ?? 'Light Hotel'))

@section('content')
<div class="lh-breakout lh-page-hero lh-page-hero--photo mb-4">
    <div class="container lh-page-hero-inner">
        <nav class="lh-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span class="text-white opacity-50 mx-2">/</span>
            <span class="active">Chính sách</span>
        </nav>
        <h1>Chính sách &amp; điều khoản</h1>
        <p class="lh-page-lead mt-2">Tài liệu pháp lý và điều kiện sử dụng dịch vụ đặt phòng trực tuyến tại {{ $hotelInfo?->name ?? 'Light Hotel' }} — áp dụng cho khách lẻ và doanh nghiệp.</p>
    </div>
</div>

<div class="lh-page-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <span class="lh-updated-tag"><i class="bi bi-info-circle"></i> Áp dụng cho giao dịch qua website hiện tại</span>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="lh-glass-card p-4 p-lg-5">
                    <div class="lh-policy-grid">
                        <a href="#privacy" class="lh-policy-doc-card">
                            <i class="bi bi-shield-lock doc-ico"></i>
                            <div>
                                <h3>Bảo mật dữ liệu</h3>
                                <p>Thu thập, mục đích sử dụng và quyền của bạn đối với thông tin cá nhân.</p>
                            </div>
                        </a>
                        <a href="#terms" class="lh-policy-doc-card">
                            <i class="bi bi-file-text doc-ico"></i>
                            <div>
                                <h3>Điều khoản sử dụng</h3>
                                <p>Quy tắc khi truy cập website, đặt phòng và sử dụng dịch vụ.</p>
                            </div>
                        </a>
                        <a href="#booking" class="lh-policy-doc-card">
                            <i class="bi bi-calendar-check doc-ico"></i>
                            <div>
                                <h3>Đặt phòng &amp; thanh toán</h3>
                                <p>Xác nhận đơn, giá hiển thị và phương thức thanh toán.</p>
                            </div>
                        </a>
                        <a href="#cancellation" class="lh-policy-doc-card">
                            <i class="bi bi-arrow-counterclockwise doc-ico"></i>
                            <div>
                                <h3>Hủy &amp; hoàn tiền</h3>
                                <p>Nguyên tắc chung; chi tiết theo từng loại phòng khi đặt.</p>
                            </div>
                        </a>
                    </div>

                    <p class="lh-doc-lead mb-0">
                        Nếu có mâu thuẫn giữa nội dung tóm tắt trên website và hợp đồng/email xác nhận, vui lòng ưu tiên văn bản trong email xác nhận hoặc liên hệ <a href="{{ route('pages.contact') }}" class="fw-semibold text-decoration-none">bộ phận lễ tân</a>.
                    </p>

                    <div class="lh-policy-toc">
                        <a href="#privacy"><i class="bi bi-shield-lock me-1"></i> Bảo mật</a>
                        <a href="#terms"><i class="bi bi-file-text me-1"></i> Điều khoản</a>
                        <a href="#booking"><i class="bi bi-calendar-check me-1"></i> Đặt phòng</a>
                        <a href="#cancellation"><i class="bi bi-arrow-counterclockwise me-1"></i> Hủy phòng</a>
                        <a href="#cookies"><i class="bi bi-hdd-network me-1"></i> Cookie</a>
                        <a href="{{ route('pages.contact') }}" class="lh-policy-toc-muted"><i class="bi bi-chat-dots me-1"></i> Liên hệ</a>
                    </div>

                    <section id="privacy" class="lh-policy-section scroll-mt-4">
                        <h2>Chính sách bảo mật</h2>
                        <p>
                            Chúng tôi thu thập thông tin cần thiết để xử lý đặt phòng (họ tên, email, số điện thoại, chi tiết lưu trú, và khi cần thông tin thanh toán theo kênh bạn chọn) và bảo mật theo quy định hiện hành tại Việt Nam.
                        </p>
                        <p>
                            Dữ liệu được dùng để xác nhận đơn, liên hệ khi cần, xử lý thanh toán/hoàn tiền và cải thiện dịch vụ. Chúng tôi không bán thông tin cá nhân cho bên thứ ba cho mục đích tiếp thị.
                        </p>
                        <p>
                            Hệ thống có thể ghi nhật ký kỹ thuật (địa chỉ IP, loại trình duyệt, thời gian truy cập) nhằm bảo mật và phân tích lỗi — dữ liệu này được lưu giữ có thời hạn và hạn chế quyền truy cập nội bộ.
                        </p>
                        <p class="mb-0">
                            Bạn có thể yêu cầu chỉnh sửa hoặc xóa thông tin liên quan tài khoản qua kênh hỗ trợ trên trang <a href="{{ route('pages.contact') }}" class="fw-semibold text-decoration-none">Liên hệ</a>. Thời gian xử lý yêu cầu phụ thuộc loại dữ liệu và nghĩa vụ lưu trữ hợp đồng.
                        </p>
                    </section>

                    <section id="terms" class="lh-policy-section scroll-mt-4">
                        <h2>Điều khoản sử dụng</h2>
                        <p>
                            Khi sử dụng website để tìm kiếm và đặt phòng, bạn đồng ý cung cấp thông tin trung thực và tuân thủ quy định của khách sạn trong thời gian lưu trú, bao gồm an ninh, an toàn cháy nổ và trật tự chung.
                        </p>
                        <p>
                            Nội dung, hình ảnh và mô tả trên website thuộc quyền sở hữu hoặc được cấp phép sử dụng. Việc sao chép cho mục đích thương mại cần có sự đồng ý bằng văn bản.
                        </p>
                        <p class="mb-0">
                            Khách sạn có quyền từ chối phục vụ trong trường hợp vi phạm nội quy, an ninh hoặc gây ảnh hưởng đến khách khác.
                        </p>
                    </section>

                    <section id="booking" class="lh-policy-section scroll-mt-4">
                        <h2>Đặt phòng &amp; thanh toán</h2>
                        <p>
                            Giá hiển thị theo từng loại phòng và ngày có thể thay đổi theo thời điểm, số lượng phòng còn trống và chương trình khuyến mãi. Giá cuối cùng được xác nhận tại bước thanh toán trước khi bạn hoàn tất đơn.
                        </p>
                        <p>
                            Đơn đặt chỉ được coi là xác nhận sau khi hệ thống ghi nhận thanh toán thành công (hoặc theo điều kiện “giữ phòng” nếu được hiển thị rõ) và bạn nhận thông báo/email xác nhận từ khách sạn.
                        </p>
                        <p class="mb-0">
                            Phụ thu (extra bed, giờ nhận/trả phòng sớm/muộn, dịch vụ thêm) được áp dụng theo bảng giá tại khách sạn và có thể được thông báo khi nhận phòng hoặc qua email trước ngày đến.
                        </p>
                    </section>

                    <section id="cancellation" class="lh-policy-section scroll-mt-4">
                        <h2>Hủy phòng &amp; hoàn tiền</h2>
                        <p>
                            Điều kiện hủy miễn phí, hủy có phí và mức hoàn tiền phụ thuộc gói giá và thời điểm bạn gửi yêu cầu. Thông tin cụ thể được hiển thị trên trang đặt phòng và trong email xác nhận.
                        </p>
                        <p class="mb-0">
                            Trường hợp bất khả kháng (thiên tai, sự cố hệ thống ngân hàng…), khách sạn sẽ phối hợp xử lý theo chính sách từng thời kỳ và thông báo qua kênh liên hệ đã đăng ký.
                        </p>
                    </section>

                    <section id="cookies" class="lh-policy-section scroll-mt-4 mb-0">
                        <h2>Cookie &amp; công nghệ tương tự</h2>
                        <p>
                            Website có thể sử dụng cookie phiên và cookie chức năng để duy trì phiên đăng nhập (nếu có), ghi nhớ tùy chọn ngôn ngữ và bảo vệ chống lạm dụng (CSRF). Bạn có thể điều chỉnh trình duyệt để từ chối cookie, tuy nhiên một số tính năng có thể không hoạt động đầy đủ.
                        </p>
                        <p class="mb-0">
                            Chúng tôi không dùng cookie để theo dõi hành vi nhạy cảm ngoài phạm vi vận hành website và xử lý đặt phòng.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
