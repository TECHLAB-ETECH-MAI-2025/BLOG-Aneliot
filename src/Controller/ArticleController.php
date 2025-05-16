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
    #[Route(name: 'app_article_index', methods: ['GET'])]
    public function index(): Response
    {
        $articles = $this->articleRepository->findAll();
        $forms = [];

        foreach ($articles as $article) {
            $comment = new Comment();
            $form = $this->formFactory->create(CommentForm::class, $comment, [
                'article_id' => $article->getId(),
                'action' => $this->generateUrl('app_comment_new', [
                    'article_id' => $article->getId(),
                ]),
            ]);
            $forms[$article->getId()] = $form->createView();
        }

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'forms' => $forms,
        ]);
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
}
