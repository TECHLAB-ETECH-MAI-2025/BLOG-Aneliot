<?php

namespace App\Controller;

use App\Entity\Article;
use App\Service\ArticleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/article')]
final class ArticleController extends AbstractController
{
    #[Route(name: 'app_article_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('article/index.html.twig');
    }

    #[Route('/{id}/comments', name: 'app_article_comments', methods: ['GET'])]
    public function showByArticle(Article $article, ArticleService $articleService): Response
    {
        $comments = $articleService->getCommentsForArticle($article);
        $form = $articleService->getCommentForm($article);

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ArticleService $articleService): Response
    {
        $created = $articleService->handleNewArticle($request);

        if ($created) {
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $articleService->getArticleForm(new Article());

        return $this->render('article/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, ArticleService $articleService): Response
    {
        if ($articleService->handleEditArticle($article, $request)) {
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $articleService->getArticleForm($article);

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, ArticleService $articleService): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->getPayload()->getString('_token'))) {
            $articleService->deleteArticle($article);
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/articles', name: 'api_articles_datatable', methods: ['POST'])]
    public function datatable(Request $request, ArticleService $articleService): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? null;
        $orders = $request->request->all('order') ?? [];

        $columns = [
            0 => 'a.id',
            1 => 'a.title',
            2 => 'categories',
            3 => 'commentsCount',
            4 => 'likesCount',
            5 => 'a.createdAt',
        ];

        $orderColumn = $columns[$orders[0]['column'] ?? 0] ?? 'a.id';
        $orderDir = $orders[0]['dir'] ?? 'desc';

        $results = $articleService->getDataTableResponseData(
            $start,
            $length,
            $search,
            $orderColumn,
            $orderDir,
            fn ($id) => $this->generateUrl('app_article_comments', ['id' => $id]),
            fn ($article) => $this->renderView('article/_actions.html.twig', ['article' => $article])
        );

        return $this->json([
            'success' => true,
            'draw' => $draw,
            'recordsTotal' => $results['totalCount'],
            'recordsFiltered' => $results['filteredCount'],
            'data' => $results['data'],
            'message' => 'Articles fetched successfully.'
        ]);
    }


    #[Route('/api/articles/search', name: 'api_articles_search', methods: ['GET'])]
    public function search(Request $request, ArticleService $articleService): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Query too short.',
                'data' => [
                    'results' => []
                ]
            ]);
        }

        $results = $articleService->searchArticles($query);

        return new JsonResponse([
            'success' => true,
            'message' => 'Articles fetched successfully.',
            'data' => [
                'results' => $results
            ]
        ]);
    }


    #[Route('/{id}/comment', name: 'api_article_comment', methods: ['POST'])]
    public function addComment(Article $article, Request $request, ArticleService $articleService): JsonResponse
    {
        $result = $articleService->handleAddComment($article, $request);

        if ($result['success']) {
            return new JsonResponse([
                'success' => true,
                'commentHtml' => $this->renderView('comment/_comment.html.twig', [
                    'comment' => $result['comment']
                ]),
                'commentsCount' => $article->getComments()->count(),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'error' => $result['errors'][0] ?? 'Formulaire invalide',
        ], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}/like', name: 'api_article_like', methods: ['POST'])]
    public function likeArticle(Article $article, ArticleService $articleService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        $liked = $articleService->toggleLike($article, $user);
        $likesCount = $articleService->getLikesCount($article);

        return new JsonResponse([
            'success' => true,
            'liked' => $liked,
            'likesCount' => $likesCount,
        ]);
    }
}