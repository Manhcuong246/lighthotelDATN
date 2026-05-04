<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\HotelInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentInstructionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?HotelInfo $hotelInfo,
        public int $nights,
        public ?string $qrCodeUrl = null,
        /** Link có chữ ký: /payment/vnpay/pay/{booking} — khi khách bấm mới tạo phiên VNPay (15 phút). */
        public ?string $vnpayPayUrl = null,
        /** Admin ghi nhận tiền mặt đủ — email chỉ xác nhận đơn, không hướng dẫn CK/VNPay. */
        public bool $cashPaidAtDesk = false,
    ) {
        $this->booking->loadMissing([
            'user',
            'room.roomType',
            'rooms.roomType',
            'bookingRooms.room.roomType',
            'bookingRooms.roomType',
        ]);
    }

    public function envelope(): Envelope
    {
        $subject = $this->vnpayPayUrl
            ? '[Light Hotel] Link thanh toán VNPay — đơn #'.$this->booking->id
            : ($this->cashPaidAtDesk
                ? '[Light Hotel] Xác nhận đặt phòng — đơn #'.$this->booking->id
                : '[Light Hotel] Thông tin chuyển khoản — đơn #'.$this->booking->id);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $logoPath = storage_path('app/public/logo.png');
        $logoUrl = is_file($logoPath) ? asset('storage/logo.png') : null;

        return new Content(
            view: 'emails.payment-instruction',
            with: [
                'booking' => $this->booking,
                'hotelInfo' => $this->hotelInfo,
                'nights' => $this->nights,
                'qrCodeUrl' => $this->qrCodeUrl,
                'vnpayPayUrl' => $this->vnpayPayUrl,
                'cashPaidAtDesk' => $this->cashPaidAtDesk,
                'vnpayTxnMinutes' => (int) config('vnpay.transaction_expire_minutes', 15),
                'payLinkDays' => (int) config('vnpay.pay_entry_signed_ttl_days', 14),
                'logoUrl' => $logoUrl,
                /** Ảnh banner email — URL tuyệt đối; có thể ghi đè bằng config app.payment_mail_hero_url */
                'heroImageUrl' => (string) (config('app.payment_mail_hero_url')
                    ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&auto=format&fit=crop&q=80'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
