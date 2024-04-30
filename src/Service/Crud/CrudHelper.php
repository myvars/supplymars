<?php

namespace App\Service\Crud;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\UX\Turbo\TurboBundle;
use Twig\Environment;

class CrudHelper
{
    public const CRUD_BASE_TEMPLATE = 'crud/crud.html.twig';
    public const MISSING_ENTITY_TEMPLATE = 'show_empty';
    public const TURBO_STREAM_REFRESH_TEMPLATE = 'common/turboStreamRefresh.html.twig';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function snakeCase(string $string): string
    {
        return (new UnicodeString($string))->lower()->snake();
    }

    public function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    public function getRouter(): UrlGeneratorInterface
    {
        return $this->router;
    }

    public function showEmpty(string $section) : Response
    {
        try {
            $content = $this->twig->render(self::CRUD_BASE_TEMPLATE, [
                'section' => $section,
                'template' => self::MISSING_ENTITY_TEMPLATE,
            ]);
        } catch (\Exception $e) {
            $content = '';
        }

        return new Response($content, Response::HTTP_OK);
    }

    public function crudError(string $section): Response
    {
        $this->requestStack->getSession()->getFlashBag()->add(
            'warning',
            $section.' not found!'
        );

        return new RedirectResponse(
            $this->router->generate('app_'.$this->snakeCase($section).'_index', []),
            Response::HTTP_SEE_OTHER
        );
    }

    public function streamRefresh(): Response
    {
        $this->requestStack->getCurrentRequest()->setRequestFormat(TurboBundle::STREAM_FORMAT);

        try {
            $content = $this->twig->render(self::TURBO_STREAM_REFRESH_TEMPLATE, []);
        } catch (\Exception $e) {
            $content = '';
        }

        return new Response($content, Response::HTTP_OK);
    }
}
