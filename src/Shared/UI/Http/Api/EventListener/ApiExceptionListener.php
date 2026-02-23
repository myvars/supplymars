<?php

namespace App\Shared\UI\Http\Api\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -1)]
final readonly class ApiExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if ($event->getResponse() instanceof Response) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface && $exception->getPrevious() instanceof ValidationFailedException) {
            /** @var ValidationFailedException $validationException */
            $validationException = $exception->getPrevious();
            $violations = [];

            foreach ($validationException->getViolations() as $violation) {
                $violations[] = [
                    'propertyPath' => $violation->getPropertyPath(),
                    'title' => (string) $violation->getMessage(),
                ];
            }

            $event->setResponse($this->buildResponse(422, 'Validation Failed', 'One or more fields are invalid.', [
                'violations' => $violations,
            ]));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $event->setResponse($this->buildResponse(
                $status,
                Response::$statusTexts[$status] ?? 'Error',
                $exception->getMessage() ?: 'An error occurred.',
            ));

            return;
        }

        $detail = $this->environment === 'prod'
            ? 'An internal error occurred.'
            : $exception->getMessage();

        $event->setResponse($this->buildResponse(500, 'Internal Server Error', $detail));
    }

    /**
     * @param array<string, mixed> $extensions
     */
    private function buildResponse(int $status, string $title, string $detail, array $extensions = []): JsonResponse
    {
        return new JsonResponse([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            ...$extensions,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
