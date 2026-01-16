<?php

namespace App\Tests\Shared\UI\Http\FormFlow\View;

use App\Shared\UI\Http\FormFlow\ShowFlow;
use App\Shared\UI\Http\FormFlow\View\ModelPath;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class ShowFlowTest extends TestCase
{
    public function testShowRendersDefaultTemplateContext(): void
    {
        $twig = $this->createMock(Environment::class);

        $twig->expects($this->once())->method('render')
            ->with(
                ModelPath::BASE_TEMPLATE,
                $this->callback(fn (array $vars): bool => $vars['flowModel'] === 'OrderItem'
                    && $vars['flowRoute'] === 'orderitem'
                    && $vars['flowPath'] === 'orderitem/'
                    && $vars['flowOperation'] === 'show'
                    && $vars['template'] === 'orderitem/show.html.twig'
                    && $vars['foo'] === 'bar')
            )->willReturn('<html>OK</html>');

        $flow = new ShowFlow($twig);
        $response = $flow->show('OrderItem', ['foo' => 'bar']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>OK</html>', $response->getContent());
    }

    public function testShowRendersWithTemplateOverride(): void
    {
        $twig = $this->createMock(Environment::class);

        $override = 'custom/orderitem/custom_show.html.twig';

        $twig->expects($this->once())->method('render')
            ->with(
                ModelPath::BASE_TEMPLATE,
                $this->callback(fn (array $vars): bool => $vars['template'] === $override
                    && $vars['flowOperation'] === 'show'
                    && $vars['flowRoute'] === 'orderitem')
            )->willReturn('<html>OVERRIDE</html>');

        $flow = new ShowFlow($twig);
        $response = $flow->show('OrderItem', ['extra' => 123], $override);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>OVERRIDE</html>', $response->getContent());
    }

    public function testShowAllowsExtraVarsToOverrideContext(): void
    {
        $twig = $this->createMock(Environment::class);

        $twig->expects($this->once())->method('render')
            ->with(
                ModelPath::BASE_TEMPLATE,
                $this->callback(fn (array $vars): bool => $vars['flowModel'] === 'CustomModel'
                    && $vars['flowOperation'] === 'OVERRIDE'
                    && $vars['template'] === 'orderitem/show.html.twig')
            )->willReturn('<html>OVERRIDDEN VARS</html>');

        $flow = new ShowFlow($twig);
        $response = $flow->show('OrderItem', [
            'flowModel' => 'CustomModel',
            'flowOperation' => 'OVERRIDE',
        ]);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('<html>OVERRIDDEN VARS</html>', $response->getContent());
    }
}
