<?php

namespace App\Tests\Unit\Service\Crud\Common;

use App\Service\Crud\Common\CrudContext;
use App\Service\Crud\Common\CrudHandlerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class CrudOptionsTest extends TestCase
{
    public function testSetAndGetTemplate(): void
    {
        $context = new CrudContext();
        $template = 'template.html.twig';

        $context->setTemplate($template);

        $this->assertSame($template, $context->getTemplate());
    }

    public function testSetAndGetSection(): void
    {
        $context = new CrudContext();
        $section = 'section';

        $context->setSection($section);

        $this->assertSame($section, $context->getSection());
    }

    public function testSetAndGetEntity(): void
    {
        $context = new CrudContext();
        $entity = new \stdClass();

        $context->setEntity($entity);

        $this->assertSame($entity, $context->getEntity());
    }

    public function testSetAndGetForm(): void
    {
        $context = new CrudContext();
        $form = $this->createMock(FormInterface::class);

        $context->setForm($form);

        $this->assertSame($form, $context->getForm());
    }

    public function testSetAndGetCrudHandler(): void
    {
        $context = new CrudContext();
        $handler = $this->createMock(CrudHandlerInterface::class);

        $context->setCrudHandler($handler);

        $this->assertSame($handler, $context->getCrudHandler());
    }

    public function testSetAndGetCrudHandlerContext(): void
    {
        $context = new CrudContext();
        $handlerContext = ['key' => 'value'];

        $context->setCrudHandlerContext($handlerContext);

        $this->assertSame($handlerContext, $context->getCrudHandlerContext());
    }

    public function testSetAndGetSuccessFlash(): void
    {
        $context = new CrudContext();
        $flash = 'Success!';

        $context->setSuccessFlash($flash);

        $this->assertSame($flash, $context->getSuccessFlash());
    }

    public function testSetAndGetErrorFlash(): void
    {
        $context = new CrudContext();
        $flash = 'Error!';

        $context->setErrorFlash($flash);

        $this->assertSame($flash, $context->getErrorFlash());
    }

    public function testSetAndGetSuccessLink(): void
    {
        $context = new CrudContext();
        $link = '/success';

        $context->setSuccessLink($link);

        $this->assertSame($link, $context->getSuccessLink());
    }

    public function testSetAndGetSafetyLink(): void
    {
        $context = new CrudContext();
        $link = '/safety';

        $context->setSafetyLink($link);

        $this->assertSame($link, $context->getSafetyLink());
    }

    public function testSetAndGetBackLink(): void
    {
        $context = new CrudContext();
        $link = '/back';

        $context->setBackLink($link);

        $this->assertSame($link, $context->getBackLink());
    }

    public function testSetAndIsUrlRefresh(): void
    {
        $context = new CrudContext();

        $context->setIsUrlRefresh(true);

        $this->assertTrue($context->isUrlRefresh());
    }

    public function testSetAndIsAllowDelete(): void
    {
        $context = new CrudContext();

        $context->setAllowDelete(true);

        $this->assertTrue($context->isAllowDelete());
    }

    public function testUseSafetyLink(): void
    {
        $context = new CrudContext();
        $link = '/safety';

        $context->setSafetyLink($link);
        $context->useSafetyLink();

        $this->assertSame($link, $context->getSuccessLink());
    }

    public function testCreate(): void
    {
        $context = CrudContext::create();

        $this->assertInstanceOf(CrudContext::class, $context);
    }
}
