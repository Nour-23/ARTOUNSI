<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categorie')]
final class CategorieController extends AbstractController
{
    #[Route(name: 'app_categorie_index', methods: ['GET'])]
    public function index(Request $request, CategorieRepository $categorieRepository): Response
    {
        $page  = $request->query->getInt('page', 1);
        $limit = 4;
        $offset = ($page - 1) * $limit;
        $query = $request->query->get('query', '');
        $sort  = $request->query->get('sort', 'asc');

        // Build the query for results.
        $qb = $categorieRepository->createQueryBuilder('c')
               ->orderBy('c.nom', strtolower($sort) === 'desc' ? 'DESC' : 'ASC')
               ->setFirstResult($offset)
               ->setMaxResults($limit);

        if ($query) {
            $qb->where('c.nom LIKE :query')
               ->setParameter('query', '%'.$query.'%');
        }

        $categories = $qb->getQuery()->getResult();

        // Count total matching results.
        $countQb = $categorieRepository->createQueryBuilder('c')
                   ->select('COUNT(c.id)');
        if ($query) {
            $countQb->where('c.nom LIKE :query')
                    ->setParameter('query', '%'.$query.'%');
        }
        $total = $countQb->getQuery()->getSingleScalarResult();
        $totalPages = ceil($total / $limit);

        return $this->render('categorie/index.html.twig', [
            'categories'  => $categories,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'query'       => $query,
            'sort'        => $sort,
        ]);
    }

    #[Route('/new', name: 'app_categorie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();

            // Prepare Slack notification data
            $postData = [
                'text' => 'A new category has been added: "' . $categorie->getNom() . '"'
            ];
            $webhookUrl = 'https://hooks.slack.com/services/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';
    
            // Initialize cURL to send the payload to Slack
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);

            curl_close($ch);

            return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Categorie $categorie): Response
    {
        return $this->render('categorie/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie/edit.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Categorie $categorie, EntityManagerInterface $entityManager, CategorieRepository $categorieRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorie->getId(), $request->get('_token'))) {
            // Reassign articles to a fallback category ("Aucune catégorie")
            $noneCategory = $categorieRepository->findOneBy(['nom' => 'Aucune catégorie']);
            if (!$noneCategory) {
                $noneCategory = new Categorie();
                $noneCategory->setNom('Aucune catégorie');
                $entityManager->persist($noneCategory);
                $entityManager->flush();
            }
            foreach ($categorie->getArticles() as $article) {
                $article->setCategorie($noneCategory);
            }
            $entityManager->flush();

            $entityManager->remove($categorie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_categorie_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/filter', name: 'app_categorie_filter', methods: ['GET'])]
    public function filter(Request $request, CategorieRepository $categorieRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        $sort  = $request->query->get('sort', 'asc');

        $qb = $categorieRepository->createQueryBuilder('c');
        if ($query) {
            $qb->where('c.nom LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        $order = strtolower($sort) === 'desc' ? 'DESC' : 'ASC';
        $qb->orderBy('c.nom', $order);
        $categories = $qb->getQuery()->getResult();

        $data = [];
        foreach ($categories as $categorie) {
            $data[] = [
                'id'  => $categorie->getId(),
                'nom' => $categorie->getNom(),
            ];
        }

        return new JsonResponse($data);
    }
}