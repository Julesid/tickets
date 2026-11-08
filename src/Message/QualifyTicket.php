<?php

namespace App\Message;

final class QualifyTicket
{
    public function __construct(public readonly int $ticketId) {}
}