<?php

namespace App\Tests\Unit\Service\Crud\Common;

use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class CrudOptionsTest extends TestCase
{
    public function testSetAndGetTemplate(): void
    {
        $crudOptions = new CrudOptions();
        $template = 'template.html.twig';

        $crudOptions->setTemplate($template);

        $this->assertSame($template, $crudOptions->getTemplate());
    }

    public function testSetAndGetSection(): void
    {
        $crudOptions = new CrudOptions();
        $section = 'section';

        $crudOptions->setSection($section);

        $this->assertSame($section, $crudOptions->getSection());
    }

    public function testSetAndGetEntity(): void
    {
        $crudOptions = new CrudOptions();
        $entity = new \stdClass();

        $crudOptions->setEntity($entity);

        $this->assertSame($entity, $crudOptions->getEntity());
    }

    public function testSetAndGetForm(): void
    {
        $crudOptions = new CrudOptions();
        $form = $this->createMock(FormInterface::class);

        $crudOptions->setForm($form);

        $this->assertSame($form, $crudOptions->getForm());
    }

    public function testSetAndGetCrudAction(): void
    {
        $crudOptions = new CrudOptions();
        $crudAction = $this->createMock(CrudActionInterface::class);

        $crudOptions->setCrudAction($crudAction);

        $this->assertSame($crudAction, $crudOptions->getCrudAction());
    }

    public function testSetAndGetCrudActionContext(): void
    {
        $crudOptions = new CrudOptions();
        $context = ['key' => 'value'];

        $crudOptions->setCrudActionContext($context);

        $this->assertSame($context, $crudOptions->getCrudActionContext());
    }

    public function testSetAndGetSuccessFlash(): void
    {
        $crudOptions = new CrudOptions();
        $flash = 'Success!';

        $crudOptions->setSuccessFlash($flash);

        $this->assertSame($flash, $crudOptions->getSuccessFlash());
    }

    public function testSetAndGetErrorFlash(): void
    {
        $crudOptions = new CrudOptions();
        $flash = 'Error!';

        $crudOptions->setErrorFlash($flash);

        $this->assertSame($flash, $crudOptions->getErrorFlash());
    }

    public function testSetAndGetSuccessLink(): void
    {
        $crudOptions = new CrudOptions();
        $link = '/success';

        $crudOptions->setSuccessLink($link);

        $this->assertSame($link, $crudOptions->getSuccessLink());
    }

    public function testSetAndGetSafetyLink(): void
    {
        $crudOptions = new CrudOptions();
        $link = '/safety';

        $crudOptions->setSafetyLink($link);

        $this->assertSame($link, $crudOptions->getSafetyLink());
    }

    public function testSetAndGetBackLink(): void
    {
        $crudOptions = new CrudOptions();
        $link = '/back';

        $crudOptions->setBackLink($link);

        $this->assertSame($link, $crudOptions->getBackLink());
    }

    public function testSetAndIsUrlRefresh(): void
    {
        $crudOptions = new CrudOptions();

        $crudOptions->setIsUrlRefresh(true);

        $this->assertTrue($crudOptions->isUrlRefresh());
    }

    public function testSetAndIsAllowDelete(): void
    {
        $crudOptions = new CrudOptions();

        $crudOptions->setAllowDelete(true);

        $this->assertTrue($crudOptions->isAllowDelete());
    }

    public function testUseSafetyLink(): void
    {
        $crudOptions = new CrudOptions();
        $link = '/safety';

        $crudOptions->setSafetyLink($link);
        $crudOptions->useSafetyLink();

        $this->assertSame($link, $crudOptions->getSuccessLink());
    }

    public function testCreate(): void
    {
        $crudOptions = CrudOptions::create();

        $this->assertInstanceOf(CrudOptions::class, $crudOptions);
    }
}