<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RefundRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $refundRequest;

    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest;
    }

    public function build()
    {
        return $this->subject('Yêu cầu hoàn tiền mới #' . $this->refundRequest->booking_id)
                    ->view('emails.refund_requested');
    }
}
