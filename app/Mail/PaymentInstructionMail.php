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
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->vnpayPayUrl
            ? '[Light Hotel] Link thanh toán VNPay — đơn #'.$this->booking->id
            : '[Light Hotel] Thông tin chuyển khoản — đơn #'.$this->booking->id;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-instruction',
            with: [
                'booking' => $this->booking,
                'hotelInfo' => $this->hotelInfo,
                'nights' => $this->nights,
                'qrCodeUrl' => $this->qrCodeUrl,
                'vnpayPayUrl' => $this->vnpayPayUrl,
                'signedBookingViewUrl' => $this->booking->signedPublicShowUrl(),
                'vnpayTxnMinutes' => (int) config('vnpay.transaction_expire_minutes', 15),
                'payLinkDays' => (int) config('vnpay.pay_entry_signed_ttl_days', 14),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
