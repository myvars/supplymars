<?php

namespace App\Tests\Shared\UI\Http\FormFlow\View;

use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FormOperation;
use App\Shared\UI\Http\FormFlow\View\ModelPath;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

final class FlowContextTest extends TestCase
{
    private function urlGenerator(): UrlGeneratorInterface
    {
        $stub = $this->createStub(UrlGeneratorInterface::class);

        $stub->method('generate')->willReturnCallback(
            static function (string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string {
                ksort($parameters);
                $query = $parameters !== [] ? '?' . http_build_query($parameters) : '';

                return '/gen/' . $name . $query;
            }
        );

        // If code under test calls setContext/getContext, add:
        $context = new RequestContext();
        $stub->method('getContext')->willReturn($context);
        $stub->method('setContext')->willReturnCallback(static function ($new) use (&$context): void { $context = $new; });

        return $stub;
    }

    public function testFactoryForCreateSetsDefaults(): void
    {
        $ctx = FlowContext::forCreate('Sales/OrderItem');
        self::assertSame('Sales/OrderItem', $ctx->getModel());
        self::assertSame(FormOperation::Create, $ctx->getOperation());
        self::assertSame(ModelPath::template('Sales/OrderItem', 'create'), $ctx->getTemplate());
        self::assertSame('app_sales_orderitem_index', $ctx->getSuccessRoute());
    }

    public function testValidateThrowsWhenModelMissing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Model not configured.');
        $ctx = FlowContext::new();
        $ctx->validate();
    }

    public function testValidateThrowsWhenOperationMissing(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Form operation not configured.');
        $ctx = FlowContext::new()->model('OrderItem');
        $ctx->validate();
    }

    public function testResolveSuccessUrlUsesConfiguredRoute(): void
    {
        $ctx = FlowContext::forCreate('OrderItem')->successParams(['page' => 2]);
        $req = Request::create('/current');
        $url = $ctx->resolveSuccessUrl($req, $this->urlGenerator());
        self::assertSame('/gen/app_orderitem_index?page=2', $url);
    }

    public function testResolveSuccessUrlFallsBackToReferer(): void
    {
        FlowContext::new()->model('OrderItem')->template('x')->successRoute('', []);
        $req = Request::create('/current');
        $req->headers->set('referer', '/prev');
        // Clear successRoute to trigger fallback
        $refCtx = FlowContext::new()->model('OrderItem');
        $ref = $refCtx->resolveSuccessUrl($req, $this->urlGenerator());
        self::assertSame('/prev', $ref);
    }

    public function testResolveSuccessUrlFallsBackToCurrentWhenNoReferer(): void
    {
        $ctx = FlowContext::new()->model('OrderItem');
        $req = Request::create('/current');
        $url = $ctx->resolveSuccessUrl($req, $this->urlGenerator());
        self::assertSame('/current', $url);
    }

    public function testResolveBackUrl(): void
    {
        $ctx = FlowContext::new();
        $req = Request::create('/x');
        self::assertNull($ctx->resolveBackUrl($req));
        $req->headers->set('referer', '/prev');
        self::assertSame('/prev', $ctx->resolveBackUrl($req));
    }

    public function testMutators(): void
    {
        $ctx = FlowContext::new()
            ->model('OrderItem')
            ->template('custom.html.twig')
            ->successRoute('app_custom', ['id' => 10])
            ->allowDelete(true)
            ->redirectOptions(true, 302);

        self::assertSame('OrderItem', $ctx->getModel());
        self::assertSame('custom.html.twig', $ctx->getTemplate());
        self::assertSame('app_custom', $ctx->getSuccessRoute());
        self::assertSame(['id' => 10], $ctx->getSuccessParams());
        self::assertTrue($ctx->isAllowDelete());
        self::assertTrue($ctx->isRedirectRefresh());
        self::assertSame(302, $ctx->getRedirectStatus());
    }
}
