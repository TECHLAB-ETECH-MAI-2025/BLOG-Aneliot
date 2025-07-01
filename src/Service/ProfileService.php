<?php

namespace App\Service;

use App\Form\ProfileForm;
use App\Form\ChangePasswordForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;

class ProfileService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function updateProfile(User $user, Request $request): array
    {
        $form = $this->formFactory->create(ProfileForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return [
                'success' => true,
                'formView' => $form->createView(),
            ];
        }

        return [
            'success' => false,
            'formView' => $form->createView(),
        ];
    }
    public function changePassword(User $user, Request $request): array
    {
        $form = $this->formFactory->create(ChangePasswordForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                return [
                    'success' => false,
                    'error' => 'Le mot de passe actuel est incorrect.',
                    'formView' => $form->createView(),
                ];
            }

            $newPassword = $form->get('plainPassword')->getData();
            $hashed = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashed);

            $this->entityManager->flush();

            return [
                'success' => true,
                'error' => null,
                'formView' => null,
            ];
        }

        return [
            'success' => false,
            'error' => null,
            'formView' => $form->createView(),
        ];
    }
}
