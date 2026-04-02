<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCancellationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $messageLine
    ) {}

    public function build(): self
    {
        return $this->subject('Thông báo đặt phòng #'.$this->booking->id.' — '.config('app.name'))
            ->view('mails.booking-cancellation');
    }
}
