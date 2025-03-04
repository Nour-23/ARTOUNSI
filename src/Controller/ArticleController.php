<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/article')]
final class ArticleController extends AbstractController
{
    #[Route(name: 'app_article_index', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 3;
        $offset = ($page - 1) * $limit;
    
        // Get paginated articles
        $qb = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        $articles = $qb->getQuery()->getResult();
    
        // Count total articles
        $total = $articleRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    
        $totalPages = ceil($total / $limit);
    
        return $this->render('article/index.html.twig', [
            'articles'     => $articles,
            'currentPage'  => $page,
            'totalPages'   => $totalPages,
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory_article'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle file upload exception if needed
                }

                $article->setImage($newFilename);
            }

            $entityManager->persist($article);
            $entityManager->flush();
            //SLACK
            $postData = [
                'text' => 'A new article has been published: "' . $article->getNom() . '"'
            ];
            $webhookUrl = 'https://hooks.slack.com/services/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';
            
            // Initialize cURL
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory_article'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Handle file upload exception if needed
                }
                $article->setImage($newFilename);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->get('csrf_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/search', name: 'app_article_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        $articles = $articleRepository->searchArticles($query);

        $data = [];
        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'nom' => $article->getNom(),
                'description' => $article->getDescription(),
                'image' => $article->getImage(),
                'prix' => $article->getPrix(),
                'categorie' => $article->getCategorie() ? $article->getCategorie()->getNom() : null,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/filter', name: 'app_article_filter', methods: ['GET'])]
    public function filter(Request $request, ArticleRepository $articleRepository): JsonResponse
    {
        $order = $request->query->get('order', 'asc');
        $articles = $articleRepository->sortArticlesByPrix($order);
    
        $data = [];
        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'nom' => $article->getNom(),
                'description' => $article->getDescription(),
                'image' => $article->getImage(),
                'prix' => $article->getPrix(),
                'categorie' => $article->getCategorie() ? $article->getCategorie()->getNom() : null,
            ];
        }
    
        return new JsonResponse($data);
    }
    
}