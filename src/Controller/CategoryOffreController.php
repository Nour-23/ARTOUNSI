<?php

namespace App\Controller;

use App\Entity\CategoryOffre;
use App\Form\CategoryOffreType;
use App\Repository\CategoryOffreRepository;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/category/offre')]
final class CategoryOffreController extends AbstractController
{
    #[Route(name: 'app_category_offre_index', methods: ['GET'])]
    public function index(CategoryOffreRepository $categoryOffreRepository, OffreRepository $offreRepository  // 🔹 Injection du repository des offres
    ): Response {
        return $this->render('category_offre/index.html.twig', [
            'category_offres' => $categoryOffreRepository->findAll(),
            'offres' => $offreRepository->findAll(),  // 🔹 Transmission des offres au template
        ]);
    }

    #[Route('/new', name: 'app_category_offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categoryOffre = new CategoryOffre();
        $form = $this->createForm(CategoryOffreType::class, $categoryOffre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categoryOffre);
            $entityManager->flush();

            return $this->redirectToRoute('app_category_offre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category_offre/new.html.twig', [
            'category_offre' => $categoryOffre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_offre_show', methods: ['GET'])]
    public function show(CategoryOffre $categoryOffre): Response
    {
        return $this->render('category_offre/show.html.twig', [
            'category_offre' => $categoryOffre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategoryOffre $categoryOffre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryOffreType::class, $categoryOffre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_category_offre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category_offre/edit.html.twig', [
            'category_offre' => $categoryOffre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_offre_delete', methods: ['POST'])]
    public function delete(Request $request, CategoryOffre $categoryOffre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categoryOffre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($categoryOffre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_category_offre_index', [], Response::HTTP_SEE_OTHER);
    }
    
}
