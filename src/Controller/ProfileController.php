<?php

namespace App\Controller;

use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('profile/index.html.twig');
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(Request $request, ProfileService $profileService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        $result = $profileService->updateProfile($user, $request);

        if ($result['success']) {
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $result['formView'],
        ]);
    }

    #[Route('/change-password', name: 'app_profile_change_password')]
    public function changePassword(Request $request, ProfileService $profileService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        $result = $profileService->changePassword($user, $request);

        if ($result['success']) {
            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_profile');
        }

        if ($result['error']) {
            $this->addFlash('error', $result['error']);
            return $this->redirectToRoute('app_profile_change_password');
        }

        return $this->render('profile/change_password.html.twig', [
            'form' => $result['formView'],
        ]);
    }
}