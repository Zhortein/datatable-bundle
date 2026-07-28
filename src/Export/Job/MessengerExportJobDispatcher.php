<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Export\Job;

use Zhortein\DatatableBundle\Contract\ExportJobDispatcherInterface;

/**
 * Optional bridge accepting Symfony Messenger's MessageBusInterface service.
 *
 * The dependency intentionally remains structural so the bundle does not
 * require symfony/messenger for applications that only use synchronous exports.
 */
final readonly class MessengerExportJobDispatcher implements ExportJobDispatcherInterface
{
    private \Closure $dispatch;

    public function __construct(
        object $messageBus,
    ) {
        if (!is_callable([$messageBus, 'dispatch'])) {
            throw new \InvalidArgumentException('The Messenger export job dispatcher requires an object exposing dispatch().');
        }

        $this->dispatch = \Closure::fromCallable([$messageBus, 'dispatch']);
    }

    public function dispatch(ExportJobIdentifier $identifier): void
    {
        ($this->dispatch)(RunExportJobMessage::fromIdentifier($identifier));
    }
}
