<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public Contact $contact;
    public string $replyMessage;

    public function __construct(Contact $contact, string $replyMessage)
    {
        $this->contact = $contact;
        $this->replyMessage = $replyMessage;
    }

    public function build()
    {
        $name = $this->contact->name ?: 'Quý khách';
        return $this->subject('Phản hồi liên hệ từ Ninh Bình StayWord')
                    ->html("
                        <h2>Xin chào {$name},</h2>
                        <p>Cảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là phản hồi của ban quản trị:</p>
                        <blockquote style='background: #f9f9f9; border-left: 5px solid #ccc; padding: 15px; margin: 15px 0; font-style: italic; color: #333;'>
                            " . nl2br(e($this->replyMessage)) . "
                        </blockquote>
                        <p>Mọi thắc mắc vui lòng phản hồi qua email này hoặc liên hệ hotline chăm sóc khách hàng.</p>
                        <p>Trân trọng,<br><strong>Ninh Bình StayWord</strong></p>
                    ");
    }
}
