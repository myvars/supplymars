<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Applies the rules in app.rate_limits (config/packages/rate_limiter.yaml). Runs before the router
 * (priority 32) and the firewall, so a refused request does no other work.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 48)]
final readonly class RateLimitListener
{
    private const array LOOPBACK = ['127.0.0.1', '::1'];

    /**
     * @param list<array{path: string, limiter: string, methods?: list<string>, exempt_loopback?: bool}> $rules
     * @param ServiceLocator<RateLimiterFactoryInterface>                                                $limiters
     */
    public function __construct(
        #[Autowire(param: 'app.rate_limits')]
        private array $rules,
        #[AutowireLocator('rate_limiter', indexAttribute: 'name')]
        private ServiceLocator $limiters,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $clientIp = $request->getClientIp() ?? '';

        foreach ($this->rules as $rule) {
            if (preg_match('{' . $rule['path'] . '}', $request->getPathInfo()) !== 1
                || (isset($rule['methods']) && !in_array($request->getMethod(), $rule['methods'], true))
                || (($rule['exempt_loopback'] ?? false) && in_array($clientIp, self::LOOPBACK, true))) {
                continue;
            }

            $limit = $this->limiters->get($rule['limiter'])->create($clientIp)->consume();

            if (!$limit->isAccepted()) {
                $event->setResponse(new Response('Too many requests. Try again later.', Response::HTTP_TOO_MANY_REQUESTS, [
                    'Retry-After' => (string) max(1, $limit->getRetryAfter()->getTimestamp() - time()),
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]));

                return;
            }
        }
    }
}
