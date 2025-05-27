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
            'users' => $users, // list for sidebar
        ]);
    }
    #[Route('/send', name: 'send', methods: ['POST'])]
    public function sendMessage(EntityManagerInterface $entityManager, Request $request): Response
    {
        $content = $request->request->get('content');
        $receiverId = $request->request->get('receiver');
        
        $message = new Message();
        $message->setSender($this->getUser());
        $message->setReceiver($entityManager->getRepository(User::class)->find($receiverId));
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());

        $entityManager->persist($message);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

}
