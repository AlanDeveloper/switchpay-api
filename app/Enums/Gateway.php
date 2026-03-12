<?php

namespace App\Enums;

use App\Services\Gateway1Service;
use App\Services\Gateway2Service;

enum Gateway: string
{
    case GATEWAY1 = 'gateway_1';
    case GATEWAY2 = 'gateway_2';

    /**
     * @return string|null
     */
    public function class(): ?string
    {
        return match ($this) {
            self::GATEWAY1 => Gateway1Service::class,
            self::GATEWAY2 => Gateway2Service::class,
            default => null,
        };
    }
}
