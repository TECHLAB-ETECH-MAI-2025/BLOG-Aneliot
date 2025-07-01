<?php

namespace App\Service;

use App\Entity\User;
use App\Form\ChangePasswordForm;
use App\Form\ResetPasswordRequestForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordService
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        private \Twig\Environment $twig
    ) {}

    public function handleResetPasswordRequest(Request $request): Response
    {
        $form = $this->formFactory->create(ResetPasswordRequestForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            return $this->processSendingPasswordResetEmail($email);
        }
        return new Response($this->twig->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]));
    }

    public function handleCheckEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }
        return new Response($this->twig->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]));
    }

    public function handleReset(Request $request, TranslatorInterface $translator, ?string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);
            return new RedirectResponse('/reset-password/reset');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw new \Exception('No reset password token found.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            return new RedirectResponse('/reset-password');
        }

        $form = $this->formFactory->create(ChangePasswordForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            $this->entityManager->flush();
            $this->cleanSessionAfterReset();

            return new RedirectResponse('/');
        }
        return new Response($this->twig->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]));
    }

    private function processSendingPasswordResetEmail(string $emailFormData): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        if (!$user) {
            return new RedirectResponse('/reset-password/check-email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return new RedirectResponse('/reset-password/check-email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('pokaneliot@gmail.com', 'Blog'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context(['resetToken' => $resetToken]);

        $this->mailer->send($email);

        $this->setTokenObjectInSession($resetToken);

        return new RedirectResponse('/reset-password/check-email');
    }
}
