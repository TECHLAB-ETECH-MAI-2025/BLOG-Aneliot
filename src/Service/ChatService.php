<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageForm;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChatService
{
    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;
    private FormFactoryInterface $formFactory;
    private ValidatorInterface $validator;
    private HubInterface $hub;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository,
        FormFactoryInterface $formFactory,
        ValidatorInterface $validator,
        HubInterface $hub
    ) {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->formFactory = $formFactory;
        $this->validator = $validator;
        $this->hub = $hub;
    }

    public function prepareChat(int $receiverId, User $currentUser, Request $request): array
    {
        $receiver = $this->getReceiver($receiverId);
        $users = $this->getAllOtherUsers($currentUser);
        $messages = $this->messageRepository->findConversation($currentUser->getId(), $receiverId);

        $message = new Message();
        $form = $this->formFactory->create(MessageForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveMessage($message, $currentUser, $receiver);
            return ['redirect' => true];
        }

        return [
            'redirect' => false,
            'messages' => $messages,
            'receiver' => $receiver,
            'form' => $form,
            'users' => $users,
        ];
    }

    public function sendMessage(?User $sender, Request $request): array
    {
        if (!$sender) {
            return $this->unauthorizedResponse();
        }

        [$content, $receiverId] = $this->extractMessageData($request);
        if (!$content) {
            return $this->badRequest('Message content cannot be empty');
        }
        if (!$receiverId) {
            return $this->badRequest('Receiver ID is required');
        }

        $receiver = $this->getReceiver($receiverId);
        if (!$receiver) {
            return $this->notFound('Receiver not found');
        }

        $message = $this->createMessage($sender, $receiver, $content);
        $validationErrors = $this->validateMessage($message);
        if ($validationErrors) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationErrors,
                'code' => 400,
            ];
        }

        $this->saveMessageEntity($message);
        $topic = $this->buildTopic($sender->getId(), $receiver->getId());
        $updateData = $this->buildMercureData($message, $sender, $receiver);
        $this->publishUpdate($updateData, $topic);

        return [
            'success' => true,
            'message' => $updateData,
            'topic' => $topic,
        ];
    }

    private function getReceiver(int $receiverId): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($receiverId);
    }

    private function getAllOtherUsers(User $currentUser): array
    {
        return $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function extractMessageData(Request $request): array
    {
        $content = trim($request->request->get('content', ''));
        $receiverId = $request->request->get('receiver');
        return [$content, $receiverId];
    }

    private function createMessage(User $sender, User $receiver, string $content): Message
    {
        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());
        return $message;
    }

    private function validateMessage(Message $message): array
    {
        $errors = $this->validator->validate($message);
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }
        return $errorMessages;
    }

    private function saveMessage(Message $message, User $sender, User $receiver): void
    {
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setCreatedAt(new \DateTime());
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    private function saveMessageEntity(Message $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    private function buildTopic(int $senderId, int $receiverId): string
    {
        $minId = min($senderId, $receiverId);
        $maxId = max($senderId, $receiverId);
        return "http://localhost:8000/conversation/{$minId}-{$maxId}";
    }

    private function buildMercureData(Message $message, User $sender, User $receiver): array
    {
        return [
            'type' => 'message.new',
            'id' => $message->getId(),
            'sender' => [
                'id' => $sender->getId(),
                'email' => $sender->getEmail(),
            ],
            'receiver' => [
                'id' => $receiver->getId(),
            ],
            'content' => $message->getContent(),
            'timestamp' => $message->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'status' => 'delivered'
        ];
    }

    private function publishUpdate(array $updateData, string $topic): void
    {
        $update = new Update([$topic], json_encode($updateData), false);
        $this->hub->publish($update);
    }

    private function unauthorizedResponse(): array
    {
        return [
            'success' => false,
            'message' => 'Authentication required',
            'code' => 401
        ];
    }

    private function badRequest(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => 400
        ];
    }

    private function notFound(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => 404
        ];
    }
}
