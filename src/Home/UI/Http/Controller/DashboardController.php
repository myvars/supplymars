<?php

namespace App\Home\UI\Http\Controller;

use App\Home\Service\DashboardDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardDataProvider $dashboardProvider,
    ) {
    }

    #[Route(path: '/dashboard/', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('home/dashboard.html.twig', $this->dashboardProvider->getData());
    }
}
