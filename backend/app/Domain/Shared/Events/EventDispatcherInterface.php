<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
