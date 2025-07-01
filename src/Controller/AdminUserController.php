<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AdminUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function index(AdminUserService $adminUserService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $counts = $adminUserService->getDashboardCounts();

        return $this->render('admin/index.html.twig', $counts);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(AdminUserService $adminUserService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $users = $adminUserService->getAllUsers();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/new', name: 'app_admin_users_new')]
    public function newUser(Request $request, AdminUserService $adminUserService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $createdUser = $adminUserService->handleNewUser($request);

        if ($createdUser) {
            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        $form = $adminUserService->getNewUserForm(new User());

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => new User(),
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_users_edit')]
    public function editUser(User $user, Request $request, AdminUserService $adminUserService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $success = $adminUserService->handleEditUser($user, $request);

        if ($success) {
            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        $form = $adminUserService->getEditUserForm($user);

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, AdminUserService $adminUserService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $success = $adminUserService->deleteUser($user, $this->getUser());

            if ($success) {
                $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            }
        }

        return $this->redirectToRoute('app_admin_users');
    }
}