<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepository,
        private CategoryRepository $categoryRepository
    ) {}

    public function listUsers(): JsonResponse
    {
        $users = $this->em->getRepository(User::class)->findAll();
        return new JsonResponse($users, 200, ['groups' => 'user:read']);
    }

    public function listArticles(Request $request): JsonResponse
    {
        $start = (int)$request->query->get('start');
        $length = (int)$request->query->get('length');
        $search = $request->query->get('search');
        $orderColumn = $request->query->get('orderColumn');
        $allowedFields = ['a.id', 'a.title', 'a.createdAt', 'commentsCount', 'likesCount', 'categoriesCount'];

        if (!in_array($orderColumn, $allowedFields, true)) {
            $orderColumn = 'a.createdAt';
        }

        $orderDir = strtoupper($request->query->get('orderDir')) === 'ASC' ? 'ASC' : 'DESC';

        $results = $this->articleRepository->findForApi($start, $length, $search, $orderColumn, $orderDir);
        $ids = array_column($results['data'], 'id');
        $articles = [];

        foreach ($ids as $id) {
            $article = $this->articleRepository->find($id);
            if ($article !== null) {
                $articles[] = $article;
            }
        }

        return new JsonResponse([
            'data' => $articles,
            'totalCount' => $results['totalCount'],
            'filteredCount' => $results['filteredCount'],
        ], 200, ['groups' => 'user:read']);
    }

    public function showArticle(int $id): JsonResponse
    {
        $article = $this->articleRepository->find($id);

        if (!$article) {
            return new JsonResponse(['message' => 'Article not found'], 404);
        }

        return new JsonResponse($article, 200, ['groups' => 'article:read']);
    }

    public function addComment(Request $request, ?UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['articleId'], $data['content'])) {
            return new JsonResponse(['message' => 'Invalid payload'], 400);
        }

        $article = $this->articleRepository->find($data['articleId']);
        if (!$article) {
            return new JsonResponse(['message' => 'Article not found'], 404);
        }

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setContent($data['content']);
        $comment->setCreatedAt(new \DateTime());

        if ($user) {
            $comment->setUser($user);
        }

        $this->em->persist($comment);
        $this->em->flush();

        return new JsonResponse(['message' => 'Comment added'], 201);
    }

    public function addLike(Request $request, ?UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['articleId'])) {
            return new JsonResponse(['message' => 'Invalid payload'], 400);
        }

        $article = $this->articleRepository->find($data['articleId']);
        if (!$article) {
            return new JsonResponse(['message' => 'Article not found'], 404);
        }

        if ($user) {
            $existingLike = $this->em->getRepository(ArticleLike::class)->findOneBy([
                'article' => $article,
                'user' => $user,
            ]);

            if ($existingLike) {
                return new JsonResponse(['message' => 'Already liked'], 200);
            }
        }

        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Indian/Antananarivo')));

        if ($user) {
            $like->setUser($user);
        }

        $this->em->persist($like);
        $this->em->flush();

        return new JsonResponse(['message' => 'Like added'], 201);
    }

    public function createArticle(Request $request, ?UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !isset($data['title'], $data['content'], $data['categoryIds']) ||
            !is_array($data['categoryIds'])
        ) {
            return new JsonResponse(['message' => 'Invalid payload'], 400);
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setCreatedAt(new \DateTime('now', new \DateTimeZone('Indian/Antananarivo')));

        foreach ($data['categoryIds'] as $catId) {
            $category = $this->categoryRepository->find($catId);
            if ($category) {
                $article->addCategory($category);
            }
        }

        if ($user) {
            $article->setAuthor($user);
        }

        $this->em->persist($article);
        $this->em->flush();

        return new JsonResponse($article, 201, ['groups' => 'article:read']);
    }

    public function fetchCategories(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();
        return new JsonResponse($categories, 200, ['groups' => 'category:read']);
    }

    public function createCategory(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'])) {
            return new JsonResponse(['message' => 'Invalid payload'], 400);
        }

        $category = new Category();
        $category->setTitle($data['title']);
        $category->setDescription('no description');
        $category->setCreatedAd(new \DateTime('now', new \DateTimeZone('Indian/Antananarivo')));

        $this->em->persist($category);
        $this->em->flush();

        return new JsonResponse($category, 201, ['groups' => 'category:read']);
    }

    public function updateCategory(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['message' => 'Category not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['title'])) {
            return new JsonResponse(['message' => 'Invalid payload'], 400);
        }

        $category->setTitle($data['title']);
        $this->em->flush();

        return new JsonResponse($category, 200, ['groups' => 'category:read']);
    }

    public function deleteCategory(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return new JsonResponse(['message' => 'Category not found'], 404);
        }

        $this->em->remove($category);
        $this->em->flush();

        return new JsonResponse(['message' => 'Category deleted'], 200);
    }
}
