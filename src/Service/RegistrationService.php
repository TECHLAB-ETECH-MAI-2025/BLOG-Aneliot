<?php

namespace App\Service;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EmailVerifier $emailVerifier
    ) {}

    public function register(Request $request): array
    {
        $user = new User();
        $form = $this->formFactory->create(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $hashed = $this->userPasswordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->sendVerificationEmail($user);

            return [
                'success' => true,
                'user' => $user,
                'authenticator' => LoginFormAuthenticator::class,
                'formView' => null,
            ];
        }

        return [
            'success' => false,
            'user' => null,
            'authenticator' => null,
            'formView' => $form->createView(),
        ];
    }

    private function sendVerificationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('pokaneliot@gmail.com', 'Blog'))
                ->to((string) $user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    public function verifyUserEmail(Request $request): array
    {
        try {
            /** @var User $user */
            $user = $request->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);

            return ['success' => true, 'error' => null];
        } catch (VerifyEmailExceptionInterface $e) {
            return ['success' => false, 'error' => $e->getReason()];
        }
    }
}
