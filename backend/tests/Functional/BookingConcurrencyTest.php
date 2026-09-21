<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Entity\Booking;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Process\Process;

/**
 * Prawdziwa współbieżność: osobne procesy PHP uderzają jednocześnie w ten sam plik SQLite.
 */
final class BookingConcurrencyTest extends ApiTestCase
{
    private const PROCESSES = 6;

    public function testOnlyOneOfManyParallelRequestsForTheSameSlotSucceeds(): void
    {
        $resource = $this->createResource();
        $payload = [
            'resource_id' => (string) $resource->id(),
            'start_at' => $this->iso('+10 days 10:00'),
            'end_at' => $this->iso('+10 days 14:00'),
            'customer_name' => 'Concurrent',
        ];

        $statuses = $this->runParallel(array_fill(0, self::PROCESSES, $payload));

        self::assertSame(1, $this->countStatus($statuses, '201'), 'Exactly one request may win: ' . json_encode($statuses));
        self::assertSame(self::PROCESSES - 1, $this->countStatus($statuses, '409'), 'The rest must get 409: ' . json_encode($statuses));
        self::assertSame(1, $this->em()->getRepository(Booking::class)->count([]), 'No double booking in the database.');
    }

    public function testParallelRequestsForDifferentSlotsAllSucceedWithoutLockErrors(): void
    {
        $resource = $this->createResource();
        $payloads = [];
        for ($i = 0; $i < self::PROCESSES; ++$i) {
            $payloads[] = [
                'resource_id' => (string) $resource->id(),
                'start_at' => $this->iso(sprintf('+%d days 10:00', 10 + $i * 2)),
                'end_at' => $this->iso(sprintf('+%d days 10:00', 11 + $i * 2)),
                'customer_name' => 'Client ' . $i,
            ];
        }

        $statuses = $this->runParallel($payloads);

        self::assertSame(self::PROCESSES, $this->countStatus($statuses, '201'), 'All should succeed: ' . json_encode($statuses));
        self::assertSame(self::PROCESSES, $this->em()->getRepository(Booking::class)->count([]));
    }

    /**
     * @param list<array<string, string>> $payloads
     * @return list<string> kody HTTP w kolejności uruchomienia
     */
    private function runParallel(array $payloads): array
    {
        $script = dirname(__DIR__) . '/Support/booking_worker.php';
        $startAt = microtime(true) + 3.0;
        $processes = [];

        foreach ($payloads as $payload) {
            $process = new Process(
                [PHP_BINARY, $script, $this->token, json_encode($payload, JSON_THROW_ON_ERROR), (string) $startAt],
                dirname(__DIR__, 2),
                ['APP_ENV' => 'test'],
            );
            $process->start();
            $processes[] = $process;
        }

        $statuses = [];
        foreach ($processes as $process) {
            $process->wait();
            $statuses[] = trim($process->getOutput()) !== '' ? trim($process->getOutput()) : 'ERR: ' . $process->getErrorOutput();
        }

        return $statuses;
    }

    /** @param list<string> $statuses */
    private function countStatus(array $statuses, string $status): int
    {
        return count(array_filter($statuses, static fn (string $s): bool => $s === $status));
    }
}
