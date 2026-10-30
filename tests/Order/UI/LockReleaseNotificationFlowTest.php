<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Customer\Domain\Model\User\User;
use App\Order\Application\Message\RefreshOrderPage;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Mercure\QueuedNotifications;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Update;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * Releasing another user's lock tells the holder, wherever they are (NotifyLockHolderOfRelease). The queue is
 * handled as the worker would; redirects are not followed, so the queue and hub are this request's.
 */
final class LockReleaseNotificationFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testReleasingAnotherUsersLockNotifiesTheHolder(): void
    {
        $holder = UserFactory::new()->asStaff()->create();
        $order = $this->orderLockedBy($holder);

        $this->toggleLockAs(UserFactory::new()->asStaff()->create(['fullName' => 'Sam Carter']), $order);
        QueuedNotifications::handle(self::getContainer());

        $toasts = $this->updatesOn('user/' . $holder->getPublicId());
        self::assertCount(1, $toasts);
        self::assertTrue($toasts[0]->isPrivate());
        self::assertStringContainsString('<turbo-stream action="append" target="flash-container">', $toasts[0]->getData());
        self::assertStringContainsString(sprintf('Sam Carter released your lock on order #%06d', $order->getId()), $toasts[0]->getData());
        self::assertMatchesRegularExpression('#<a\s+href="/order/' . $order->getPublicId()->value() . '"[^>]*>View</a>#', $toasts[0]->getData());
        // The holder's open order page refreshes too, as everyone else's does
        self::assertCount(1, $this->updatesOn('order/' . $order->getPublicId()->value()));
    }

    public function testTakingALockNotifiesNobody(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->toggleLockAs(UserFactory::new()->asStaff()->create(), $order);

        $this->assertOnlyARefreshWasQueued();
    }

    public function testReleasingYourOwnLockNotifiesNobody(): void
    {
        $holder = UserFactory::new()->asStaff()->create();
        $order = $this->orderLockedBy($holder);

        $this->toggleLockAs($holder, $order);

        $this->assertOnlyARefreshWasQueued();
    }

    public function testHubFailureKeepsTheReleaseAndDoesNotFailTheMessage(): void
    {
        $order = $this->orderLockedBy(UserFactory::new()->asStaff()->create());

        $this->toggleLockAs(UserFactory::new()->asStaff()->create(), $order);
        QueuedNotifications::hub(self::getContainer())->failWith(new RuntimeException('Hub is down'));

        // Throws HandlerFailedException if a handler let the failure through
        $handled = QueuedNotifications::handle(self::getContainer());

        self::assertCount(2, $handled);
        self::assertSame([], QueuedNotifications::published(self::getContainer()));
        self::assertNull(CustomerOrderFactory::repository()->find($order->getId())?->getOrderLock());
    }

    private function orderLockedBy(User $holder): CustomerOrder
    {
        $order = CustomerOrderFactory::createOne();
        $order->lockOrder($holder, $holder);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        return $order;
    }

    private function toggleLockAs(User $user, CustomerOrder $order): void
    {
        $this->browser()
            ->actingAs($user)
            ->visit('/order/' . $order->getPublicId()->value())
            ->interceptRedirects()
            ->click('button[data-order-lock]')
            ->assertRedirected();
    }

    private function assertOnlyARefreshWasQueued(): void
    {
        $handled = QueuedNotifications::handle(self::getContainer());

        self::assertCount(1, $handled);
        self::assertInstanceOf(RefreshOrderPage::class, $handled[0]);
        self::assertSame([], array_filter(
            QueuedNotifications::published(self::getContainer()),
            static fn (Update $update): bool => str_starts_with($update->getTopics()[0], 'user/'),
        ));
    }

    /** @return list<Update> */
    private function updatesOn(string $topic): array
    {
        return array_values(array_filter(
            QueuedNotifications::published(self::getContainer()),
            static fn (Update $update): bool => $update->getTopics() === [$topic],
        ));
    }
}
