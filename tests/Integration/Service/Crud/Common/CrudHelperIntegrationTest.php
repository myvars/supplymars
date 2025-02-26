<?php

namespace App\Tests\Integration\Service\Crud\Common;

use App\Service\Crud\Common\CrudHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CrudHelperIntegrationTest extends KernelTestCase
{
    private CrudHelper $crudHelper;
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $requestStack = static::getContainer()->get(RequestStack::class);
        $twig = static::getContainer()->get(Environment::class);
        $router = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->formFactory = static::getContainer()->get(FormFactoryInterface::class);

        $this->crudHelper = new CrudHelper($requestStack, $twig, $router);
    }

    public function testIsAutoUpdate(): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('auto-update', SubmitType::class)
            ->getForm();

        $form->submit(['auto-update' => 'clicked']);

        $result = $this->crudHelper->isAutoUpdate($form);
        $this->assertTrue($result);
    }
}