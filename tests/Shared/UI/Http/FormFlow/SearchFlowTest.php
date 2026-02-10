<?php

namespace App\Tests\Shared\UI\Http\FormFlow;

use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\Paginator;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\SearchFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use App\Shared\UI\Http\FormFlow\View\FlowRoutes;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class SearchFlowTest extends TestCase
{
    /**
     * @param array<string, mixed> $query
     */
    private function newRequest(string $uri = '/order-item', array $query = []): Request
    {
        $r = Request::create($uri, 'GET', $query);
        $r->setSession(new Session(new MockArraySessionStorage()));

        return $r;
    }

    private function getFlashBag(Request $request): FlashBagInterface
    {
        $session = $request->getSession();
        assert($session instanceof FlashBagAwareSessionInterface);

        return $session->getFlashBag();
    }

    private function criteria(int $page, int $limit): SearchCriteriaInterface
    {
        return new TestSearchCriteria($page, $limit);
    }

    /**
     * @param array<int, mixed> $items
     */
    private function repository(array $items): FindByCriteriaInterface
    {
        return new readonly class($items) implements FindByCriteriaInterface {
            /** @param array<int, mixed> $items */
            public function __construct(private array $items)
            {
            }

            public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
            {
                return new ArrayAdapter($this->items);
            }
        };
    }

    public function testSearchRendersTemplateWithResults(): void
    {
        $request = $this->newRequest();
        $criteria = $this->criteria(1, 2);
        $repository = $this->repository(['A', 'B', 'C', 'D', 'E']);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')
            ->with(
                'shared/form_flow/base.html.twig',
                $this->callback(fn (array $vars): bool => $vars['flowModel'] === 'Order Item'
                    && $vars['flowOperation'] === 'index'
                    && $vars['template'] === 'order_item/index.html.twig'
                    && $vars['routes'] instanceof FlowRoutes
                    && $vars['routes']->index === 'app_order_item_index'
                    && $vars['results'] instanceof Pagerfanta)
            )->willReturn('<html>OK</html>');

        $redirector = $this->createStub(RedirectorInterface::class);
        $urls = $this->createStub(UrlGeneratorInterface::class);

        $flow = new SearchFlow(new Paginator(), $twig, new FlashMessenger(), $redirector, $urls);

        $response = $flow->search($request, $repository, $criteria, FlowContext::forSearch(FlowModel::simple('order_item')));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>OK</html>', $response->getContent());
        self::assertEmpty($this->getFlashBag($request)->get('warning'));
    }

    public function testSearchOutOfRangeRedirectsWithWarningFlash(): void
    {
        $request = $this->newRequest('/order-item', ['foo' => 'bar']);
        $criteria = $this->criteria(99, 2);
        $repository = $this->repository(['A', 'B', 'C', 'D', 'E']);

        $twig = $this->createStub(Environment::class);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_order_item_index', ['foo' => 'bar', 'page' => TestSearchCriteria::PAGE_DEFAULT])
            ->willReturn('/gen/app_order_item_index?foo=bar&page=1');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_order_item_index?foo=bar&page=1')
            ->willReturn(new Response('', 303));

        $flow = new SearchFlow(new Paginator(), $twig, new FlashMessenger(), $redirector, $urls);

        $response = $flow->search($request, $repository, $criteria, FlowContext::forSearch(FlowModel::simple('order_item')));

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Page 99 not found.'], $this->getFlashBag($request)->get('warning'));
    }

    public function testSearchThrowsWhenModelNotConfigured(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Model not configured.');

        $request = $this->newRequest();
        $context = FlowContext::new();
        $criteria = $this->criteria(1, 10);
        $repository = $this->repository([]);

        $twig = $this->createStub(Environment::class);
        $urls = $this->createStub(UrlGeneratorInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);

        $flow = new SearchFlow(new Paginator(), $twig, new FlashMessenger(), $redirector, $urls);
        $flow->search($request, $repository, $criteria, $context);
    }
}
