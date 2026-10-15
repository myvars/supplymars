<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\Controller;

use App\Shared\Infrastructure\Health\HealthCheckInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(
        #[AutowireIterator(HealthCheckInterface::class)]
        private iterable $checks,
        private LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/health', name: 'app_health', methods: ['GET'], stateless: true)]
    public function __invoke(): JsonResponse
    {
        $results = [];
        foreach ($this->checks as $check) {
            try {
                $check->check();
                $results[$check->name()] = 'ok';
            } catch (\Throwable $e) {
                // The route is public: the reason goes to the log, not the response.
                $this->logger->error('Health check failed: {check}', ['check' => $check->name(), 'exception' => $e]);
                $results[$check->name()] = 'fail';
            }
        }

        $healthy = !in_array('fail', $results, true);

        $response = new JsonResponse(
            ['status' => $healthy ? 'ok' : 'fail', 'checks' => $results],
            $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
