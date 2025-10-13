<?php

namespace App\Tests\Shared\UI\Http\FormFlow;

use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use App\Shared\UI\Http\FlashMessenger;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\Guard\AutoUpdateGuard;
use App\Shared\UI\Http\FormFlow\Redirect\RedirectorInterface;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class FormFlowTest extends TestCase
{
    private function newRequest(string $uri, string $method): Request
    {
        $request = Request::create($uri, $method);
        $request->setSession(new Session(new MockArraySessionStorage()));
        return $request;
    }

    private function formMock(bool $submitted, bool $valid, mixed $data): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('handleRequest')->with($this->isInstanceOf(Request::class));
        $form->method('isSubmitted')->willReturn($submitted);
        $form->method('isValid')->willReturn($valid);
        $form->method('getData')->willReturn($data);
        $form->method('createView')->willReturn(new FormView());
        return $form;
    }

    private function mapper(): callable
    {
        return fn(mixed $d) => (object)['mapped' => $d];
    }

    private function handlerOk(string $msg = 'Saved', ?RedirectTarget $rt = null): callable
    {
        return fn(object $cmd) => Result::ok($msg, payload: null, redirect: $rt);
    }

    private function handlerFail(string $msg = 'Failed'): callable
    {
        return fn(object $cmd) => Result::fail($msg);
    }

    public function testGetRequestRendersTemplateOk(): void
    {
        $request = $this->newRequest('/order/new', 'GET');
        $form = $this->formMock(false, true, null);

        $forms = $this->createMock(FormFactoryInterface::class);
        $forms->expects($this->once())->method('create')
            ->with('FormType', $this->equalTo([]), $this->callback(fn($opts) => $opts['action'] === $request->getUri()))
            ->willReturn($form);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')
            ->with($this->equalTo('shared/form_flow/base.html.twig'), $this->arrayHasKey('form'))
            ->willReturn('<html>GET</html>');

        $urls = $this->createStub(UrlGeneratorInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);
        $autoUpdate = $this->createStub(AutoUpdateGuard::class);
        $autoUpdate->method('is')->willReturn(false);

        $flow = new FormFlow($forms, new FlashMessenger(), $twig, $urls, $redirector, $autoUpdate);
        $ctx = FlowContext::forCreate('OrderItem');

        $response = $flow->form($request, 'FormType', [], $this->mapper(), $this->handlerOk(), $ctx);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>GET</html>', $response->getContent());
        self::assertEmpty($request->getSession()->getFlashBag()->get('success'));
    }

    public function testSuccessfulPostRedirectsAndFlashes(): void
    {
        $request = $this->newRequest('/order/new', 'POST');
        $form = $this->formMock(true, true, ['x' => 1]);

        $forms = $this->createStub(FormFactoryInterface::class);
        $forms->method('create')->willReturn($form);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_orderitem_index', [])
            ->willReturn('/gen/app_orderitem_index');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_orderitem_index', false, 303)
            ->willReturn(new Response('', 303));

        $autoUpdate = $this->createStub(AutoUpdateGuard::class);
        $autoUpdate->method('is')->willReturn(false);

        $flow = new FormFlow($forms, new FlashMessenger(), $this->createStub(Environment::class), $urls, $redirector, $autoUpdate);
        $ctx = FlowContext::forCreate('OrderItem');

        $response = $flow->form($request, 'FormType', [], $this->mapper(), $this->handlerOk('Saved'), $ctx);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(['Saved'], $request->getSession()->getFlashBag()->get('success'));
    }

    public function testSuccessfulPostWithRedirectTargetOverridesSuccessRoute(): void
    {
        $request = $this->newRequest('/order/edit', 'POST');
        $form = $this->formMock(true, true, ['id' => 5]);

        $forms = $this->createStub(FormFactoryInterface::class);
        $forms->method('create')->willReturn($form);

        $rt = new RedirectTarget('app_orderitem_show', ['id' => 5], true, 307);

        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')
            ->with('app_orderitem_show', ['id' => 5])
            ->willReturn('/gen/app_orderitem_show?id=5');

        $redirector = $this->createMock(RedirectorInterface::class);
        $redirector->expects($this->once())->method('to')
            ->with($request, '/gen/app_orderitem_show?id=5', true, 307)
            ->willReturn(new Response('', 307));

        $autoUpdate = $this->createStub(AutoUpdateGuard::class);
        $autoUpdate->method('is')->willReturn(false);

        $flow = new FormFlow($forms, new FlashMessenger(), $this->createStub(Environment::class), $urls, $redirector, $autoUpdate);
        $ctx = FlowContext::forUpdate('OrderItem');

        $response = $flow->form($request, 'FormType', [], $this->mapper(), $this->handlerOk('Updated', $rt), $ctx);

        self::assertSame(307, $response->getStatusCode());
        self::assertSame(['Updated'], $request->getSession()->getFlashBag()->get('success'));
    }

    public function testInvalidPostRenders422WithoutErrorFlash(): void
    {
        $request = $this->newRequest('/order/new', 'POST');
        $form = $this->formMock(true, false, []);

        $forms = $this->createStub(FormFactoryInterface::class);
        $forms->method('create')->willReturn($form);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->willReturn('<html>422</html>');

        $urls = $this->createStub(UrlGeneratorInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);
        $autoUpdate = $this->createStub(AutoUpdateGuard::class);
        $autoUpdate->method('is')->willReturn(false);

        $flow = new FormFlow($forms, new FlashMessenger(), $twig, $urls, $redirector, $autoUpdate);
        $ctx = FlowContext::forCreate('OrderItem');

        $response = $flow->form($request, 'FormType', [], $this->mapper(), $this->handlerFail(), $ctx);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame('<html>422</html>', $response->getContent());
        self::assertEmpty($request->getSession()->getFlashBag()->get('danger'));
    }

    public function testValidPostHandlerFailureRenders422AndErrorFlash(): void
    {
        $request = $this->newRequest('/order/new', 'POST');
        $form = $this->formMock(true, true, ['x' => 1]);

        $forms = $this->createStub(FormFactoryInterface::class);
        $forms->method('create')->willReturn($form);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->willReturn('<html>422</html>');

        $urls = $this->createStub(UrlGeneratorInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);
        $autoUpdate = $this->createStub(AutoUpdateGuard::class);
        $autoUpdate->method('is')->willReturn(false);

        $flow = new FormFlow($forms, new FlashMessenger(), $twig, $urls, $redirector, $autoUpdate);
        $ctx = FlowContext::forCreate('OrderItem');

        $response = $flow->form($request, 'FormType', [], $this->mapper(), $this->handlerFail('Failed'), $ctx);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame('<html>422</html>', $response->getContent());
        self::assertSame(['Failed'], $request->getSession()->getFlashBag()->get('danger'));
    }

    public function testAutoUpdateInvalidSkips422AndClearsGuard(): void
    {
        $request = $this->newRequest('/order/new', 'POST');
        $form = $this->formMock(true, false, []);

        $forms = $this->createStub(FormFactoryInterface::class);
        $forms->method('create')->willReturn($form);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())->method('render')->willReturn('<html>AUTO</html>');

        $urls = $this->createStub(UrlGeneratorInterface::class);
        $redirector = $this->createStub(RedirectorInterface::class);

        $autoUpdate = $this->createMock(AutoUpdateGuard::class);
        $autoUpdate->method('is')->with($form)->willReturn(true);
        $autoUpdate->expects($this->once())->method('clear')->with($form);

        $flow = new FormFlow($forms, new FlashMessenger(), $twig, $urls, $redirector, $autoUpdate);
        $ctx = FlowContext::forCreate('OrderItem');

        $response = $flow->form($request, 'FormType', [], $this->mapper(), $this->handlerFail('Ignored'), $ctx);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>AUTO</html>', $response->getContent());
        self::assertEmpty($request->getSession()->getFlashBag()->get('danger'));
        self::assertEmpty($request->getSession()->getFlashBag()->get('success'));
    }
}
