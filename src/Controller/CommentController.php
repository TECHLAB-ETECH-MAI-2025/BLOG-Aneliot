<?php

namespace App\Controller;

use App\Service\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Comment;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route(name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentService $commentService): Response
    {
        $comments = $commentService->getAllComments();

        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/new/{article_id}', name: 'app_comment_new', methods: ['POST'])]
    public function new(int $article_id, Request $request, CommentService $commentService): JsonResponse
    {
        $result = $commentService->createComment($article_id, $request);

        if (!$result['success']) {
            return $this->json([
                'success' => false,
                'error' => $result['error'],
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'commentHtml' => $result['commentHtml'],
            'commentsCount' => $result['commentsCount'],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Comment $comment, Request $request, CommentService $commentService): Response
    {
        $commentService->deleteComment($comment, $request);

        return $this->redirectToRoute('app_comment_index', [], Response::HTTP_SEE_OTHER);
    }
}
