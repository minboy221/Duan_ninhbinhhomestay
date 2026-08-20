<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTenantAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $tenantName;
    public string $phone;
    public string $email;
    public string $password;
    public string $roomName;
    public string $boardingHouseName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $tenantName,
        string $phone,
        string $email,
        string $password,
        string $roomName = '',
        string $boardingHouseName = ''
    ) {
        $this->tenantName = $tenantName;
        $this->phone = $phone;
        $this->email = $email;
        $this->password = $password;
        $this->roomName = $roomName;
        $this->boardingHouseName = $boardingHouseName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thông tin tài khoản & Hợp đồng thuê phòng mới - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-tenant-account',
            with: [
                'tenantName' => $this->tenantName,
                'phone' => $this->phone,
                'email' => $this->email,
                'password' => $this->password,
                'roomName' => $this->roomName,
                'boardingHouseName' => $this->boardingHouseName,
            ]
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
