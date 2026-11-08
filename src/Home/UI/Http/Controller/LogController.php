<?php

declare(strict_types=1);

namespace App\Home\UI\Http\Controller;

use App\Home\Service\EngineeringLog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogController extends AbstractController
{
    public function __construct(private readonly EngineeringLog $log)
    {
    }

    #[Route(path: '/log', name: 'app_log_index')]
    public function index(): Response
    {
        return $this->render('home/log/index.html.twig', ['posts' => $this->log->all()]);
    }

    #[Route(path: '/log/{slug}', name: 'app_log_show', requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])]
    public function show(string $slug): Response
    {
        $post = $this->log->find($slug) ?? throw $this->createNotFoundException('No log post with that name.');

        return $this->render('home/log/show.html.twig', [
            'post' => $post,
            'content' => $this->log->render($post),
            'neighbours' => $this->log->neighbours($post),
        ]);
    }
}
