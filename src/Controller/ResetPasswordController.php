<?php

namespace App\Controller;

use App\Service\ResetPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private ResetPasswordService $resetPasswordService
    ) {}

    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        return $this->resetPasswordService->handleResetPasswordRequest($request);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->resetPasswordService->handleCheckEmail();
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, TranslatorInterface $translator, ?string $token = null): Response
    {
        return $this->resetPasswordService->handleReset($request, $translator, $token);
    }
}
