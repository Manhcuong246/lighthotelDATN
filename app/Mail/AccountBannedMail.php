<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountBannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        $appName = config('app.name', 'Light Hotel');

        return new Envelope(
            subject: '['.$appName.'] Thông báo: tài khoản của bạn đã bị cấm',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-banned',
            with: [
                'user' => $this->user,
                'appName' => config('app.name', 'Light Hotel'),
                'supportHint' => 'Nếu bạn cho rằng đây là nhầm lẫn, vui lòng liên hệ khách sạn / bộ phận hỗ trợ qua kênh liên hệ trên website.',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
