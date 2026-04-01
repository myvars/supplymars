<?php

namespace App\Order\Application\Handler;

use App\Order\Application\Service\DemoOrderCreator;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\Result;
use Psr\Log\LoggerInterface;

final readonly class CreateDemoOrderHandler
{
    public const int DAILY_LIMIT = 10;

    public function __construct(
        private OrderRepository $orders,
        private DemoOrderCreator $demoOrderCreator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): Result
    {
        if ($this->orders->countDemoOrdersCreatedToday() >= self::DAILY_LIMIT) {
            return Result::fail('Daily demo order limit reached.');
        }

        try {
            $result = $this->demoOrderCreator->create('DEMO-');
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed to create demo order', [
                'error' => $throwable->getMessage(),
            ]);

            return Result::fail('Failed to create demo order: ' . $throwable->getMessage());
        }

        return Result::ok(
            message: sprintf('Demo order #%06d created.', $result->order->getId()),
            payload: $result->order->getPublicId(),
        );
    }
}
