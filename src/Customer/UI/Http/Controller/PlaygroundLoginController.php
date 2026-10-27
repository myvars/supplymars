<?php

declare(strict_types=1);

namespace App\Customer\UI\Http\Controller;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\UI\Http\Validation\Turnstile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        UserRepository $users,
        ValidatorInterface $validator,
        Security $security,
        #[Autowire(env: 'bool:PLAYGROUND_MODE')]
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

        $user = $users->getByEmail(self::DEMO_EMAIL);

        if (!$user instanceof User) {
            $this->addFlash('danger', 'Demo account is not available.');

            return $this->redirectToRoute('app_login');
        }

        $security->login($user, 'security.authenticator.form_login.main');

        return $this->redirectToRoute('app_dashboard');
    }
}
