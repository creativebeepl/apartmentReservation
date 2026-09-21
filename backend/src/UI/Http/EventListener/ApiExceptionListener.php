<?php

declare(strict_types=1);

namespace App\UI\Http\EventListener;

use App\Domain\Exception\DomainConflictException;
use App\Domain\Exception\DomainErrorInterface;
use App\Domain\Exception\DomainNotFoundException;
use App\Domain\Exception\DomainRuleViolationException;
use App\UI\Http\Response\ApiErrorResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/** Zamienia wyjątki na jednolite odpowiedzi JSON dla ścieżek /api. */
#[AsEventListener(event: 'kernel.exception')]
final readonly class ApiExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (! str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $event->setResponse($this->toResponse($event->getThrowable()));
    }

    private function toResponse(\Throwable $e): JsonResponse
    {
        $validation = $e instanceof ValidationFailedException ? $e : $e->getPrevious();
        if ($validation instanceof ValidationFailedException) {
            return ApiErrorResponse::create(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'validation_failed',
                'Validation failed.',
                $this->groupViolations($validation),
            );
        }

        if ($e instanceof DomainErrorInterface) {
            return ApiErrorResponse::create($this->statusFor($e), $e->errorCode(), $e->getMessage());
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            return ApiErrorResponse::create($status, $this->codeFor($status), $e->getMessage(), null, $e->getHeaders());
        }

        $this->logger->error('Unhandled API exception: ' . $e->getMessage(), ['exception' => $e]);

        return ApiErrorResponse::create(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'server_error',
            $this->debug ? $e->getMessage() : 'Internal server error.',
        );
    }

    private function statusFor(DomainErrorInterface $e): int
    {
        return match (true) {
            $e instanceof DomainNotFoundException => Response::HTTP_NOT_FOUND,
            $e instanceof DomainConflictException => Response::HTTP_CONFLICT,
            $e instanceof DomainRuleViolationException => Response::HTTP_UNPROCESSABLE_ENTITY,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    private function codeFor(int $status): string
    {
        $text = Response::$statusTexts[$status] ?? 'error';

        return strtolower(str_replace([' ', '-'], '_', $text));
    }

    /** @return array<string, list<string>> */
    private function groupViolations(ValidationFailedException $e): array
    {
        $converter = new CamelCaseToSnakeCaseNameConverter();
        $errors = [];

        foreach ($e->getViolations() as $violation) {
            $field = $converter->normalize($violation->getPropertyPath());
            $errors[$field][] = (string) $violation->getMessage();
        }

        return $errors;
    }
}
