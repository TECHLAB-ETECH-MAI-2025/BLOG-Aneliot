<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Firebase\JWT\JWT;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/{receiverId}', name: 'chat_index', requirements: ['receiverId' => '\d+'])]
    public function index(
        int $receiverId,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser instanceof UserInterface) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $receiver = $entityManager->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Get all users except the current one for the sidebar
        $users = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();

        // Fetch messages between current user and receiver
        $messages = $messageRepository->findConversation($currentUser->getId(), $receiverId);

        // Handle message form
        $message = new Message();
        $form = $this->createForm(MessageForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($currentUser);
            $message->setReceiver($receiver);
            $message->setCreatedAt(new \DateTime());

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('chat_index', ['receiverId' => $receiverId]);
        }

        return $this->render('chat/index.html.twig', [
            'messages' => $messages,
            'receiver' => $receiver,
            'form' => $form->createView(),
            'users' => $users, 
        ]);
    }
    // #[Route('/send', name: 'send', methods: ['POST'])]
    // public function sendMessage(EntityManagerInterface $entityManager, Request $request): Response
    // {
    //     $content = $request->request->get('content');
    //     $receiverId = $request->request->get('receiver');
        
    //     $message = new Message();
    //     $message->setSender($this->getUser());
    //     $message->setReceiver($entityManager->getRepository(User::class)->find($receiverId));
    //     $message->setContent($content);
    //     $message->setCreatedAt(new \DateTime());

    //     $entityManager->persist($message);
    //     $entityManager->flush();

    //     return new JsonResponse(['success' => true]);
    // }
    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMessage(
        EntityManagerInterface $entityManager,
        Request $request,
        HubInterface $hub,
        ValidatorInterface $validator
    ): Response {
        $content = trim($request->request->get('content', ''));
        $receiverId = $request->request->get('receiver');

        if (empty($content)) {
            return new JsonResponse(['error' => 'Message content cannot be empty'], 400);
        }

        if (!$receiverId) {
            return new JsonResponse(['error' => 'Receiver ID is required'], 400);
        }

        $sender = $this->getUser();
        $senderId = $sender ? $sender->getId() : null;
        $receiver = $entityManager->getRepository(User::class)->find($receiverId);

        if (!$sender) {
            return new JsonResponse(['error' => 'You must be logged in to send messages'], 401);
        }

        if (!$receiver) {
            return new JsonResponse(['error' => 'Receiver not found'], 404);
        }
        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());

        $errors = $validator->validate($message);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        $entityManager->persist($message);
        $entityManager->flush();
        $minId = min($senderId, $receiverId);
        $maxId = max($senderId, $receiverId);

        $topic = "http://localhost:8000/conversation/{$minId}-{$maxId}";
        $updateData = [
            'type' => 'message.new',
            'id' => $message->getId(),
            'sender' => [
                'id' => $sender->getId(),
                'email' => $sender->getEmail(),
            ],
            'receiver' => [
                'id' => $receiver->getId(),
            ],
            'content' => $content,
            'timestamp' => $message->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'status' => 'delivered'
        ];

        $update = new Update([$topic], json_encode($updateData), false);
        $hub->publish($update);

        return new JsonResponse([
            'status' => 'sent',
            'message' => $updateData,
            'topic' => $topic
        ]);
    }
    #[Route('/mercure-test')]
    public function test(HubInterface $hub): Response
    {
        $update = new Update(
            'http://example.com/test',
            json_encode(['message' => 'Real-time update from Symfony']),
            private: false
        );

        $hub->publish($update);

        return new Response('Update sent');
    }

}
