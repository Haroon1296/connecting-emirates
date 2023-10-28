<?php

namespace App\Enums\Event;

enum RequestStatus: int
{
    case PENDING = 0;
    case ACCEPT = 1;
    case REJECT = 2;
}