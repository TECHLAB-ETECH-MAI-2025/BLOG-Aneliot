<?php

namespace App\Service;

use App\Entity\User;
use App\Form\AdminUserForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminUserService
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private FormFactoryInterface $formFactory;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        FormFactoryInterface $formFactory
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->formFactory = $formFactory;
    }

    public function getDashboardCounts(): array
    {
        return [
            'userCount' => $this->userRepository->count([]),
            'verifiedCount' => $this->userRepository->count(['isVerified' => true]),
            'adminCount' => $this->userRepository->countAdmins(),
        ];
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function handleNewUser(Request $request): ?User
    {
        $user = new User();
        $form = $this->formFactory->create(AdminUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setIsVerified(true);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $user;
        }

        return null;
    }

    public function getNewUserForm(User $user): \Symfony\Component\Form\FormInterface
    {
        return $this->formFactory->create(AdminUserForm::class, $user);
    }

    public function handleEditUser(User $user, Request $request): bool
    {
        $form = $this->formFactory->create(AdminUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function getEditUserForm(User $user): \Symfony\Component\Form\FormInterface
    {
        return $this->formFactory->create(AdminUserForm::class, $user);
    }

    public function deleteUser(User $user, UserInterface $currentUser): bool
    {
        if ($user === $currentUser) {
            return false;
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }
}