<?php

namespace App\Controller\Api;

use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(private ApiService $apiService) {}

    #[Route('/users', name: 'api_user_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->apiService->listUsers();
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['message' => 'Login is handled by Lexik JWT']);
    }

    #[Route('/articles', name: 'api_articles_list', methods: ['GET'])]
    public function articlesList(Request $request): JsonResponse
    {
        return $this->apiService->listArticles($request);
    }

    #[Route('/articles/{id}', name: 'api_article_show', methods: ['GET'])]
    public function showArticle(int $id): JsonResponse
    {
        return $this->apiService->showArticle($id);
    }

    #[Route('/comments', name: 'api_comment_add', methods: ['POST'])]
    public function addComment(Request $request): JsonResponse
    {
        return $this->apiService->addComment($request, $this->getUser());
    }

    #[Route('/likes', name: 'api_like_add', methods: ['POST'])]
    public function addLike(Request $request): JsonResponse
    {
        return $this->apiService->addLike($request, $this->getUser());
    }

    #[Route('/articles', name: 'api_article_create', methods: ['POST'])]
    public function createArticle(Request $request): JsonResponse
    {
        return $this->apiService->createArticle($request, $this->getUser());
    }

    #[Route('/categories', name: 'api_category_list', methods: ['GET'])]
    public function fetchCategories(): JsonResponse
    {
        return $this->apiService->fetchCategories();
    }

    #[Route('/categories', name: 'api_category_create', methods: ['POST'])]
    public function createCategory(Request $request): JsonResponse
    {
        return $this->apiService->createCategory($request);
    }

    #[Route('/categories/{id}', name: 'api_category_update', methods: ['PUT'])]
    public function updateCategory(int $id, Request $request): JsonResponse
    {
        return $this->apiService->updateCategory($id, $request);
    }

    #[Route('/categories/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function deleteCategory(int $id): JsonResponse
    {
        return $this->apiService->deleteCategory($id);
    }
}