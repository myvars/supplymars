<?php

namespace App\Customer\UI\Http\Controller;

use App\Customer\Application\Handler\RegisterUserHandler;
use App\Customer\Domain\Model\User\EmailVerifier;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use App\Customer\UI\Http\Form\Mapper\RegisterUserMapper;
use App\Customer\UI\Http\Form\Model\RegistrationForm;
use App\Customer\UI\Http\Form\Model\ResendVerificationForm;
use App\Customer\UI\Http\Form\Type\RegistrationFormType;
use App\Customer\UI\Http\Form\Type\ResendVerificationFormType;
use App\Shared\UI\Http\FormFlow\FormFlow;
use App\Shared\UI\Http\FormFlow\View\FlowContext;
use App\Shared\UI\Http\FormFlow\View\FlowModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly MailerHelper $mailerHelper,
    ) {
    }

    private function model(): FlowModel
    {
        return FlowModel::create('customer', 'registration');
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        RegisterUserMapper $mapper,
        RegisterUserHandler $handler,
        FormFlow $flow,
        RateLimiterFactory $registrationLimiter,
    ): Response {
        if ($request->isMethod('POST')) {
            $limiter = $registrationLimiter->create($request->getClientIp());

            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('danger', 'Too many attempts. Please try again later.');

                return $this->redirectToRoute('app_register');
            }
        }

        return $flow->form(
            request: $request,
            formType: RegistrationFormType::class,
            data: new RegistrationForm(),
            mapper: $mapper,
            handler: $handler,
            context: FlowContext::forCreate($this->model())
                ->template('customer/registration/register.html.twig')
                ->successRoute('app_homepage'),
        );
    }

    #[Route(path: '/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        Security $security,
    ): RedirectResponse {
        $id = $request->query->get('id');
        if ($id === null) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->get(UserId::fromInt((int) $id));
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_register');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $verifyEmailException) {
            $this->addFlash('verify_email_error', $verifyEmailException->getReason());

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your account has been verified.');
        $security->login($user);

        return $this->redirectToRoute('app_homepage');
    }

    #[Route(path: 'verify/resend', name: 'app_verify_resend_email', methods: ['GET', 'POST'])]
    public function resendVerifyUserEmail(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        UserRepository $repository,
    ): Response {
        $data = new ResendVerificationForm();
        $data->email = $authenticationUtils->getLastUsername();

        $form = $this->createForm(ResendVerificationFormType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $repository->getByEmail($data->email);
            if ($user instanceof User) {
                $this->mailerHelper->sendEmailVerificationMessage(
                    $user,
                    $this->emailVerifier->createEmailSignatureContext('app_verify_email', $user),
                );
            }

            $this->addFlash('success', 'Email has been sent');

            return $this->redirectToRoute('app_verify_resend_email');
        }

        return $this->render('customer/registration/resend-verify-email.html.twig', [
            'form' => $form,
        ]);
    }
}
