<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleForm;
use App\Form\CommentForm;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ArticleLikeRepository;
use App\Entity\ArticleLike;

#[Route('/article')]
final class ArticleController extends AbstractController
{
    private ArticleRepository $articleRepository;
    private EntityManagerInterface $entityManager;
    private FormFactoryInterface $formFactory;
    private CommentRepository $commentRepository;
    public function __construct(ArticleRepository $articleRepository, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, CommentRepository $commentRepository){
        $this->articleRepository = $articleRepository;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->commentRepository = $commentRepository;
    }
    // #[Route(name: 'app_article_index', methods: ['GET'])]
    // public function index(): Response
    // {
    //     $articles = $this->articleRepository->findAll();
    //     $forms = [];

    //     foreach ($articles as $article) {
    //         $comment = new Comment();
    //         $form = $this->formFactory->create(CommentForm::class, $comment, [
    //             'article_id' => $article->getId(),
    //             'action' => $this->generateUrl('app_comment_new', [
    //                 'article_id' => $article->getId(),
    //             ]),
    //         ]);
    //         $forms[$article->getId()] = $form->createView();
    //     }

    //     return $this->render('article/index.html.twig', [
    //         'articles' => $articles,
    //         'forms' => $forms,
    //     ]);
    // }
    #[Route(name: 'app_article_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('article/index.html.twig');
    }

    #[Route('/{id}/comments', name: 'app_article_comments', methods: ['GET'])]
    public function showByArticle(Article $article, CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy(['article' => $article], ['createdAt' => 'DESC']);
        $comment = new Comment();
        $form = $this->formFactory->create(CommentForm::class, $comment, [
            'article_id' => $article->getId(),
            'action' => $this->generateUrl('app_comment_new', [
                'article_id' => $article->getId(),
            ]),
        ]);
        
        return $this->render('article/show.html.twig', [
            'article' => $article,
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
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
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleForm::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/api/articles', name: 'api_articles_datatable', methods: ['POST'])]
    public function datatable(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? null;
        $orders = $request->request->all('order') ?? [];

        // Colonnes pour le tri
        $columns = [
            0 => 'a.id',
            1 => 'a.title',
            2 => 'categories',
            3 => 'commentsCount',
            4 => 'likesCount',
            5 => 'a.createdAt',
        ];

        // Ordre de tri
        $orderColumn = $columns[$orders[0]['column'] ?? 0] ?? 'a.id';
        $orderDir = $orders[0]['dir'] ?? 'desc';

        // Récupération des données
        $results = $articleRepository->findForDataTable($start, $length, $search, $orderColumn, $orderDir);

        // Formatage des données pour DataTables
        $data = [];
        foreach ($results['data'] as $article) {
            $categoryNames = array_map(function($category) {
                return sprintf('%s', $category->getTitle());
            }, $article->getCategories()->toArray());

            $data[] = [
                'id' => $article->getId(),
                'title' => sprintf(
                    '<a href="%s">%s</a>',
                    $this->generateUrl('app_article_comments', ['id' => $article->getId()]),
                    htmlspecialchars($article->getTitle())
                ),
                'categories' => implode(' - ', $categoryNames),
                'commentsCount' => $article->getComments()->count(),
                'likesCount' => $article->getArticleLikes()->count(),
                'createdAt' => $article->getCreatedAt()->format('d/m/Y H:i'),
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article
                ])
            ];
        }

        // Réponse au format attendu par DataTables
        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $results['totalCount'],
            'recordsFiltered' => $results['filteredCount'],
            'data' => $data
        ]);
    }

    #[Route('/api/articles/search', name: 'api_articles_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }

        $articles = $articleRepository->searchByTitle($query, 10);

        $results = [];
        foreach ($articles as $article) {
            $categoryNames = array_map(function($category) {
                return $category->getTitle();
            }, $article->getCategories()->toArray());

            $results[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'categories' => $categoryNames
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/{id}/comment', name: 'api_article_comment', methods: ['POST'])]
    public function addComment(Article $article, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setCreatedAt(new \DateTime());

        $form = $this->createForm(CommentForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'commentHtml' => $this->renderView('comment/_comment.html.twig', [
                    'comment' => $comment
                ]),
                'commentsCount' => $article->getComments()->count()
            ]);
        }

        // En cas d'erreur, renvoyer les erreurs du formulaire
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'error' => count($errors) > 0 ? $errors[0] : 'Formulaire invalide'
        ], Response::HTTP_BAD_REQUEST);
    }

   #[Route('/{id}/like', name: 'api_article_like', methods: ['POST'])]
    public function likeArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleLikeRepository $likeRepository
    ): JsonResponse {
        $ipAddress = $request->getClientIp();

        $existingLike = $likeRepository->findOneBy([
            'article' => $article,
            'ipAddress' => $ipAddress,
        ]);

        if ($existingLike) {
            $entityManager->remove($existingLike);
            $entityManager->flush();

            $liked = false;
        } else {
            $like = new ArticleLike();
            $like->setArticle($article);
            $like->setIpAddress($ipAddress);
            $like->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($like);
            $entityManager->flush();

            $liked = true;
        }

        // Get fresh count from repository after flush
        $likesCount = $likeRepository->count(['article' => $article]);

        return new JsonResponse([
            'success' => true,
            'liked' => $liked,
            'likesCount' => $likesCount,
        ]);
    }


}
