<?php

declare(strict_types=1);

namespace App\Order\Application\Listener;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Application\Message\NotifyOrderLockReleased;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\Event\OrderLockWasChangedEvent;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\UserNotifierInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tells a lock's holder, on whatever page they have open, that another user released it: the simulation skips a
 * locked order, so it may now pick the order up. Nothing is sent for taking a lock, or for releasing
 * your own. Sent through the queue, so no request calls the hub.
 *
 * No console command changes a lock today. If one does, it acts as the default staff user: decide then whether
 * a release by the system should notify, rather than sending "admin released your lock".
 */
final readonly class NotifyLockHolderOfRelease
{
    public function __construct(
        private MessageBusInterface $bus,
        private OrderRepository $orders,
        private UserRepository $users,
        private UserNotifierInterface $notifier,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[AsEventListener]
    public function onLockChanged(OrderLockWasChangedEvent $event): void
    {
        $holderId = $event->getPreviousHolderId();
        if (!$holderId instanceof UserPublicId || !$event->isReleasedByAnotherUser()) {
            return;
        }

        $this->bus->dispatch(new NotifyOrderLockReleased(
            $event->getId()->value(),
            $holderId->value(),
            $event->getChangedById()->value(),
        ));
    }

    #[AsMessageHandler]
    public function notify(NotifyOrderLockReleased $message): void
    {
        $order = $this->orders->getByPublicId(OrderPublicId::fromString($message->orderId));
        $releasedBy = $this->users->getByPublicId(UserPublicId::fromString($message->releasedById));
        if (!$order instanceof CustomerOrder || !$releasedBy instanceof User) {
            return;
        }

        $this->notifier->notify(
            userId: $message->holderId,
            type: 'info',
            // The order number as the order pages show it
            message: sprintf('%s released your lock on order #%06d', $releasedBy->getFullName() ?? $releasedBy->getUserIdentifier(), $order->getId()),
            url: $this->urlGenerator->generate('app_order_show', ['id' => $message->orderId]),
        );
    }
}
