<?php

namespace App\Tests\Shared\UI\Http\FormFlow;

use App\Shared\Application\Result;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use App\Shared\UI\Http\FormFlow\View\FlowRoutes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final class DeleteFlowTest extends TestCase
{
    private function newRequest(string $uri = '/order-item/5/delete', string $method = 'GET'): Request
    {
        $r = Request::create($uri, $method);
        $r->setSession(new Session(new MockArraySessionStorage()));

        return $r;
    }

    private function getFlashBag(Request $request): FlashBagInterface
    {
        $session = $request->getSession();
        assert($session instanceof FlashBagAwareSessionInterface);

        return $session->getFlashBag();
    }

    public function testDeleteConfirmRendersBaseTemplate(): void
    {
        $entity = (object) ['id' => 5, 'name' => 'Test'];
        $model = FlowModel::simple('order_item');
        $context = FlowContext::forDelete($model);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')
            ->with(
                FlowModel::BASE_TEMPLATE,
                $this->callback(fn (array $vars): bool => $vars['result'] === $entity
                    && $vars['flowOperation'] === $context->getOperation()->value
                    && $vars['flowModel'] === 'Order Item'
                    && $vars['routes'] instanceof FlowRoutes
                    && $vars['routes']->delete === 'app_order_item_delete')
            )
            ->willReturn('<html>confirm</html>');

        $flashes = new FlashMessenger();
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);
        $urls = $this->createStub(UrlGeneratorInterface::class);
        $commandFlow = new CommandFlow($flashes, $redirector, $urls);

        $flow = new DeleteFlow($twig, $flashes, $csrf, $commandFlow);

        $response = $flow->deleteConfirm($entity, $context);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>confirm</html>', $response->getContent());
    }

    public function testDeleteInvalidCsrfAddsErrorFlashAndRedirects(): void
    {
        $request = $this->newRequest(method: 'POST');
        $request->request->set('_token', 'bad-token');

        $command = (object) ['id' => 5];
        $model = FlowModel::simple('order_item');
        $context = FlowContext::forDelete($model);

        $twig = $this->createStub(Environment::class);
        $flashes = new FlashMessenger();

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects($this->once())->method('isTokenValid')
            ->with($this->callback(fn (CsrfToken $t): bool => $t->getId() === 'delete5' && $t->getValue() === 'bad-token'))
            ->willReturn(false);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_order_item_index', [])
            ->willReturn('/gen/app_order_item_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_order_item_index', true, 303)
            ->willReturn(new Response('', 303));

        $commandFlow = new CommandFlow($flashes, $redirector, $urls);
        $flow = new DeleteFlow($twig, $flashes, $csrf, $commandFlow);

        $response = $flow->delete($request, $command, fn (): null => null, $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Invalid CSRF token.'], $this->getFlashBag($request)->get('danger'));
        self::assertEmpty($this->getFlashBag($request)->get('success'));
    }

    public function testDeleteValidCsrfDelegatesToCommandFlowProcess(): void
    {
        $request = $this->newRequest(method: 'POST');
        $request->request->set('_token', 'good-token');

        $command = (object) ['id' => 9];
        $model = FlowModel::simple('order_item');
        $context = FlowContext::forDelete($model);

        $twig = $this->createStub(Environment::class);
        $flashes = new FlashMessenger();

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects($this->once())->method('isTokenValid')
            ->with($this->callback(fn (CsrfToken $t): bool => $t->getId() === 'delete9' && $t->getValue() === 'good-token'))
            ->willReturn(true);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_order_item_index', [])
            ->willReturn('/gen/app_order_item_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_order_item_index', true, 303)
            ->willReturn(new Response('', 303));

        $handler = fn (object $cmd): Result => Result::ok('Deleted');

        $commandFlow = new CommandFlow($flashes, $redirector, $urls);
        $flow = new DeleteFlow($twig, $flashes, $csrf, $commandFlow);

        $response = $flow->delete($request, $command, $handler, $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Deleted'], $this->getFlashBag($request)->get('success'));
        self::assertEmpty($this->getFlashBag($request)->get('danger'));
    }
}
