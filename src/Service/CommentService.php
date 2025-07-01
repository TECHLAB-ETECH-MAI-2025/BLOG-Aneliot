<?php

namespace App\Service;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentService extends AbstractController
{
    public function __construct(
        private CommentRepository $commentRepository,
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory,
    ) {}

    public function getAllComments(): array
    {
        return $this->commentRepository->findAll();
    }

    public function createComment(int $articleId, Request $request): array
    {
        $article = $this->articleRepository->find($articleId);

        if (!$article) {
            return [
                'success' => false,
                'error' => 'Article not found',
            ];
        }

        $comment = new Comment();
        $form = $this->formFactory->create(\App\Form\CommentForm::class, $comment, [
            'article_id' => $articleId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime());
            $comment->setArticle($article);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $commentHtml = $this->renderView('comment/_comment.html.twig', [
                'comment' => $comment,
            ]);

            return [
                'success' => true,
                'commentHtml' => $commentHtml,
                'commentsCount' => $article->getComments()->count(),
            ];
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return [
            'success' => false,
            'error' => implode(', ', $errors),
        ];
    }

    public function deleteComment(Comment $comment, Request $request): void
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($comment);
            $this->entityManager->flush();
        }
    }
}
