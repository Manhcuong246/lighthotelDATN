<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\HotelInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentInstructionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public ?HotelInfo $hotelInfo;
    public int $nights;
    public ?string $qrCodeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, ?HotelInfo $hotelInfo, int $nights, ?string $qrCodeUrl = null)
    {
        $this->booking = $booking;
        $this->hotelInfo = $hotelInfo;
        $this->nights = $nights;
        $this->qrCodeUrl = $qrCodeUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Light Hotel] Thông tin thanh toán đơn đặt phòng #' . $this->booking->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-instruction',
            with: [
                'booking' => $this->booking,
                'hotelInfo' => $this->hotelInfo,
                'nights' => $this->nights,
                'qrCodeUrl' => $this->qrCodeUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
