<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\ArticleLike;
use App\Form\ArticleForm;
use App\Form\CommentForm;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\ArticleLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class ArticleService
{
    private ArticleRepository $articleRepository;
    private CommentRepository $commentRepository;
    private ArticleLikeRepository $likeRepository;
    private EntityManagerInterface $entityManager;
    private FormFactoryInterface $formFactory;

    public function __construct(
        ArticleRepository $articleRepository,
        CommentRepository $commentRepository,
        ArticleLikeRepository $likeRepository,
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory
    ) {
        $this->articleRepository = $articleRepository;
        $this->commentRepository = $commentRepository;
        $this->likeRepository = $likeRepository;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    public function getCommentsForArticle(Article $article): array
    {
        return $this->commentRepository->findBy(
            ['article' => $article],
            ['createdAt' => 'DESC']
        );
    }

    public function getCommentForm(Article $article): FormInterface
    {
        $comment = new Comment();
        return $this->formFactory->create(CommentForm::class, $comment, [
            'article_id' => $article->getId(),
        ]);
    }

    public function handleNewArticle(Request $request): ?Article
    {
        $article = new Article();
        $form = $this->formFactory->create(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($article);
            $this->entityManager->flush();
            return $article;
        }

        return null;
    }

    public function getArticleForm(Article $article): FormInterface
    {
        return $this->formFactory->create(ArticleForm::class, $article);
    }

    public function handleEditArticle(Article $article, Request $request): bool
    {
        $form = $this->formFactory->create(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    public function deleteArticle(Article $article): void
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    }

    public function getDataTableResponseData(int $start, int $length, ?string $search, string $orderColumn, string $orderDir, callable $urlGenerator, callable $actionsRenderer): array
    {
        $results = $this->articleRepository->findForDataTable($start, $length, $search, $orderColumn, $orderDir);

        $data = [];
        foreach ($results['data'] as $article) {
            $categoryNames = array_map(fn($c) => $c->getTitle(), $article->getCategories()->toArray());

            $data[] = [
                'id' => $article->getId(),
                'title' => sprintf(
                    '<a href="%s">%s</a>',
                    $urlGenerator($article->getId()),
                    htmlspecialchars($article->getTitle())
                ),
                'categories' => implode(' - ', $categoryNames),
                'commentsCount' => $article->getComments()->count(),
                'likesCount' => $article->getArticleLikes()->count(),
                'createdAt' => $article->getCreatedAt()->format('d/m/Y H:i'),
                'actions' => $actionsRenderer($article),
            ];
        }

        return [
            'totalCount' => $results['totalCount'],
            'filteredCount' => $results['filteredCount'],
            'data' => $data,
        ];
    }


    public function searchArticles(string $query): array
    {
        $articles = $this->articleRepository->searchByTitle($query, 10);

        return array_map(function ($article) {
            $categoryNames = array_map(
                fn ($category) => $category->getTitle(),
                $article->getCategories()->toArray()
            );

            return [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'categories' => $categoryNames,
            ];
        }, $articles);
    }


    public function handleAddComment(Article $article, Request $request): array
    {
        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setCreatedAt(new \DateTime());

        $form = $this->formFactory->create(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return ['success' => true, 'comment' => $comment];
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return [
            'success' => false,
            'errors' => $errors,
        ];
    }

    public function toggleLike(Article $article, UserInterface $user): bool
    {
        $existingLike = $this->likeRepository->findOneBy([
            'article' => $article,
            'user' => $user,
        ]);

        if ($existingLike) {
            $this->entityManager->remove($existingLike);
            $this->entityManager->flush();
            return false;
        }

        $like = new ArticleLike();
        $like->setArticle($article);
        $like->setUser($user);
        $like->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($like);
        $this->entityManager->flush();

        return true;
    }

    public function getLikesCount(Article $article): int
    {
        return $this->likeRepository->count(['article' => $article]);
    }
}