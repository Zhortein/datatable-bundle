<?php

declare(strict_types=1);

namespace Zhortein\DatatableBundle\Tests\Unit\Export\Job;

use PHPUnit\Framework\TestCase;
use Zhortein\DatatableBundle\Export\Job\ExportJobIdentifier;
use Zhortein\DatatableBundle\Export\Job\MessengerExportJobDispatcher;
use Zhortein\DatatableBundle\Export\Job\RunExportJobMessage;

final class MessengerExportJobDispatcherTest extends TestCase
{
    public function test_it_dispatches_the_transport_safe_job_message(): void
    {
        $bus = new RecordingMessageBus();
        $dispatcher = new MessengerExportJobDispatcher($bus);

        $dispatcher->dispatch(new ExportJobIdentifier('job_1234567890abcdef'));

        self::assertInstanceOf(RunExportJobMessage::class, $bus->message);
        self::assertSame(
            'job_1234567890abcdef',
            $bus->message->getJobIdentifier()->toString(),
        );
    }

    public function test_it_rejects_objects_without_a_dispatch_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MessengerExportJobDispatcher(new \stdClass());
    }
}

final class RecordingMessageBus
{
    public ?object $message = null;

    public function dispatch(object $message): object
    {
        $this->message = $message;

        return $message;
    }
}
