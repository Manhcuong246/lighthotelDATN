<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\HotelInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VnPayPaidPortalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        /** URL có chữ ký tới chi tiết đơn (màn hình chính, có hoàn tiền khi đủ điều kiện). */
        public string $bookingDetailUrl,
        /** URL có chữ ký tới biên lai / hóa đơn. */
        public string $invoiceUrl,
        public ?HotelInfo $hotelInfo = null,
        /** URL có chữ ký tới danh sách đơn (liên kết phụ). */
        public ?string $guestPortalIndexUrl = null,
    ) {
        $this->booking->loadMissing([
            'user',
            'rooms.roomType',
            'bookingRooms.roomType',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Light Hotel] Thanh toán VNPay thành công — biên lai đơn #'.$this->booking->id
        );
    }

    public function content(): Content
    {
        $logoPath = storage_path('app/public/logo.png');
        $logoUrl = is_file($logoPath) ? asset('storage/logo.png') : null;

        $nights = max(1, (int) $this->booking->check_in->diffInDays($this->booking->check_out));

        return new Content(
            view: 'emails.vnpay-paid-portal',
            with: [
                'booking' => $this->booking,
                'hotelInfo' => $this->hotelInfo,
                'bookingDetailUrl' => $this->bookingDetailUrl,
                'invoiceUrl' => $this->invoiceUrl,
                'guestPortalIndexUrl' => $this->guestPortalIndexUrl,
                'nights' => $nights,
                'logoUrl' => $logoUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
