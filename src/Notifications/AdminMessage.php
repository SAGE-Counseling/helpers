<?php

namespace SageCounseling\Helpers\Notifications;

final class AdminMessage
{
    public function __construct(
        public readonly string $message,
        public readonly Severity $severity,
        public readonly ?string $subject = null,
    ) {
    }
}
