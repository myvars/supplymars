<?php

namespace App\Customer\UI\Http\Controller;

use App\Customer\Domain\Model\User\User;
use App\Shared\UI\Http\Validation\Turnstile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlaygroundLoginController extends AbstractController
{
    private const string DEMO_EMAIL = 'demo@supplymars.com';

    #[Route(path: '/playground/login', name: 'app_playground_login', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        Security $security,
        bool $playgroundMode,
    ): Response {
        if (!$playgroundMode) {
            throw $this->createNotFoundException();
        }

        $token = $request->request->getString('turnstile_response');
        $violations = $validator->validate($token, new Turnstile());

        if ($violations->count() > 0) {
            $this->addFlash('danger', 'Verification failed. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => self::DEMO_EMAIL]);

        if ($user === null) {
            $this->addFlash('danger', 'Demo account is not available.');

            return $this->redirectToRoute('app_login');
        }

        $security->login($user, 'security.authenticator.form_login.main');

        return $this->redirectToRoute('app_catalog_product_index');
    }
}
