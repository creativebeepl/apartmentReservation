<?php

/**
 * Pomocniczy proces testu współbieżności: uruchamia prawdziwe jądro aplikacji, czeka do wspólnej
 * chwili startu i wysyła jedno żądanie POST /api/bookings. Na stdout wypisuje kod HTTP odpowiedzi.
 *
 * Użycie: php booking_worker.php <token> <json-payload> <start-timestamp-float>
 */

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__, 2) . '/.env');

[, $token, $payload, $startAt] = $argv;

$kernel = new Kernel('test', true);
$kernel->boot();

// Wszystkie procesy ruszają w tej samej chwili, żeby maksymalnie zwiększyć szansę na wyścig.
while (microtime(true) < (float) $startAt) {
    usleep(200);
}

$request = Request::create('/api/bookings', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
], $payload);

echo $kernel->handle($request)->getStatusCode();
