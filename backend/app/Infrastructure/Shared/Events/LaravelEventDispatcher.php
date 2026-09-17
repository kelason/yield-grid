<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Events;

use App\Domain\Shared\Events\EventDispatcherInterface;

class LaravelEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): void
    {
        broadcast($event)->toOthers();
    }
}
