<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Controller;

use App\Shared\Infrastructure\Health\BackupFreshnessCheck;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Separate from /health, which the nginx healthcheck and the deploy depend on; only the uptime monitor calls this.
 */
final readonly class BackupHealthController
{
    public function __construct(
        private BackupFreshnessCheck $check,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/health/backup', name: 'app_health_backup', methods: ['GET'], stateless: true)]
    public function __invoke(): JsonResponse
    {
        try {
            $this->check->check();
            $result = 'ok';
        } catch (\Throwable $throwable) {
            // The route is public: the reason goes to the log, not the response.
            $this->logger->error('Health check failed: {check}', ['check' => $this->check->name(), 'exception' => $throwable]);
            $result = 'fail';
        }

        $response = new JsonResponse(
            ['status' => $result, 'checks' => [$this->check->name() => $result]],
            $result === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
