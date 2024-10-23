<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\MailerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
        private readonly FormLoginAuthenticator $formLoginAuthenticator,
        private readonly MailerHelper $mailerHelper,
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_register'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerification($user);

            // do anything else you need here, like send an email
            $this->addFlash('success', 'Your account has been created. Follow the link in your email to verify your account.');

            return $userAuthenticator->authenticateUser(
                $user,
                $this->formLoginAuthenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository, UserAuthenticatorInterface $userAuthenticator): Response
    {
        $id = $request->query->get('id');
        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $verifyEmailException) {
            $this->addFlash('verify_email_error', $translator->trans($verifyEmailException->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your account has been verified.');

        return $userAuthenticator->authenticateUser(
            $user,
            $this->formLoginAuthenticator,
            $request
        );
    }

    #[Route('verify/resend', name: 'app_verify_resend_email')]
    public function resendVerifyUserEmail(AuthenticationUtils $authenticationUtils): Response
    {
        // remove the login error if there is one
        $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('registration/resend-verify-email.html.twig', [
            'last_username' => $lastUsername,
        ]);
    }

    #[Route('verify/send', name: 'app_verify_send_email')]
    public function sendVerifyUserEmail(Request $request, UserRepository $repository): Response
    {
        $user = $repository->findOneBy(['email' => $request->get('email')]);
        if ($user instanceof User) {
            $this->emailVerification($user);
        }

        $this->addFlash('success', 'Email has been sent');

        return $this->redirectToRoute('app_verify_resend_email');
    }

    private function emailVerification(User $user): void
    {
        $this->mailerHelper->sendEmailVerificationMessage(
            $user,
            $this->emailVerifier->createEmailSignatureContext('app_verify_email', $user),
        );
    }
}
