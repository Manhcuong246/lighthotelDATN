<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RefundProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $refundRequest;

    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest;
    }

    public function build()
    {
        $status = $this->refundRequest->status === 'refunded' ? 'đã được chấp nhận' : 'đã bị từ chối';
        return $this->subject("Yêu cầu hoàn tiền đơn #{$this->refundRequest->booking_id} {$status}")
                    ->view('emails.refund_processed');
    }
}
