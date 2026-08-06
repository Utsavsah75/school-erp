<?php

namespace App\Core\Sms;

interface SmsDriverInterface
{
    public function send(string $toPhone, string $message): bool;
}
