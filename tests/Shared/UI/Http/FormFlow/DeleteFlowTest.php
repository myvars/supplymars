<?php

namespace App\Tests\Shared\UI\Http\FormFlow;

use App\Shared\Application\Result;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\DeleteFlow;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\ModelPath;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    public function testDeleteConfirmRendersBaseTemplate(): void
    {
        $entity = (object) ['id' => 5, 'name' => 'Test'];
        $context = FlowContext::forDelete('OrderItem');

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')
            ->with(
                ModelPath::BASE_TEMPLATE,
                $this->callback(fn (array $vars): bool => $vars['result'] === $entity
                    && $vars['flowOperation'] === $context->getOperation()->value
                    && $vars['flowModel'] === 'OrderItem')
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
        $context = FlowContext::forDelete('OrderItem');

        $twig = $this->createStub(Environment::class);
        $flashes = new FlashMessenger();

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects($this->once())->method('isTokenValid')
            ->with($this->callback(fn (CsrfToken $t): bool => $t->getId() === 'delete5' && $t->getValue() === 'bad-token'))
            ->willReturn(false);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_orderitem_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_orderitem_index', false, 303)
            ->willReturn(new Response('', 303));

        $commandFlow = new CommandFlow($flashes, $redirector, $urls);
        $flow = new DeleteFlow($twig, $flashes, $csrf, $commandFlow);

        $response = $flow->delete($request, $command, fn (): null => null, $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Invalid CSRF token.'], $request->getSession()->getFlashBag()->get('danger'));
        self::assertEmpty($request->getSession()->getFlashBag()->get('success'));
    }

    public function testDeleteValidCsrfDelegatesToCommandFlowProcess(): void
    {
        $request = $this->newRequest(method: 'POST');
        $request->request->set('_token', 'good-token');

        $command = (object) ['id' => 9];
        $context = FlowContext::forDelete('OrderItem');

        $twig = $this->createStub(Environment::class);
        $flashes = new FlashMessenger();

        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects($this->once())->method('isTokenValid')
            ->with($this->callback(fn (CsrfToken $t): bool => $t->getId() === 'delete9' && $t->getValue() === 'good-token'))
            ->willReturn(true);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_orderitem_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_orderitem_index', false, 303)
            ->willReturn(new Response('', 303));

        $handler = fn (object $cmd): Result => Result::ok('Deleted');

        $commandFlow = new CommandFlow($flashes, $redirector, $urls);
        $flow = new DeleteFlow($twig, $flashes, $csrf, $commandFlow);

        $response = $flow->delete($request, $command, $handler, $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Deleted'], $request->getSession()->getFlashBag()->get('success'));
        self::assertEmpty($request->getSession()->getFlashBag()->get('danger'));
    }
}
