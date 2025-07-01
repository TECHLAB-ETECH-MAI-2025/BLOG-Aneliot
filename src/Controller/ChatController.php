<?php

namespace App\Controller;

use App\Service\ChatService;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/{receiverId}', name: 'chat_index', requirements: ['receiverId' => '\d+'])]
    public function index(
        int $receiverId,
        Request $request,
        ChatService $chatService
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $result = $chatService->prepareChat($receiverId, $currentUser, $request);

        if ($result['redirect']) {
            return $this->redirectToRoute('chat_index', ['receiverId' => $receiverId]);
        }

        return $this->render('chat/index.html.twig', [
            'messages' => $result['messages'],
            'receiver' => $result['receiver'],
            'form' => $result['form']->createView(),
            'users' => $result['users'],
        ]);
    }

    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        ChatService $chatService
    ): JsonResponse {
        $result = $chatService->sendMessage($this->getUser(), $request);

        if (!$result['success']) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $result['message'],
                'errors' => $result['errors'] ?? [],
            ], $result['code']);
        }

        return new JsonResponse([
            'status' => 'sent',
            'message' => $result['message'],
            'topic' => $result['topic'],
        ], Response::HTTP_OK);
    }

    #[Route('/mercure-test')]
    public function test(HubInterface $hub): Response
    {
        $update = new \Symfony\Component\Mercure\Update(
            'http://example.com/test',
            json_encode(['message' => 'Real-time update from Symfony']),
            private: false
        );

        $hub->publish($update);

        return new Response('Update sent');
    }
}
