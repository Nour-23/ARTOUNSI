<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/articlefront')]
final class FrontarticleController extends AbstractController
{
    #[Route(name: 'app_article_indexfront', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('article/index2.html.twig', [
            'articles' => $articleRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_article_showfront', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show2.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/checkout/{id}', name: 'app_article_checkoutfront', methods: ['GET'])]
    public function checkout(Article $article, UrlGeneratorInterface $urlGenerator): Response
    {
        // Set your Stripe secret key (ensure it is defined in your .env file and accessed via parameters)
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        // Create a new Checkout Session
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $article->getNom(),
                    ],
                    // Conversion: price in TND to USD in cents if needed,
                    // Here assume article->getPrix() is in USD; otherwise adjust accordingly.
                    'unit_amount' => $article->getPrix() * 100, 
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('app_article_successfront', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $urlGenerator->generate('app_article_cancelfront', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url);
    }


    #[Route('/success', name: 'app_article_successfront', methods: ['GET'])]
    public function success(Request $request, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
    {
        // Retrieve parameters from the query
        $sessionId = $request->query->get('session_id', 'Unknown Session');
        $articleId = $request->query->get('article_id');
    
        // Prepare a details string
        $details = "Payment successful. Session ID: " . $sessionId;
    
        // If an article_id is provided, fetch the article and decrease its stock
        if ($articleId) {
            $article = $articleRepository->find($articleId);
            if ($article) {
                $currentStock = $article->getNbrearticle();
                if ($currentStock > 0) {
                    $article->setNbrearticle($currentStock - 1);
                    $entityManager->flush();
                    $details .= " | Stock updated: " . ($currentStock - 1) . " remaining.";
                } else {
                    $details .= " | Out of stock!";
                }
            }
        }
    
        return $this->render('article/success.html.twig', [
            'details' => $details,
        ]);
    }

    #[Route('/cancel', name: 'app_article_cancelfront', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('article/cancel.html.twig');
    }
}