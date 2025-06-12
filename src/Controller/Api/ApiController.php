<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Article;
use App\Entity\ArticleLike;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/users', name: 'api_user_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();

        return $this->json($users, 200, [], ['groups' => 'user:read']);
    }
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request)
    {
        return $this->json(['message' => 'Login should be handled by Lexik JWT']);
    }
    #[Route('/articles', name: 'api_articles_list', methods: ['GET'])]
    public function articlesList(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
       $start = (int) $request->query->get('start');
        $length = (int) $request->query->get('length');
        $search = $request->query->get('search');
        $orderColumn = $request->query->get('orderColumn');
        $allowedFields = ['a.id', 'a.title', 'a.createdAt', 'commentsCount', 'likesCount', 'categoriesCount'];
        if (!in_array($orderColumn, $allowedFields, true)) {
            $orderColumn = 'a.createdAt';
        }
        $orderDir = strtoupper($request->query->get('orderDir')) === 'ASC' ? 'ASC' : 'DESC';

        $results = $articleRepository->findForApi($start, $length, $search, $orderColumn, $orderDir);
        $ids = array_column($results['data'], 'id');
        $articles = $articleRepository->findBy(['id' => $ids]);
        return $this->json([
            'data' => $articles,
            'totalCount' => $results['totalCount'],
            'filteredCount' => $results['filteredCount'],
        ], 200, [], ['groups' => 'article:read']);
    }
    #[Route('/articles/{id}', name: 'api_article_show', methods: ['GET'])]
    public function showArticle(int $id, ArticleRepository $articleRepository): JsonResponse
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            return $this->json(['message' => 'Article not found'], 404);
        }

        return $this->json($article, 200, [], ['groups' => 'article:read']);
    }
    #[Route('/comments', name: 'api_comment_add', methods: ['POST'])]
    public function addComment(Request $request, EntityManagerInterface $em, ArticleRepository $articleRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['articleId'], $data['content'])) {
            return $this->json(['message' => 'Invalid payload'], 400);
        }

        $article = $articleRepository->find($data['articleId']);
        if (!$article) {
            return $this->json(['message' => 'Article not found'], 404);
        }

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setContent($data['content']);
        $comment->setCreatedAt(new \DateTime());

        if ($this->getUser()) {
            $comment->setUser($this->getUser());
        }

        $em->persist($comment);
        $em->flush();

        return $this->json(['message' => 'Comment added'], 201);
    }
    #[Route('/likes', name: 'api_like_add', methods: ['POST'])]
    public function addLike(Request $request, EntityManagerInterface $em, ArticleRepository $articleRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['articleId'])) {
            return $this->json(['message' => 'Invalid payload'], 400);
        }

        $article = $articleRepository->find($data['articleId']);
        if (!$article) {
            return $this->json(['message' => 'Article not found'], 404);
        }
        $user = $this->getUser();
        if ($user) {
            $existingLike = $em->getRepository(ArticleLike::class)->findOneBy([
                'article' => $article,
                'user' => $user,
            ]);
            if ($existingLike) {
                return $this->json(['message' => 'Already liked'], 200);
            }
        }

        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setCreatedAt(new \DateTimeImmutable());

        if ($user) {
            $like->setUser($this->getUser());
        }

        $em->persist($like);
        $em->flush();

        return $this->json(['message' => 'Like added'], 201);
    }
    #[Route('/articles', name: 'api_article_create', methods: ['POST'])]
    public function createArticle(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            !isset($data['title'], $data['content'], $data['categoryIds']) ||
            !is_array($data['categoryIds'])
        ) {
            return $this->json(['message' => 'Invalid payload'], 400);
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setCreatedAt(new \DateTime());

        $categoryRepo = $em->getRepository(Category::class);
        foreach ($data['categoryIds'] as $catId) {
            $category = $categoryRepo->find($catId);
            if ($category) {
                $article->addCategory($category);
            }
        }

        if ($this->getUser()) {
            $article->setAuthor($this->getUser());
        }

        $em->persist($article);
        $em->flush();

        return $this->json($article, 201, [], ['groups' => 'article:read']);
    }
    #[Route('/categories', name: 'api_category_list', methods: ['GET'])]
    public function fetchCategories(EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(Category::class)->findAll();
        return $this->json($categories, 200, [], ['groups' => 'category:read']);
    }
    #[Route('/categories', name: 'api_category_create', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['title'])) {
                return $this->json(['message' => 'Invalid payload'], 400);
            }

            $category = new Category();
            $category->setTitle($data['title']);
            $category->setDescription('no description');
            $category->setCreatedAd(new \DateTime());
            $em->persist($category);
            $em->flush();

            return $this->json($category, 201, [], ['groups' => 'category:read']);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    #[Route('/categories/{id}', name: 'api_category_update', methods: ['PUT'])]
    public function updateCategory(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $category = $em->getRepository(Category::class)->find($id);
        if (!$category) {
            return $this->json(['message' => 'Category not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['title'])) {
            return $this->json(['message' => 'Invalid payload'], 400);
        }

        $category->setTitle($data['title']);
        $em->flush();

        return $this->json($category, 200, [], ['groups' => 'category:read']);
    }
    #[Route('/categories/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function deleteCategory(int $id, EntityManagerInterface $em): JsonResponse
    {
        $category = $em->getRepository(Category::class)->find($id);
        if (!$category) {
            return $this->json(['message' => 'Category not found'], 404);
        }

        $em->remove($category);
        $em->flush();

        return $this->json(['message' => 'Category deleted'], 200);
    }
    
}
