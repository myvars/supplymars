<?php

namespace App\Customer\UI\Http\Controller;

use App\Customer\Application\Command\RequestPasswordReset;
use App\Customer\Application\Command\ResetPassword;
use App\Customer\Application\Handler\RequestPasswordResetHandler;
use App\Customer\Application\Handler\ResetPasswordHandler;
use App\Customer\UI\Http\Form\Model\ChangePasswordForm;
use App\Customer\UI\Http\Form\Model\ResetPasswordRequestForm;
use App\Customer\UI\Http\Form\Type\ChangePasswordFormType;
use App\Customer\UI\Http\Form\Type\ResetPasswordRequestFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
    }

    #[Route(path: '/reset-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        RequestPasswordResetHandler $handler,
    ): Response {
        $formData = new ResetPasswordRequestForm();
        $form = $this->createForm(ResetPasswordRequestFormType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $handler(new RequestPasswordReset($formData->email));

            if ($result->payload instanceof ResetPasswordToken) {
                $this->setTokenObjectInSession($result->payload);
            }

            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('customer/reset_password/request.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        $resetToken = $this->getTokenObjectFromSession();
        if (!$resetToken instanceof ResetPasswordToken) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('customer/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route(path: '/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        ResetPasswordHandler $handler,
        TranslatorInterface $translator,
        ?string $token = null,
    ): Response {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if ($token === null) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $resetPasswordException) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                $resetPasswordException->getReason(),
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $formData = new ChangePasswordForm();
        $form = $this->createForm(ChangePasswordFormType::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $handler(new ResetPassword($token, $formData->plainPassword));

            if ($result->ok) {
                $this->cleanSessionAfterReset();
                $this->addFlash('success', $result->message);

                return $this->redirectToRoute('app_homepage');
            }

            $this->addFlash('reset_password_error', $result->message);
        }

        return $this->render('customer/reset_password/reset.html.twig', [
            'form' => $form,
        ]);
    }
}
