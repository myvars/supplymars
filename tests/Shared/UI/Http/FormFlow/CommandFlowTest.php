<?php

namespace App\Tests\Shared\UI\Http\FormFlow;

use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\CommandFlow;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CommandFlowTest extends TestCase
{
    private function newRequest(string $uri = '/order', string $method = 'POST'): Request
    {
        $request = Request::create($uri, $method);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function handlerOk(?string $msg = 'Done', ?RedirectTarget $rt = null): callable
    {
        return fn (object $cmd): Result => Result::ok($msg, redirect: $rt);
    }

    private function handlerFail(?string $msg = 'Failed'): callable
    {
        return fn (object $cmd): Result => Result::fail($msg);
    }

    public function testProcessSuccessAddsSuccessFlashAndRedirectsToSuccessRoute(): void
    {
        $request = $this->newRequest();
        $context = FlowContext::forCreate('OrderItem'); // successRoute: app_order_item_index

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())
            ->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_order_item_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())
            ->method('to')
            ->with($request, '/gen/app_order_item_index', false, 303)
            ->willReturn(new Response('', 303));

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);

        $response = $flow->process($request, new \stdClass(), $this->handlerOk('Saved'), $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Saved'], $request->getSession()->getFlashBag()->get('success'));
        self::assertEmpty($request->getSession()->getFlashBag()->get('danger'));
    }

    public function testProcessFailureAddsErrorFlashAndRedirectsToSuccessRoute(): void
    {
        $request = $this->newRequest();
        $context = FlowContext::forCreate('OrderItem');

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())
            ->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_order_item_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())
            ->method('to')
            ->with($request, '/gen/app_order_item_index', false, 303)
            ->willReturn(new Response('', 303));

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);

        $response = $flow->process($request, new \stdClass(), $this->handlerFail('Failed'), $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Failed'], $request->getSession()->getFlashBag()->get('danger'));
        self::assertEmpty($request->getSession()->getFlashBag()->get('success'));
    }

    public function testProcessSuccessWithRedirectTargetOverridesSuccessRoute(): void
    {
        $request = $this->newRequest();
        $context = FlowContext::forCreate('OrderItem'); // should be ignored due to redirect target

        $target = new RedirectTarget('app_order_item_show', ['id' => 5], true, 302);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())
            ->method('generate')
            ->with('app_order_item_show', ['id' => 5])
            ->willReturn('/gen/app_order_item_show?id=5');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())
            ->method('to')
            ->with($request, '/gen/app_order_item_show?id=5', true, 302)
            ->willReturn(new Response('', 302));

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);

        $response = $flow->process($request, new \stdClass(), $this->handlerOk('Shown', $target), $context);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame(['Shown'], $request->getSession()->getFlashBag()->get('success'));
    }

    public function testProcessUsesContextRedirectOptions(): void
    {
        $request = $this->newRequest();
        $context = FlowContext::forCreate('OrderItem')->redirectOptions(true, 307);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())
            ->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_order_item_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())
            ->method('to')
            ->with($request, '/gen/app_order_item_index', true, 307)
            ->willReturn(new Response('', 307));

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);

        $response = $flow->process($request, new \stdClass(), $this->handlerOk('Updated'), $context);

        self::assertSame(307, $response->getStatusCode());
        self::assertSame(['Updated'], $request->getSession()->getFlashBag()->get('success'));
    }

    public function testProcessSuccessWithNullMessageDoesNotFlash(): void
    {
        $request = $this->newRequest();
        $context = FlowContext::forCreate('OrderItem');

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())
            ->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_order_item_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())
            ->method('to')
            ->with($request, '/gen/app_order_item_index', false, 303)
            ->willReturn(new Response('', 303));

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);

        $response = $flow->process($request, new \stdClass(), $this->handlerOk(null), $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertEmpty($request->getSession()->getFlashBag()->get('success'));
        self::assertEmpty($request->getSession()->getFlashBag()->get('danger'));
    }

    public function testProcessThrowsWhenSuccessRouteNotConfigured(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Success route not configured.');

        $request = $this->newRequest();
        $context = FlowContext::new(); // No success route

        $urls = $this->createStub(UrlGeneratorInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);
        $flow->process($request, new \stdClass(), $this->handlerOk(), $context);
    }

    public function testProcessWithForCommandFactoryWorks(): void
    {
        $request = $this->newRequest();
        $context = FlowContext::forCommand('app_order_show', ['id' => 1]);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())
            ->method('generate')
            ->with('app_order_show', ['id' => 1])
            ->willReturn('/gen/app_order_show?id=1');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())
            ->method('to')
            ->with($request, '/gen/app_order_show?id=1', false, 303)
            ->willReturn(new Response('', 303));

        $flow = new CommandFlow(new FlashMessenger(), $redirector, $urls);

        $response = $flow->process($request, new \stdClass(), $this->handlerOk('Processed'), $context);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Processed'], $request->getSession()->getFlashBag()->get('success'));
    }
}
