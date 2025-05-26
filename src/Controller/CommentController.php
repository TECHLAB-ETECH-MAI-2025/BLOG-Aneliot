<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentForm;
use App\Repository\CommentRepository;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/new/{article_id}', name: 'app_comment_new', methods: ['POST'])]
    public function new(
        int $article_id,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleRepository $articleRepository
    ): JsonResponse {
        $article = $articleRepository->find($article_id);
        if (!$article) {
            return $this->json(['success' => false, 'error' => 'Article not found']);
        }

        $comment = new Comment();

        $form = $this->createForm(CommentForm::class, $comment, [
            'article_id' => $article_id,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime());
            $comment->setArticle($article);
            $entityManager->persist($comment);
            $entityManager->flush();

            // Render the comment partial to HTML
            $commentHtml = $this->renderView('comment/_comment.html.twig', [
                'comment' => $comment,
            ]);

            return $this->json([
                'success' => true,
                'commentHtml' => $commentHtml,
                'commentsCount' => count($article->getComments()),
            ]);
        }

        // Extract errors to return
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->json([
            'success' => false,
            'error' => implode(', ', $errors),
        ]);
    }


    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }
}
