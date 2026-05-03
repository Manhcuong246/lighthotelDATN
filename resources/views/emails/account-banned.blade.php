<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tài khoản bị cấm</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;font-size:16px;line-height:1.5;color:#1a1a1a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f5f7;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <p style="margin:0;font-size:13px;letter-spacing:0.06em;text-transform:uppercase;color:#6c757d;">{{ $appName }}</p>
                            <h1 style="margin:8px 0 16px;font-size:22px;font-weight:700;color:#c0392b;">Tài khoản đã bị cấm</h1>
                            <p style="margin:0 0 16px;">Xin chào <strong>{{ $user->full_name }}</strong>,</p>
                            <p style="margin:0 0 16px;">Chúng tôi thông báo tài khoản gắn với email <strong>{{ $user->email }}</strong> trên hệ thống <strong>{{ $appName }}</strong> đã được đặt trạng thái <strong>bị cấm</strong>.</p>
                            <p style="margin:0 0 16px;">Bạn sẽ <strong>không thể đăng nhập</strong> cho đến khi khách sạn xem xét và mở lại (nếu có).</p>
                            <p style="margin:0 0 24px;color:#495057;">{{ $supportHint }}</p>
                            <p style="margin:0;font-size:14px;color:#868e96;">Đây là email tự động, vui lòng không trả lời trực tiếp nếu hộp thư không được giám sát.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
