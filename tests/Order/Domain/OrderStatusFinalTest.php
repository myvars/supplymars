<?php

declare(strict_types=1);

namespace App\Tests\Order\Domain;

use App\Order\Domain\Model\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * Status change log retention deletes the history of entities in a final status, so a status counts as final
 * only when nothing can follow it.
 */
class OrderStatusFinalTest extends TestCase
{
    public function testOrderStatusIsFinalWhenItHasNoTransitions(): void
    {
        foreach (OrderStatus::cases() as $from) {
            $hasTransition = array_any(OrderStatus::cases(), fn (OrderStatus $to): bool => $from->canTransitionTo($to));

            self::assertSame(!$hasTransition, $from->isFinal(), $from->value);
        }
    }
}
