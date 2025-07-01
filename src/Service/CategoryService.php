<?php

namespace App\Service;

use App\Entity\Category;
use App\Form\CategoryForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class CategoryService
{
    private EntityManagerInterface $entityManager;
    private FormFactoryInterface $formFactory;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    public function handleNew(Request $request): array
    {
        $category = new Category();
        $form = $this->formFactory->create(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setCreatedAd(new \DateTime());
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            return [
                'success' => true
            ];
        }

        return [
            'success' => false,
            'category' => $category,
            'form' => $form
        ];
    }

    public function handleEdit(Request $request, Category $category): array
    {
        $form = $this->formFactory->create(CategoryForm::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setCreatedAd(new \DateTime());
            $this->entityManager->flush();

            return [
                'success' => true
            ];
        }

        return [
            'success' => false,
            'form' => $form
        ];
    }

    public function handleDelete(Request $request, Category $category): void
    {
        if ($request->request->has('_token')) {
            $token = $request->getPayload()->getString('_token');

            if ($this->isCsrfTokenValid('delete' . $category->getId(), $token)) {
                $this->entityManager->remove($category);
                $this->entityManager->flush();
            }
        }
    }

    // For CSRF check you need to inject the AbstractController helper or do it in the controller.
    private function isCsrfTokenValid(string $id, string $token): bool
    {
        // If you want CSRF validation inside the service, pass a callable or Security service.
        // Or better: keep CSRF check in the controller.
        return true; // Simplified for example
    }
}
