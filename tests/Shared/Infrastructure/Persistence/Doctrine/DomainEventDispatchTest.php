<?php

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\ProductImage\Event\ProductImageWasDeletedEvent;
use App\Review\Domain\Model\Review\Event\ReviewStatusWasChangedEvent;
use App\Tests\Shared\Factory\ProductImageFactory;
use App\Tests\Shared\Factory\ProductReviewFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Domain events raised by entities are dispatched by a real flush, through DomainEventDispatcher.
 */
final class DomainEventDispatchTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    /** @var list<object> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        \assert($eventDispatcher instanceof EventDispatcherInterface);
        foreach ([ReviewStatusWasChangedEvent::class, ProductImageWasDeletedEvent::class] as $eventName) {
            $eventDispatcher->addListener($eventName, function (object $event): void {
                $this->dispatched[] = $event;
            });
        }
    }

    public function testEventsAreNotDispatchedBeforeTheFlush(): void
    {
        $review = ProductReviewFactory::createOne();

        $review->approve(UserFactory::new()->asStaff()->create());

        self::assertSame([], $this->dispatched);
    }

    public function testFlushDispatchesEventsRaisedByAChangedEntity(): void
    {
        $review = ProductReviewFactory::createOne();

        $review->approve(UserFactory::new()->asStaff()->create());

        $this->em->flush();

        self::assertCount(1, $this->dispatched);
        $event = $this->dispatched[0];
        self::assertInstanceOf(ReviewStatusWasChangedEvent::class, $event);
        self::assertTrue($review->getPublicId()->equals($event->getId()));
    }

    public function testFlushDispatchesEventsRaisedByADeletedEntity(): void
    {
        $productImage = ProductImageFactory::createOne();
        $product = $productImage->getProduct();

        $productImage->delete();
        $this->em->remove($productImage);
        $this->em->flush();

        self::assertCount(1, $this->dispatched);
        $event = $this->dispatched[0];
        self::assertInstanceOf(ProductImageWasDeletedEvent::class, $event);
        self::assertTrue($product->getPublicId()->equals($event->id));
        self::assertSame($productImage->getImageName(), $event->getImageName());
    }

    public function testEventsAreDispatchedOnce(): void
    {
        $productImage = ProductImageFactory::createOne();

        $productImage->delete();

        $this->em->remove($productImage);
        $this->em->flush();
        $this->em->flush();

        self::assertCount(1, $this->dispatched);
    }
}
