<?php

declare(strict_types=1);

namespace App\Home\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route(path: '/about', name: 'app_about_page')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route(path: '/contact', name: 'app_contact_page')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }

    #[Route(path: '/privacy', name: 'app_privacy_page')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig');
    }

    #[Route(path: '/terms', name: 'app_terms_page')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig');
    }
}
