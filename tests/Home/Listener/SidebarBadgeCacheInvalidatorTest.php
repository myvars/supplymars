<?php

declare(strict_types=1);

namespace App\Tests\Home\Listener;

use App\Catalog\Domain\Model\Product\ProductPublicId;
use App\Home\Listener\SidebarBadgeCacheInvalidator;
use App\Home\Service\SidebarBadgeProvider;
use App\Review\Domain\Model\Review\Event\ReviewWasDeletedEvent;
use App\Review\Domain\Model\Review\ReviewStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SidebarBadgeCacheInvalidatorTest extends TestCase
{
    public function testDeletingPendingReviewClearsPendingReviewBadge(): void
    {
        $cache = $this->cacheWithPendingReviewCount();

        new SidebarBadgeCacheInvalidator($cache)->onReviewDeleted(
            new ReviewWasDeletedEvent(ProductPublicId::new(), ReviewStatus::PENDING)
        );

        self::assertFalse($cache->hasItem(SidebarBadgeProvider::KEY_PENDING_REVIEWS));
    }

    public function testDeletingPublishedReviewKeepsPendingReviewBadge(): void
    {
        $cache = $this->cacheWithPendingReviewCount();

        new SidebarBadgeCacheInvalidator($cache)->onReviewDeleted(
            new ReviewWasDeletedEvent(ProductPublicId::new(), ReviewStatus::PUBLISHED)
        );

        self::assertTrue($cache->hasItem(SidebarBadgeProvider::KEY_PENDING_REVIEWS));
    }

    private function cacheWithPendingReviewCount(): ArrayAdapter
    {
        $cache = new ArrayAdapter();
        $cache->get(SidebarBadgeProvider::KEY_PENDING_REVIEWS, fn (): int => 3);

        return $cache;
    }
}
