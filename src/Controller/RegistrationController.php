<?php

namespace App\Controller;

use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/register')]
class RegistrationController extends AbstractController
{
    public function __construct(private RegistrationService $registrationService)
    {
    }

    #[Route('', name: 'app_register')]
    public function register(Request $request, Security $security): Response
    {
        $result = $this->registrationService->register($request);

        if ($result['success']) {
            return $security->login($result['user'], $result['authenticator'], 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $result['formView'],
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $result = $this->registrationService->verifyUserEmail($request);

        if (!$result['success']) {
            $this->addFlash('verify_email_error', $translator->trans(
                $result['error'],
                [],
                'VerifyEmailBundle'
            ));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
