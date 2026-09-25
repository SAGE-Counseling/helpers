<?php

namespace SageCounseling\Helpers\Notifications;

use Illuminate\Contracts\Mail\Mailer;

/**
 * Delivers an AdminMessage as a raw-text email to the single configured admin
 * recipient. See docs/admin-notifications-contract.md.
 */
final class MailChannelSender implements ChannelSender
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly string $toEmail,
        private readonly ?string $toName = null,
    ) {
    }

    public function send(AdminMessage $message): void
    {
        $address = $this->toName ? [$this->toEmail => $this->toName] : $this->toEmail;
        $subject = $message->subject ?? sprintf('SAGE Admin Alert (%s)', $message->severity->name);

        $this->mailer->raw($message->message, function ($mail) use ($address, $subject): void {
            $mail->to($address)->subject($subject);
        });
    }
}
